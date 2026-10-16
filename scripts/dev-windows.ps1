# Ambiente local no Windows SEM XAMPP: PHP standalone + MySQL ja instalado + servidor embutido do PHP.
#
# Primeira vez (instala/configura tudo):
#   powershell -ExecutionPolicy Bypass -File scripts\dev-windows.ps1 -Setup -Demo
# Dia a dia (so sobe o site):
#   powershell -ExecutionPolicy Bypass -File scripts\dev-windows.ps1
#
# -Demo  cria produtos, categorias e zonas de frete de exemplo
# -Port  porta do site (padrao 8000)

param(
    [switch]$Setup,
    [switch]$Demo,
    [int]$Port = 8000
)

$ErrorActionPreference = 'Stop'
$Root = Split-Path -Parent $PSScriptRoot
Set-Location $Root

function Write-Step([string]$Text) { Write-Host "`n==> $Text" -ForegroundColor Cyan }
function Write-Ok([string]$Text) { Write-Host "    $Text" -ForegroundColor Green }
function Write-Warn([string]$Text) { Write-Host "    $Text" -ForegroundColor Yellow }

function Save-Utf8([string]$Path, [string]$Content) {
    [IO.File]::WriteAllText($Path, $Content, (New-Object Text.UTF8Encoding $false))
}

function Set-EnvValue([string]$Content, [string]$Key, [string]$Value) {
    $pattern = "(?m)^" + [regex]::Escape($Key) + "=.*$"
    if ($Content -match $pattern) {
        return [regex]::Replace($Content, $pattern, ($Key + '=' + $Value.Replace('$', '$$')))
    }
    return $Content.TrimEnd() + "`r`n" + $Key + '=' + $Value + "`r`n"
}

function Get-PhpDir {
    $cmd = Get-Command php -ErrorAction SilentlyContinue
    if (-not $cmd) { return $null }
    $item = Get-Item $cmd.Source
    $exe = $item.FullName
    if ($item.LinkType -and $item.Target) { $exe = @($item.Target)[0] }
    return Split-Path -Parent $exe
}

function Find-MySqlClient {
    $cmd = Get-Command mysql -ErrorAction SilentlyContinue
    if ($cmd) { return $cmd.Source }
    $candidates = Get-ChildItem 'C:\Program Files\MySQL' -Directory -ErrorAction SilentlyContinue |
        Where-Object { $_.Name -like 'MySQL Server*' } |
        ForEach-Object { Join-Path $_.FullName 'bin\mysql.exe' } |
        Where-Object { Test-Path $_ }
    return ($candidates | Select-Object -First 1)
}

function Invoke-Composer([string[]]$Arguments) {
    if (Get-Command composer -ErrorAction SilentlyContinue) {
        & composer @Arguments
    } else {
        & php (Join-Path $Root 'composer.phar') @Arguments
    }
    if ($LASTEXITCODE -ne 0) { throw "composer $($Arguments -join ' ') falhou." }
}

