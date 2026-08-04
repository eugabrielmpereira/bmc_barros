# Design: Database-per-Tenant (Fase 1 — Levantamento)

**Data:** 2026-06-14  
**Status:** Proposta para revisão — **sem implementação de código**  
**Escopo:** Análise AS-IS e proposta de arquitetura para SaaS com um MySQL, um database por tenant, resolução por domínio.

---

## 1. Resumo executivo

Hoje o produto opera com **banco compartilhado único** (`DB_NAME` no `.env`), enquanto a resolução de **branding/rotas** já suporta multi-cliente via `ACTIVE_CLIENT` + `config/tenants.php` (modo `domain` ou `env`). O acoplamento ao banco é centralizado em `Core\Database::getInstance()` — singleton global **sem chave de tenant** — propagado por repositórios, services críticos (checkout, Pix, estoque) e scripts CLI.

A proposta é introduzir um **banco mestre** (`erp_master` ou similar) com registro operacional dos tenants e, por request/CLI, resolver o tenant → abrir conexão PDO dedicada → injetar ou disponibilizar via factory compatível com o padrão de testes já estabelecido nos Blocos 2–4.

**Princípio de migração:** BMC Barros continua no database atual (`erp_product`) como primeiro tenant registrado no mestre; nenhuma alteração de schema de negócio é necessária na Fase 1 de design.

---

## 2. Estado atual (AS-IS)

### 2.1 Fluxo de bootstrap

```
HTTP/CLI → config/app.php
  ├── Dotenv carrega .env → $_ENV
  ├── TenantResolver → define ACTIVE_CLIENT, BASE_URL (branding/domínio)
  ├── session_start() + SessionGuard
  └── (Database NÃO é inicializado aqui)

Runtime → Database::getInstance() (lazy, na primeira chamada)
  └── lê config/database.php → getenv('DB_*') → PDO único estático
```

| Aspecto | Comportamento atual |
|---------|---------------------|
| Singleton? | **Sim.** `Core\Database` mantém `private static ?PDO $instance` |
| Onde inicializa | Lazy, na **primeira** chamada a `getInstance()` |
| Credenciais | `config/database.php` → `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` via `getenv()` |
| Contexto de tenant | **Nenhum.** Uma única instância PDO para todo o processo |
| Resolução de tenant | Apenas para `ACTIVE_CLIENT` / `BASE_URL` (PHP defines), não para DB |

**Observação técnica:** `config/database.php` usa `getenv()`, enquanto o Dotenv popula principalmente `$_ENV`. Em ambientes onde `putenv` não espelha as variáveis, o fallback hardcoded (`erp_product`, `root`) pode mascarar misconfiguração. Isso deve ser unificado na implementação futura.

### 2.2 Resolução de tenant hoje (branding)

Arquivo `config/tenants.php`:

- `TENANT_RESOLUTION=env|domain`
- Mapa `hosts` → client key (`bmc`, `demo`)
- `known_clients`, `base_urls` por client
- Fallback: `ACTIVE_CLIENT` do `.env`

Isso alimenta `Shared\Support\Client` (rotas, assets, `ClientConfig` por pasta `app/Clients/{Bmc|Demo}`), mas **não seleciona banco de dados**.

---

## 3. Mapeamento de acoplamento ao banco

### 3.1 Chamadas **diretas** a `Database::getInstance()`

