# ARCHITECTURE — ERP + Site Multi-Cliente

> Mapa técnico oficial da arquitetura atual.  
> O sistema separa produto ERP reutilizável, implementação de cliente e infraestrutura compartilhada mantendo um único projeto PHP e um único front controller.

## 1. Visão Geral

A aplicação é um **monólito modular tenant-aware** em PHP 8.1+, sem framework full-stack.

Objetivo da arquitetura:

- manter operação simples em XAMPP/Apache/MySQL;
- preservar URLs e comportamento atuais;
- separar ERP Admin do Site Público;
- permitir novos clientes sem duplicar o núcleo do produto;
- remover marca e regras específicas do BMC do ERP.

## 2. Contextos Oficiais

| Contexto | Namespace | Responsabilidade |
|----------|-----------|------------------|
| Core | `Core\` | Framework técnico neutro |
| ERP | `Erp\` | Produto reutilizável, admin e regras de negócio |
| Clients | `Clients\` | Sites, branding, textos, assets e configurações por cliente |
| Shared | `Shared\` | Helpers, view resolver e utilitários neutros |
| Database | `Database\` | Migrations e seeds |

## 3. Fluxo HTTP

```text
HTTP Request
  -> public/index.php
  -> config/app.php
       -> Composer autoload
       -> .env
       -> constantes globais
       -> sessão
       -> SessionGuard
       -> configuração do cliente ativo
  -> Core\App::run()
       -> Core\Request::capture()
       -> Core\Router::resolve(method, uri)
       -> MiddlewarePipeline
            web: rotas públicas do cliente
            admin: AuthMiddleware -> AclMiddleware
       -> Controller do contexto correto
            Clients\Bmc\Site\Controllers\*
            Clients\Demo\Site\Controllers\*
            Erp\Admin\Controllers\*
       -> Service
       -> Repository
       -> Database/PDO
       -> Shared\View\ViewRenderer
       -> Core\Response::send()
