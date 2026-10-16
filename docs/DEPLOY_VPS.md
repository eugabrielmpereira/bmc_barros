# Deploy em VPS — BMC Barros (loja única)

Guia para colocar a loja no ar numa VPS Linux (Ubuntu 24.04) com Apache, PHP 8.3 e MySQL/MariaDB.
Para uma loja só, use o modo simples: **um banco, `MULTITENANT_DB=false`, `TENANT_RESOLUTION=env`**.

> Tempo estimado: 1–2 horas na primeira vez. Tudo abaixo roda como um usuário com `sudo`.

---

## 1. Servidor

VPS mínima recomendada: 1 vCPU, 2 GB RAM, 25 GB SSD (Hostinger, Contabo, DigitalOcean, Magalu Cloud…).

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y apache2 mariadb-server git unzip curl \
  php8.3 php8.3-cli php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl php8.3-intl php8.3-zip php8.3-sodium \
  libapache2-mod-php8.3 certbot python3-certbot-apache
sudo a2enmod rewrite headers expires deflate
curl -sS https://getcomposer.org/installer | php && sudo mv composer.phar /usr/local/bin/composer

# Firewall
sudo ufw allow OpenSSH && sudo ufw allow 'Apache Full' && sudo ufw enable
```

Fuso horário (datas de pedido e vencimento do Pix):

```bash
sudo timedatectl set-timezone America/Sao_Paulo
sudo sed -i 's#^;date.timezone =.*#date.timezone = America/Sao_Paulo#' /etc/php/8.3/apache2/php.ini /etc/php/8.3/cli/php.ini
```

## 2. Banco de dados

```bash
sudo mysql_secure_installation
sudo mysql -e "CREATE DATABASE erp_product CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'bmc'@'localhost' IDENTIFIED BY 'TROQUE-POR-UMA-SENHA-FORTE';
GRANT ALL PRIVILEGES ON erp_product.* TO 'bmc'@'localhost'; FLUSH PRIVILEGES;"
```

## 3. Código

```bash
sudo mkdir -p /var/www/bmc && sudo chown $USER:www-data /var/www/bmc
git clone https://github.com/eugabrielmpereira/bmc_barros.git /var/www/bmc
cd /var/www/bmc
composer install --no-dev --optimize-autoloader
cp .env.example .env
```

Edite o `.env` (nunca vai para o Git):

```dotenv
APP_ENV=production
APP_DEBUG=false
BASE_URL=https://www.seudominio.com.br
ACTIVE_CLIENT=bmc
TENANT_RESOLUTION=env
MULTITENANT_DB=false

DB_HOST=localhost
DB_NAME=erp_product
DB_USER=bmc
DB_PASS=TROQUE-POR-UMA-SENHA-FORTE

# Gere com: php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"
APP_KEY=

# CNPJ exibido no rodapé (obrigatório para loja online — Decreto 7.962/2013)
STORE_CNPJ=00.000.000/0001-00

MAIL_ENABLED=true
MAIL_HOST=smtp.seuprovedor.com
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM=pedidos@seudominio.com.br
MAIL_FROM_NAME="BMC Barros"
MAIL_ADMIN_TO=dono@seudominio.com.br

ASAAS_ENABLED=true
ASAAS_SANDBOX=false
ASAAS_API_KEY=            # chave de PRODUÇÃO do painel Asaas
ASAAS_WEBHOOK_TOKEN=      # mínimo 32 caracteres, igual ao configurado no painel
PAYMENT_CARD_ENABLED=true
PAYMENT_CARD_MAX_INSTALLMENTS=6
PAYMENT_CARD_MIN_INSTALLMENT=50
```

Tabelas e primeiro acesso:

```bash
php migrate.php run
php database/seeds/criar_admin.php "dono@seudominio.com.br" "UmaSenhaBemForte123"
php database/seeds/seed_delivery_zones.php   # zonas de EXEMPLO — revise em /admin/frete
```

> Não rode `seed_catalog_mvp.php` em produção: ele cria produtos de demonstração. Cadastre os produtos reais no admin.

Permissões (o Apache precisa escrever só em uploads e storage):

```bash
sudo chown -R $USER:www-data /var/www/bmc
sudo find /var/www/bmc -type d -exec chmod 750 {} \;
sudo find /var/www/bmc -type f -exec chmod 640 {} \;
sudo chmod -R 770 /var/www/bmc/storage /var/www/bmc/public/uploads
sudo chgrp www-data /var/www/bmc/.env && chmod 640 /var/www/bmc/.env
```

## 4. Apache (document root = `public/`)

`/etc/apache2/sites-available/bmc.conf`:

```apache
<VirtualHost *:80>
    ServerName seudominio.com.br
    ServerAlias www.seudominio.com.br
    DocumentRoot /var/www/bmc/public

    <Directory /var/www/bmc/public>
        AllowOverride All
        Require all granted
    </Directory>

    # Nada fora de public/ é servido
    <Directory /var/www/bmc>
        Require all denied
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/bmc-error.log
    CustomLog ${APACHE_LOG_DIR}/bmc-access.log combined