| Arquivo | Linha | Contexto |
|---------|-------|----------|
| `core/Database.php` | 13–24 | Definição do singleton |
| `core/Database/Repository.php` | 20 | Fallback no construtor base de **todos** os repositórios |
| `core/Model.php` | 13 | Construtor base (classe **não utilizada** por nenhum model ativo) |
| `core/Security/LoginRateLimiter.php` | 24 | Rate limit de login admin (`login_attempts`) |
| `app/Erp/Auth/Services/AuthService.php` | 43 | Query direta em `usuarios` (fora de repositório) |
| `app/Erp/Orders/Services/OrderService.php` | 54 | Lazy init PDO |
| `app/Erp/Finance/Services/PaymentChargeService.php` | 25 | Lazy init PDO |
| `app/Erp/Finance/Services/AsaasWebhookService.php` | 31 | Lazy init PDO |
| `app/Erp/Finance/Services/ReceivableService.php` | 21 | Lazy init PDO |
| `app/Erp/Stock/Services/StockReservationService.php` | 26 | Lazy init PDO |
| `app/Erp/Stock/Services/StockAvailabilityService.php` | 18 | Lazy init PDO |
| `app/Erp/Stock/Services/StockService.php` | 32 | Lazy init PDO |
| `app/Erp/Products/Repositories/AttributeFacetRepository.php` | 16 | Não estende `Repository`; fallback próprio |
| `migrate.php` | 16 | Migrations CLI (tenant implícito via `.env`) |
| `database/seeds/criar_admin.php` | 41 | Seed CLI |
| `database/seeds/seed_catalog_mvp.php` | 27 | Seed CLI |
| `database/seeds/seed_catalog_attributes.php` | 22 | Seed CLI |
| `tests/Feature/AuthServiceDatabaseTest.php` | 52, 67 | Integração MySQL opcional |

### 3.2 Acoplamento **indireto** via `Repository` (fallback `getInstance()`)

Todos estendem `Core\Database\Repository` e herdam o fallback na linha 20:

| Repositório | Tabela principal |
|-------------|------------------|
| `AuditLogRepository` | `audit_logs` |
| `UsuarioRepository` | `usuarios` |
| `UserRepository` | `usuarios` |
| `CommercialClientRepository` | `commercial_clients` |
| `CustomerRepository` | `customers` |
| `CustomerAddressRepository` | `customer_addresses` |
| `ProductRepository` | `produtos` |
| `CategoryRepository` | `categorias` |
| `BrandRepository` | `brands` |
| `StockAlertRepository` | `product_stock_alerts` |
| `StockMovementRepository` | `stock_movements` |
| `StockReservationRepository` | `stock_reservations` |
| `CartRepository` | `carts` |
| `CartItemRepository` | `cart_items` |
| `OrderRepository` | `orders` |
| `OrderItemRepository` | `order_items` |
| `PaymentChargeRepository` | `order_payment_charges` |

**Services sem PDO no construtor** que instanciam repositórios vazios (→ `getInstance()`):

`AuditService`, `ProductService`, `ProductCatalogService`, `CartService`, `CommercialClientService`, `CustomerAuthService`, `CustomerAccountService`, `FulfillmentService`, `OrderTrackingService`, `ReportService`, `StockHistoryService`, e controllers que fazem `new XxxService()` sem argumentos.

### 3.3 Controllers de produção que disparam cadeia singleton

| Controller | Services instanciados sem PDO |
|------------|----------------------------|
| `PublicCartController` | `CartService`, `OrderService`, `PaymentChargeService`, … |
| `AdminOrderController` | `OrderService`, `ReceivableService`, `PaymentChargeService`, … |
| `AsaasWebhookController` | `AsaasWebhookService` |
| `AdminReceivableController` | `ReceivableService` |
| `PublicCustomerAccountController` | `CustomerAuthService`, `CustomerAccountService` |

### 3.4 Helpers com acesso implícito ao banco

| Local | Mecanismo |
|-------|-----------|
| `commerce_cart_nav_count()` em `app/Helpers/functions.php:150` | `new CartService()` → repositórios → `getInstance()` |
| `AuthService::attemptLogin()` | PDO direto + `LoginRateLimiter` → tenant DB |

---

## 4. Padrão de injeção nos testes (Blocos 2–4)

Os testes unitários de checkout, Pix, reservas e webhooks **não usam** `Database::getInstance()`. Padrão estabelecido:

1. **`PDO` SQLite in-memory** criado no `setUp()`
2. **Repositórios** recebem `$this->pdo` explicitamente
3. **Services críticos** recebem `$this->pdo` + dependências injetadas
4. **`AuditService`** via `new AuditService(new AuditLogRepository($pdo))` ou `InMemorySchema::auditService($pdo)`
5. **Gateway Pix** mockado (`FakePaymentGateway`) em `PaymentChargeServiceTest`
6. **`$_ENV['ASAAS_WEBHOOK_TOKEN']`** setado manualmente em `AsaasWebhookServiceTest`

### 4.1 Matriz de injeção por teste (Blocos 2–4)

| Teste | PDO | AuditService | Outras injeções relevantes |
|-------|-----|--------------|----------------------------|
| `OrderServiceTest` | SQLite memória | `AuditLogRepository($pdo)` | `StockService`, repos, `FakeCepLookupService` |
| `PaymentChargeServiceTest` | `InMemorySchema::createPdo()` | — | `FakePaymentGateway`, repos |
| `AsaasWebhookServiceTest` | `InMemorySchema::createPdo()` | `InMemorySchema::auditService()` | `ReceivableService`, `OrderService` parcial |
| `ReceivableServiceTest` | SQLite memória | `InMemorySchema::auditService()` | `OrderRepository` |
| `StockReservationServiceTest` | SQLite memória | — | Cadeia completa stock repos + `StockAvailabilityService` |

### 4.2 Factory compartilhada de testes

`tests/Support/InMemorySchema.php` centraliza DDL e montagem de `OrderService` / `AuditService` — **referência direta** para uma futura `TenantConnectionFactory` ou `ApplicationContext` de produção.

### 4.3 Implicação para database-per-tenant

A estratégia de conexão por tenant **deve ser compatível** com:

```php
// Padrão já validado em produção de testes
new OrderService($pdo, new OrderRepository($pdo), auditService: $audit);
new PaymentChargeService($pdo, new PaymentChargeRepository($pdo), new OrderRepository($pdo), $gateway);
new AsaasWebhookService($pdo, /* repos */, $receivableService, $auditService, $orderService);
```

Ou seja: **`Database::getInstance()` deve passar a retornar o PDO do tenant corrente**, resolvido antes de qualquer service, preservando assinaturas existentes e testes atuais.

---

## 5. Estado em memória, sessão e cache (riscos multi-tenant)

### 5.1 Crítico — requer mudança

| Componente | Problema |
|------------|----------|
| `Core\Database::$instance` | Singleton **global**; em PHP-FPM, se o mesmo worker atender dois tenants sequencialmente com resolução errada, há risco de **vazamento de conexão/dados** entre tenants |
| `AsaasConfig` / `MailConfig` | Leem `env()` estático; com multi-tenant, `.env` único não escala |
| `config/tenants.php` + `Client::CLIENTS` | Mapa hardcoded; novos tenants exigem deploy de código hoje |

### 5.2 Provavelmente OK (isolado por domínio/cookie/request)

| Componente | Justificativa |
|------------|---------------|
| `$_SESSION` (admin + customer) | Cookie escopado ao host; cada tenant tem domínio próprio |
| Cookie `cart_token` (`CartService`) | `path=/`, escopo por domínio |
| CSRF token (`Core\Security\Csrf`) | Armazenado na sessão por domínio |
| Uploads `public/uploads/clients/{ACTIVE_CLIENT}/` | Path usa `ACTIVE_CLIENT` resolvido por host |
| `commerce_cart_nav_count()` static `$count` | Cache **por request**; OK se tenant resolvido antes |

### 5.3 Cache de schema por processo (baixo risco)

| Componente | Comportamento |
|------------|---------------|
| `ProductRepository`, `BrandRepository`, `CategoryRepository` | `static $exists/$has` — cache de existência de colunas/tabelas |
| `ProductService::loadUploadHelper()` | `static $loaded` — flag de include |
| `LoginRateLimiter::$loggedMissingTable` | Flag de log único por processo |