```

## 4. Roteamento Modular

`config/routes.php` é o agregador.

Arquivos por contexto:

```text
config/routes/clients.php
config/routes/erp.php
app/Clients/Bmc/routes.php
app/Clients/Demo/routes.php
```

Regras:

- Rotas públicas são definidas pelo cliente ativo.
- Rotas administrativas são definidas pelo ERP.
- URLs atuais não mudam.
- Middleware `web` continua vazio para site público.
- Middleware `admin` continua com `AuthMiddleware` e `AclMiddleware`.
- Produtos públicos possuem listagem em `/produtos` e página individual em `/produtos/:slug`.

## 5. Renderização Com Múltiplas Raízes

O sistema de views resolve templates por contexto.

Raízes oficiais:

```text
erp     -> app/Erp/Admin/Views
client  -> app/Clients/{ActiveClient}/Site/Views
shared  -> app/Shared/View
```

Formato recomendado:

```php
$this->view('erp::products/index', $data, 'erp::layouts/admin');
$this->view('client::home', $data);
```

Todas as views renderizadas pela aplicação devem informar um contexto registrado. As views de erro HTTP ficam em `app/Shared/View/errors`.

## 6. ERP Admin

O ERP contém:

```text
app/Erp/Admin/Controllers/
app/Erp/Admin/Views/
app/Erp/Auth/
app/Erp/Dashboard/
app/Erp/Products/
app/Erp/Stock/
```

Responsabilidades:

- login/logout admin;
- dashboard;
- CRUD administrativo de produtos;
- movimentações de estoque;
- histórico de estoque;
- regras de negócio genéricas;
- persistência genérica em repositories.

Proibido dentro de `app/Erp`:

- textos comerciais BMC;
- telefone, endereço, WhatsApp ou e-mail BMC;
- logo ou imagens BMC;
- CSS/JS do site público;
- decisões específicas de layout do cliente.

## 7. Sites De Clientes

Cliente 1:

```text
app/Clients/Bmc/
├── Config/
├── Site/Controllers/
├── Site/Views/
└── routes.php
```

Cliente demonstrativo:

```text
app/Clients/Demo/
├── Config/
├── Site/Controllers/
├── Site/Views/
└── routes.php
```

Responsabilidades:

- home pública;
- catálogo público;
- página individual de produto;
- textos institucionais;
- links de WhatsApp/telefone;
- endereço;
- imagens e marca;
- assets públicos do cliente.

O site pode consultar dados do ERP por services/repositories públicos, mas o ERP não deve conhecer o cliente.

## 8. Assets

Separação oficial:

```text
public/assets/erp/admin/
public/assets/clients/bmc/site/
public/assets/clients/demo/site/
```

Regras:

- Admin usa somente assets do ERP.
- Site usa somente assets do cliente.
- Assets legados fora de `public/assets` foram removidos após validação funcional.

## 9. Uploads

Diretório alvo:

```text
public/uploads/clients/{client}/products/
```

O serviço de produto grava novos uploads no diretório segmentado por cliente ativo. O fallback legado para `public/img/produtos` foi removido.

## 10. Persistência

Banco atual: MySQL via `Core\Database`.

Tabelas atuais:

- `usuarios`
- `login_attempts`
- `produtos`
- `categorias`
- `stock_movements`
- `commercial_clients`
- `orders`
- `order_items`
- `audit_logs`

Repositories continuam responsáveis por SQL. Views não acessam banco.

## 11. Segurança

Mantido:

- `password_hash` e `password_verify`;
- sessão admin com chaves atuais;
- `SessionGuard`;
- rate limit de login;
- CSRF no login e POSTs sensíveis;
- ACL por padrão de URI;
- auditoria de ações administrativas críticas.

Chaves de sessão preservadas:

```text
admin_id
admin_nome
admin_role
last_activity
csrf
```

## 12. Autoload

Namespaces obrigatórios:

```json
{
  "Erp\\": "app/Erp/",
  "Clients\\": "app/Clients/",
  "Shared\\": "app/Shared/",
  "Core\\": "core/",
  "Database\\": "database/"
}
```

`App\` foi removido do autoload após validação funcional. Código novo deve usar os namespaces oficiais acima.

## 13. Critérios De Aceite Arquitetural

- `app/Erp` não contém hardcode BMC.
- `app/Clients/Bmc` concentra branding e site público BMC.
- `app/Clients/Demo` prova a troca de tenant por `ACTIVE_CLIENT`.
- `config/routes.php` apenas agrega rotas.
- Admin e site público possuem assets separados.
- Controller público de catálogo não é o controller admin de produtos.
- Renderização aceita múltiplas raízes.
- URLs atuais continuam válidas.
- `public/index.php` continua funcionando.

## 14. Estado Atual

Estado aplicado:

- documentação oficial consolidada;
- namespaces `Erp`, `Clients` e `Shared` ativos no Composer;
- controllers admin movidos para ERP;
- controllers públicos movidos para clientes;
- views admin movidas para ERP;
- views públicas movidas para clientes;
- assets admin e site separados;
- rotas divididas por contexto;
- acoplamentos diretos BMC removidos do núcleo ERP;
- namespace `App` removido do autoload;
- pontes antigas em `app/Controllers`, `app/Models` e `app/Views` removidas;
- módulos ERP de usuários, clientes comerciais, pedidos, relatórios e auditoria adicionados;
- Cliente 2 demonstrativo registrado no resolver multi-cliente;
- catálogo público evoluído com página individual de produto por slug;
- módulo Commerce com carrinho persistente (`carts`, `cart_items`) e checkout público;
- pedidos online integrados ao ERP com aprovação admin e baixa de estoque;
- admin de pedidos com detalhe em `/admin/pedidos/:id` e indicador de origem Site/Admin.

## Last Update

| Campo | Valor |
|-------|-------|
| Data | 2026-06-08 |
| Status | Fase C implementada (financeiro, Pix, multi-tenant, logística); documentação oficial consolidada |
| O que foi feito | Remoção de roadmaps obsoletos (`ERP_ECOMMERCE_ROADMAP.md`, `PRODUCT_VISION.md`); fontes AS-IS centralizadas em `PROJECT_CONTEXT.md` e `PHASE_C_BACKLOG.md` |
| Onde parou / O que falta | Smoke test pré-go-live; go-live BMC; evoluções pós-MVP conforme `PROJECT_CONTEXT.md` §10 |