</VirtualHost>
```

> A ordem dos blocos `<Directory>` não importa: o Apache aplica o caminho mais específico (`public`) por cima do pai.

```bash
sudo a2ensite bmc && sudo a2dissite 000-default && sudo systemctl reload apache2
```

## 5. Domínio e HTTPS

1. No registro do domínio (Registro.br etc.), crie registros **A** de `@` e `www` para o IP da VPS.
2. Quando o DNS propagar:

```bash
sudo certbot --apache -d seudominio.com.br -d www.seudominio.com.br
```

3. Com o HTTPS funcionando, descomente a linha `Strict-Transport-Security` em `public/.htaccess`.

O `.htaccess` já redireciona HTTP → HTTPS fora de `localhost`/`*.local`.

## 6. Asaas (Pix e cartão)

1. Painel Asaas → **Integrações → Chave de API**: gere a chave de produção e coloque em `ASAAS_API_KEY`.
2. **Integrações → Webhooks**: URL `https://www.seudominio.com.br/webhooks/asaas`, token igual a `ASAAS_WEBHOOK_TOKEN`, eventos de **cobrança** (PAYMENT_*).
3. Confirme no Asaas que **cartão de crédito** está habilitado na conta. Se não estiver, use `PAYMENT_CARD_ENABLED=false` e o site oferece só Pix.
4. O cartão é pago na **fatura hospedada do Asaas**: o site nunca recebe número de cartão (sem escopo PCI).

## 7. Backup diário do banco

```bash
sudo mkdir -p /var/backups/bmc && sudo chown $USER /var/backups/bmc
cat > ~/backup-bmc.sh <<'SH'
#!/bin/sh
set -e
STAMP=$(date +%F)
mysqldump --single-transaction --routines erp_product | gzip > /var/backups/bmc/erp_product-$STAMP.sql.gz
tar czf /var/backups/bmc/uploads-$STAMP.tgz -C /var/www/bmc/public uploads
find /var/backups/bmc -type f -mtime +14 -delete
SH
chmod +x ~/backup-bmc.sh
(crontab -l 2>/dev/null; echo "30 3 * * * $HOME/backup-bmc.sh") | crontab -
```

Credenciais do `mysqldump`: crie `~/.my.cnf` com `[client] user=bmc password=...` e `chmod 600 ~/.my.cnf`.
Copie os backups para fora da VPS (Google Drive, S3, Backblaze) — backup no mesmo disco não protege de perda da máquina.

## 8. Smoke test antes de divulgar

- [ ] `https://www.seudominio.com.br` abre com cadeado; `http://` redireciona.
- [ ] `/produtos`, uma página de produto e a busca funcionam.
- [ ] Carrinho → checkout com **entrega**: frete aparece conforme o bairro; total = itens + frete.
- [ ] Checkout com **retirada**: frete grátis.
- [ ] Pedido com **Pix**: QR aparece; pague R$ 1 de teste e veja o status mudar em `/admin/pedidos/:id`.
- [ ] Pedido com **cartão**: botão "Pagar com cartão" abre a fatura do Asaas.
- [ ] E-mails de pedido chegam para o cliente e para `MAIL_ADMIN_TO`.
- [ ] `/admin/frete`: valores e bairros reais revisados.
- [ ] Rodapé mostra o CNPJ real.
- [ ] `/robots.txt` libera o site e `/sitemap.xml` lista os produtos (só com `APP_ENV=production`).
- [ ] Cadastre o site no Google Search Console e envie o sitemap.

## 9. Atualizações

```bash
cd /var/www/bmc
~/backup-bmc.sh
git pull
composer install --no-dev --optimize-autoloader
php migrate.php run
sudo systemctl reload apache2
```

Rollback: `git checkout <commit-anterior>` + restaurar o dump do dia (`gunzip < arquivo.sql.gz | mysql erp_product`).