**Risco:** se o **mesmo worker** alternar entre databases de tenants com schemas ligeiramente diferentes (ex.: migration pendente em um tenant), o cache estático pode retornar resultado stale. Mitigação: keyed cache por `tenant_id` ou reset na troca de conexão.

### 5.4 Não implementado

- `CACHE_DRIVER=file` no `.env.example` — **sem uso ativo** no código de aplicação
- Logs em `storage/logs/php_error.log` — **compartilhados** entre tenants no mesmo deploy; considerar prefixo `tenant_slug` nos logs na implementação

---

## 6. Variáveis de ambiente — classificação

Fonte: `.env.example` + variáveis usadas em testes.

### 6.1 Permanece global (operador / infraestrutura)

| Variável | Motivo |
|----------|--------|
| `APP_ENV` | Ambiente do deploy (`local`, `production`) |
| `APP_DEBUG` | Política global de debug no servidor |
| `DB_HOST` | Host MySQL compartilhado |
| `DB_USER` / `DB_PASS` | Credencial do operador (acesso mestre + CREATE DATABASE) |
| `DB_NAME` | **Passa a ser o banco mestre** (ex.: `erp_master`) |
| `TENANT_RESOLUTION` | Estratégia global (`domain` em produção SaaS) |
| `LOG_CHANNEL` / `LOG_LEVEL` | Infra de logging do operador |
| `CSRF_TOKEN_LENGTH` | Default de produto (pode override por tenant no futuro) |
| `SESSION_TIMEOUT` | Default de produto |
| `ENABLE_AUDIT` / `ENABLE_SOFT_DELETES` / `ENABLE_ROLE_BASED_ACCESS` | Feature flags globais (override por tenant = fase 2) |
| `CACHE_DRIVER` | Infra futura |
| `ASAAS_USER_AGENT` | Identificação do produto na API Asaas |
| `ERP_RUN_INTEGRATION` | Flag de teste PHPUnit apenas |

### 6.2 Legacy global — substituído pelo mestre (mantido temporariamente)

| Variável | Situação |
|----------|----------|
| `ACTIVE_CLIENT` | Fallback CLI e modo `env`; migrar para `--tenant=` em scripts |
| `BASE_URL` | Fallback quando `domain` não deriva URL; mestre terá URL canônica por tenant |

### 6.3 Precisa ser por tenant (banco mestre)

| Variável | Motivo |
|----------|--------|
| `DB_NAME` (tenant) | Nome do database isolado (ex.: `erp_tenant_bmc`) |
| `ASAAS_ENABLED` | Cliente pode não usar Pix online |
| `ASAAS_SANDBOX` | Conta sandbox vs produção por cliente |
| `ASAAS_API_KEY` | Conta Asaas separada por lojista |
| `ASAAS_WEBHOOK_TOKEN` | Token único por webhook/conta Asaas |
| `ASAAS_PIX_DUE_DAYS` | Política comercial por tenant |
| `MAIL_DRIVER` / `MAIL_HOST` / `MAIL_PORT` | SMTP do lojista |
| `MAIL_USERNAME` / `MAIL_PASSWORD` / `MAIL_ENCRYPTION` | Credenciais SMTP |
| `MAIL_FROM` / `MAIL_FROM_NAME` | Remetente da loja |
| `MAIL_ENABLED` / `MAIL_ADMIN_TO` | Notificações operacionais por loja |

### 6.4 Permanece em código (`ClientConfig`) — recomendação

Branding rico (logo paths, WhatsApp, horários, páginas legais, prefixo de pedido) hoje vive em `app/Clients/{Tenant}/Config/ClientConfig.php`.