if ($Setup) {
    # 1. PHP --------------------------------------------------------------
    Write-Step 'PHP'
    if (-not (Get-Command php -ErrorAction SilentlyContinue)) {
        Write-Warn 'PHP nao encontrado. Instalando PHP 8.3 pelo winget...'
        winget install --id PHP.PHP.8.3 -e --accept-source-agreements --accept-package-agreements
        Write-Warn 'Feche este terminal, abra outro e rode o mesmo comando de novo (para o PATH atualizar).'
        exit 0
    }
    $phpDir = Get-PhpDir
    Write-Ok ((& php -r 'echo PHP_VERSION;') + " em $phpDir")

    # 2. php.ini com as extensoes do projeto ------------------------------
    Write-Step 'php.ini'
    $ini = Join-Path $phpDir 'php.ini'
    if (-not (Test-Path $ini)) {
        $template = Join-Path $phpDir 'php.ini-development'
        if (-not (Test-Path $template)) { throw "Nao achei php.ini nem php.ini-development em $phpDir" }
        Copy-Item $template $ini
        Write-Ok 'php.ini criado a partir do php.ini-development'
    }
    $iniText = Get-Content $ini -Raw
    $iniText = [regex]::Replace($iniText, '(?m)^;\s*extension_dir\s*=\s*"ext"\s*$', 'extension_dir = "ext"')
    foreach ($ext in @('curl', 'fileinfo', 'intl', 'mbstring', 'openssl', 'pdo_mysql', 'pdo_sqlite', 'sodium', 'zip')) {
        $iniText = [regex]::Replace($iniText, "(?m)^;\s*extension\s*=\s*$ext\s*$", "extension=$ext")
    }
    $iniText = [regex]::Replace($iniText, '(?m)^;?\s*date\.timezone\s*=.*$', 'date.timezone = America/Sao_Paulo')
    $iniText = [regex]::Replace($iniText, '(?m)^upload_max_filesize\s*=.*$', 'upload_max_filesize = 8M')
    Save-Utf8 $ini $iniText
    # Sem aspas duplas no codigo PHP: o PowerShell 5 as remove ao chamar programas externos.
    $loaded = & php -r 'echo implode(chr(44), array_filter([''pdo_mysql'', ''mbstring'', ''openssl'', ''sodium'', ''curl''], fn($e) => !extension_loaded($e)));'
    if ($loaded) { throw "Extensoes ainda desligadas no PHP: $loaded (confira $ini)" }
    Write-Ok 'pdo_mysql, mbstring, openssl, sodium, curl, intl ativas'

    # 3. Composer ---------------------------------------------------------
    Write-Step 'Composer'
    if (-not (Get-Command composer -ErrorAction SilentlyContinue) -and -not (Test-Path (Join-Path $Root 'composer.phar'))) {
        & php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
        & php composer-setup.php --quiet
        Remove-Item composer-setup.php -ErrorAction SilentlyContinue
        Write-Ok 'composer.phar baixado na pasta do projeto'
    }
    Invoke-Composer @('install', '--no-interaction')
    Write-Ok 'dependencias instaladas'

    # 4. .env -------------------------------------------------------------
    Write-Step '.env'
    $envPath = Join-Path $Root '.env'
    if (-not (Test-Path $envPath)) { Copy-Item (Join-Path $Root '.env.example') $envPath }
    $dbUser = Read-Host 'Usuario do MySQL (Enter = root)'
    if ([string]::IsNullOrWhiteSpace($dbUser)) { $dbUser = 'root' }
    $secure = Read-Host 'Senha do MySQL (Enter = sem senha)' -AsSecureString
    $dbPass = [Runtime.InteropServices.Marshal]::PtrToStringAuto([Runtime.InteropServices.Marshal]::SecureStringToBSTR($secure))

    $envText = Get-Content $envPath -Raw
    $envText = Set-EnvValue $envText 'APP_ENV' 'local'
    $envText = Set-EnvValue $envText 'APP_DEBUG' 'true'
    $envText = Set-EnvValue $envText 'BASE_URL' "http://localhost:$Port"
    $envText = Set-EnvValue $envText 'ACTIVE_CLIENT' 'bmc'
    $envText = Set-EnvValue $envText 'TENANT_RESOLUTION' 'env'
    $envText = Set-EnvValue $envText 'MULTITENANT_DB' 'false'
    $envText = Set-EnvValue $envText 'DB_HOST' '127.0.0.1'
    $envText = Set-EnvValue $envText 'DB_NAME' 'erp_product'
    $envText = Set-EnvValue $envText 'DB_USER' $dbUser
    $envText = Set-EnvValue $envText 'DB_PASS' $dbPass
    Save-Utf8 $envPath $envText
    Write-Ok ".env pronto (BASE_URL=http://localhost:$Port)"

    # 5. Banco ------------------------------------------------------------
    Write-Step 'Banco erp_product'
    $mysql = Find-MySqlClient
    $createSql = 'CREATE DATABASE IF NOT EXISTS erp_product CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;'
    if ($mysql) {
        $mysqlArgs = @('-h', '127.0.0.1', '-u', $dbUser)
        if ($dbPass -ne '') { $mysqlArgs += "-p$dbPass" }
        & $mysql @mysqlArgs -e $createSql
        if ($LASTEXITCODE -ne 0) { throw 'Nao consegui criar o banco. Confira usuario/senha e se o servico MySQL80 esta rodando.' }
        Write-Ok 'banco criado (ou ja existia)'
    } else {
        Write-Warn 'mysql.exe nao encontrado. Crie o banco no MySQL Workbench com:'
        Write-Warn "  $createSql"
        Read-Host 'Depois de criar, aperte Enter para continuar'
    }

    Write-Step 'Migrations'
    & php migrate.php run
    if ($LASTEXITCODE -ne 0) { throw 'migrate.php falhou.' }

    if ($Demo) {
        Write-Step 'Dados de exemplo'
        & php database/seeds/seed_catalog_attributes.php
        & php database/seeds/seed_catalog_mvp.php
        & php database/seeds/seed_delivery_zones.php
    }

    Write-Step 'Usuario do painel admin'
    $adminEmail = Read-Host 'E-mail do admin (Enter = pular)'
    if (-not [string]::IsNullOrWhiteSpace($adminEmail)) {
        $adminSecure = Read-Host 'Senha do admin (min. 8 caracteres)' -AsSecureString
        $adminPass = [Runtime.InteropServices.Marshal]::PtrToStringAuto([Runtime.InteropServices.Marshal]::SecureStringToBSTR($adminSecure))
        & php database/seeds/criar_admin.php $adminEmail $adminPass
    }

    Write-Host "`nSetup concluido." -ForegroundColor Green
}

# Servidor ---------------------------------------------------------------
if (-not (Get-Command php -ErrorAction SilentlyContinue)) {
    throw 'PHP nao encontrado. Rode primeiro com -Setup.'
}
if (-not (Test-Path (Join-Path $Root 'vendor\autoload.php'))) {
    throw 'Pasta vendor ausente. Rode primeiro com -Setup.'
}

$url = "http://localhost:$Port"
Write-Step "Loja em $url  |  Admin em $url/admin/login  |  Ctrl+C para parar"
Start-Process $url
& php -S "localhost:$Port" -t public server.php