| Abordagem | Prós | Contras |
|-----------|------|---------|
| **A) Manter ClientConfig + registro no mestre** | Separação limpa: mestre = ops/DB/credenciais; código = UI/marca | Novo tenant ainda exige pasta `Clients/{X}` no deploy |
| **B) Centralizar branding no mestre (JSON)** | Onboarding 100% via CLI/DB | Mistura marca no Core path de dados; views/assets ainda precisam de pasta |
| **C) Híbrido** | Mestre guarda `display_name`, domínio, status; ClientConfig opcional para customização profunda | Duas fontes de verdade |

**Recomendação:** **C (híbrido)** para Fase 2 de implementação:

- Mestre: identidade operacional, domínios, DB, Asaas, mail, status
- `ClientConfig`: branding visual, assets, rotas customizadas, `orderReferencePrefix`
- Campo `client_config_key` no mestre liga ao slug da pasta `app/Clients/{key}`

---

## 7. Proposta de banco mestre

Nome sugerido: **`erp_master`** (database separado no mesmo servidor MySQL).

### 7.1 Diagrama ER (mínimo)

```mermaid
erDiagram
    tenants ||--o{ tenant_domains : has
    tenants ||--|| tenant_database : has
    tenants ||--o| tenant_asaas_config : has
    tenants ||--o| tenant_mail_config : has

    tenants {
        bigint id PK
        varchar slug UK "bmc, acme-store"
        varchar client_config_key "liga ClientConfig"
        enum status "active,suspended,provisioning,archived"
        varchar suspension_reason nullable
        datetime suspended_at nullable
        datetime created_at
        datetime updated_at
    }

    tenant_domains {
        bigint id PK
        bigint tenant_id FK
        varchar host UK "loja.exemplo.com.br"
        bool is_primary
        datetime created_at
    }

    tenant_database {
        bigint tenant_id PK_FK
        varchar database_name UK "erp_tenant_bmc"
        varchar charset "utf8mb4"
        datetime provisioned_at
        datetime last_migration_at nullable
    }

    tenant_asaas_config {
        bigint tenant_id PK_FK
        bool enabled
        bool sandbox
        varchar api_key_encrypted
        varchar webhook_token_hash
        tinyint pix_due_days
        datetime updated_at
    }

    tenant_mail_config {
        bigint tenant_id PK_FK
        bool enabled
        varchar driver "smtp"
        varchar host
        smallint port
        varchar username
        varchar password_encrypted
        varchar encryption "tls,ssl,"
        varchar from_address
        varchar from_name
        varchar admin_to nullable
        datetime updated_at
    }
```

### 7.2 Tabela `tenants`

| Coluna | Tipo | Notas |
|--------|------|-------|
| `id` | BIGINT UNSIGNED PK AI | |
| `slug` | VARCHAR(64) UNIQUE | Identificador estável (`bmc`) |
| `client_config_key` | VARCHAR(64) | Mapeia `Shared\Support\Client` / pasta `Clients/` |
| `display_name` | VARCHAR(255) | Nome operacional (painel operador) |
| `status` | ENUM | Ver §7.6 |
| `suspension_reason` | VARCHAR(255) NULL | Ex.: `billing_overdue`, `manual`, `abuse` |
| `suspended_at` | DATETIME NULL | |
| `notes` | TEXT NULL | Anotações internas do operador |
| `created_at` / `updated_at` | DATETIME | |

### 7.3 Tabela `tenant_domains`

| Coluna | Tipo | Notas |
|--------|------|-------|
| `id` | BIGINT UNSIGNED PK AI | |
| `tenant_id` | FK → tenants | |
| `host` | VARCHAR(255) UNIQUE | Normalizado lowercase, sem porta |
| `is_primary` | TINYINT(1) | URL canônica / e-mails |
| `created_at` | DATETIME | |

Substitui/evolui o mapa `hosts` de `config/tenants.php` em runtime.

### 7.4 Tabela `tenant_database`

| Coluna | Tipo | Notas |
|--------|------|-------|
| `tenant_id` | PK/FK | |
| `database_name` | VARCHAR(64) UNIQUE | Ex.: `erp_product` (BMC inicial) ou `erp_tenant_bmc` |
| `charset` | VARCHAR(32) | Default `utf8mb4` |
| `provisioned_at` | DATETIME | |
| `last_migration_at` | DATETIME NULL | Controle operacional |

**Credenciais:** reutilizar `DB_USER`/`DB_PASS` globais com GRANT por database (modelo operador). Colunas `db_username`/`db_password_encrypted` só se no futuro cada tenant tiver user MySQL dedicado.

### 7.5 Tabelas de integração

**`tenant_asaas_config`** — espelha `.env` Asaas por tenant.  
**`tenant_mail_config`** — espelha `.env` Mail por tenant.

Segredos: armazenar **criptografados at-rest** (chave `APP_KEY` global do operador) ou em vault externo; `webhook_token` como hash bcrypt/argon para comparação (`hash_equals`), mantendo plaintext apenas no onboarding para configurar Asaas.

### 7.6 Status do tenant

| Status | Comportamento HTTP | CLI |
|--------|-------------------|-----|
| `provisioning` | 503 "Loja em preparação" | Migrations/seeds permitidos |
| `active` | Normal | Normal |
| `suspended` | 503 ou página estática "Conta suspensa" | Somente leitura ou bloqueado |
| `archived` | 410 Gone | Backup/export apenas |

**Motivos de suspensão sugeridos:** `billing_overdue`, `contract_ended`, `manual_operator`, `security_incident`.

Suspensão **não dropa** o database — isolamento operacional apenas.

### 7.7 Seed inicial (BMC)

```text
tenants: slug=bmc, client_config_key=bmc, status=active
tenant_domains: bmc.local, (domínio produção)
tenant_database: database_name=erp_product  (nome atual, zero rename)
tenant_asaas_config: copiar valores atuais do .env
tenant_mail_config: copiar valores atuais do .env
```

---

## 8. Resolução de conexão — proposta de runtime

### 8.1 Sequência HTTP (modo `domain`)

```
1. config/app.php carrega .env global
2. Conectar ao erp_master (DatabaseMaster ou conexão separada)
3. Resolver host → tenant (tenant_domains JOIN tenants)
4. Validar status === active (senão 503)
5. Carregar tenant_database → abrir PDO tenant
6. Registrar PDO em TenantContext / Database::setTenantConnection($pdo)
7. Opcional: hidratar TenantConfig (Asaas/Mail) em objeto request-scoped
8. Definir ACTIVE_CLIENT a partir de tenant.client_config_key
9. Pipeline normal (Router → Controller → Service)
```

### 8.2 Webhook Asaas (`POST /webhooks/asaas`)

Desafio: rota **sem** domínio de loja necessariamente; token identifica a conta.

**Proposta:**

1. Ler header `asaas-access-token`
2. Lookup no mestre: `tenant_asaas_config.webhook_token_hash` → `tenant_id`
3. Abrir PDO do tenant
4. Delegar para `AsaasWebhookService` existente

Alternativa complementar: URL por tenant (`/webhooks/asaas/{slug}`) — mais explícito, porém exige N webhooks no painel Asaas.

### 8.3 CLI (`migrate.php`, seeds, futuro `tenant:create`)

```
php migrate.php run --tenant=bmc
php migrate.php run --all-tenants
```

- `--tenant` consulta mestre → `database_name` → executa migrations
- Sem flag: erro ou exige `ACTIVE_CLIENT` legacy durante transição

---

## 9. Estratégia de migração BMC (sem quebrar Blocos 1–4)

### Fase A — Preparação (zero downtime)

1. Criar `erp_master` + schema mestre
2. Registrar tenant `bmc` apontando para database **atual**
3. Manter `.env` legado funcionando (feature flag `MULTITENANT_DB=false`)

### Fase B — Conexão tenant-aware

1. Evoluir `Core\Database` para connection resolver (tenant-scoped)
2. Bootstrap HTTP resolve tenant **antes** de qualquer controller
3. `AsaasConfig` / `MailConfig` leem `TenantConfig` com fallback `.env` (compat)

### Fase C — Cutover

1. `TENANT_RESOLUTION=domain` em produção
2. Remover dependência de `DB_NAME` tenant no `.env`
3. Validar smoke: checkout, Pix, reserva estoque, webhook OVERDUE/REFUNDED

### Fase D — Novos tenants

1. CLI: criar registro mestre → `CREATE DATABASE` → `migrate` → seed admin
2. Configurar DNS → `tenant_domains`
3. Conta Asaas + webhook token no mestre

**Testes existentes:** permanecem inalterados (SQLite + injeção manual). Adicionar testes de integração opcionais para `TenantConnectionResolver`.

---

## 10. Tooling CLI proposto (Fase 2 — implementação)

| Comando | Função |
|---------|--------|
| `tenant:create {slug}` | Registro mestre + CREATE DATABASE + migrations |
| `tenant:suspend {slug} --reason=` | Status suspended |
| `tenant:activate {slug}` | Reativa |
| `tenant:list` | Lista tenants e status |
| `tenant:domain:add {slug} {host}` | Adiciona domínio |
| `migrate.php run --tenant={slug}` | Migrations por tenant |
| `migrate.php run --all-tenants` | Rolling migrate (operador) |

Onboarding **manual** conforme requisito — sem self-service.

---

## 11. Riscos e decisões em aberto

| # | Tema | Opções | Recomendação |
|---|------|--------|--------------|
| 1 | Singleton PDO em PHP-FPM | Reset por request vs pool keyed | **Reset explícito** no bootstrap; nunca reutilizar `$instance` entre tenants no mesmo request |
| 2 | Webhook Asaas | Token lookup vs URL com slug | **Token lookup no mestre** (1 URL para documentar) |
| 3 | Credenciais MySQL | User compartilhado vs user/tenant | **User compartilhado operador** na v1 |
| 4 | Criptografia de segredos | APP_KEY vs vault | **APP_KEY + sodium** na v1 |
| 5 | Demo tenant | DB separado ou remover | DB separado `erp_tenant_demo` para testes multi-tenant |
| 6 | `AuthService` query direta | Refatorar para `UsuarioRepository` | Fazer junto com tenant DB (consistência) |
| 7 | Logs compartilhados | Prefixo tenant | Adicionar `tenant_slug` em error_log structured |

---

## 12. Checklist de aprovação (para o coordenador)

- [ ] Nome do banco mestre: `erp_master`?
- [ ] BMC permanece em `erp_product` ou renomear para `erp_tenant_bmc`?
- [ ] Branding híbrido (mestre + ClientConfig) aprovado?
- [ ] Webhook: token lookup global vs URL por tenant?
- [ ] Suspensão exibe 503 genérico ou página customizada?
- [ ] User MySQL dedicado por tenant — v1 ou v2?
- [ ] Demo tenant recebe database isolado no cutover?

---

## 13. Referências no código

| Artefato | Caminho |
|----------|---------|
| Singleton Database | `core/Database.php` |
| Config DB | `config/database.php` |
| Bootstrap | `config/app.php` |
| Resolução domínio (branding) | `config/tenants.php`, `app/Shared/Support/TenantResolver.php` |
| Config Asaas | `app/Erp/Finance/Gateways/Asaas/AsaasConfig.php` |
| Config Mail | `app/Erp/Notifications/Config/MailConfig.php` |
| Webhook route | `config/routes/erp.php` → `/webhooks/asaas` |
| Test factory | `tests/Support/InMemorySchema.php` |
| Migrations tenant-scoped | `database/migrations/` (31 arquivos) |

---

*Documento gerado na Fase 1 (levantamento). Nenhum código de produção foi alterado.*
