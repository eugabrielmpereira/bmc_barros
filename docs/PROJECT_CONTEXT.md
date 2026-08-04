# PROJECT_CONTEXT — ERP + Site Multi-Cliente

> Fonte oficial de contexto do produto ERP reutilizável com implementação de cliente.  
> Este documento registra a realidade arquitetural atual: `Erp` como produto, `Clients/Bmc` como Cliente 1, `Clients/Demo` como cliente demonstrativo e `Shared` como infraestrutura comum.

## 1. Identidade Do Produto

O sistema é um **produto ERP reutilizável multi-cliente**, implementado como monólito modular em PHP.

Separação conceitual obrigatória:

| Contexto | Papel |
|----------|-------|
| `Core` | Framework técnico neutro: HTTP, router, response, database, middleware e segurança base |
| `Erp` | Núcleo reutilizável do produto ERP: admin, autenticação, produtos, estoque, dashboard e regras comerciais genéricas |
| `Clients` | Implementações de clientes/tenants, começando por `Clients\Bmc` |
| `Shared` | Helpers, contratos e utilitários neutros compartilhados entre ERP e clientes |

O cliente **BMC Barros** é o **Cliente 1** e não define a identidade estrutural do produto.

## 2. Estado Real Antes Da Migração

Estado encontrado em 2026-05-16:

- Front controller único em `public/index.php`.
- Bootstrap em `config/app.php`.
- Rotas centralizadas em `config/routes.php`.
- Controllers em `app/Controllers`.
- Services e Repositories em `app/Services` e `app/Repositories`.
- Views públicas e administrativas misturadas em `app/Views`.
- Assets públicos em `public/css`, `public/js` e `public/img`.
- Assets admin em `public/assets/css/admin.css`, ainda com fallback para `public/css/admin.css`.
- Controller `ProdutosController` acumulando catálogo público e CRUD administrativo.
- Marca, textos, telefone, WhatsApp, endereço e imagens BMC hardcoded em views públicas e login/admin.
- Catálogo público migrado para `Erp\Products\Services\ProductCatalogService`.
- Uploads de produtos em `public/img/produtos`, sem segmentação por cliente.

## 3. Arquitetura Atual

Estrutura atual do produto:

```text
erp-produto/
├── app/
│   ├── Erp/
│   │   ├── Admin/
│   │   │   ├── Controllers/
│   │   │   └── Views/
│   │   ├── Auth/
│   │   ├── Dashboard/
│   │   ├── Products/
│   │   └── Stock/
│   ├── Clients/
│   │   ├── Bmc/
│   │   │   ├── Config/
│   │   │   ├── Site/
│   │   │   │   ├── Controllers/
│   │   │   │   └── Views/
│   │   │   └── routes.php
│   │   └── Demo/
│   └── Shared/
│       ├── Support/
│       └── View/
├── core/
├── config/
│   ├── routes.php
│   └── routes/
│       ├── erp.php
│       └── clients.php
├── public/
│   ├── assets/
│   │   ├── erp/
│   │   └── clients/
│   └── uploads/
│       └── clients/
└── docs/
```

Namespaces oficiais:

```text
Core\
Erp\
Clients\
Shared\
Database\
```

O namespace legado `App\` foi removido do autoload após validação funcional. Código novo deve usar `Erp`, `Clients` ou `Shared`.

## 4. Fluxos Preservados

URLs atuais devem continuar funcionando:

Rotas públicas dos clientes:

- `GET /`
- `GET /produtos`
- `GET /produtos/:slug`

Rotas administrativas do ERP:

- `GET /admin/login`
- `POST /admin/login`
- `GET /admin/dashboard`
- `GET /admin/logout`
- `GET /admin/estoque`
- `GET /admin/inventario-rapido`
- `POST /admin/inventario-rapido`
- `GET /admin/produtos`
- `GET /admin/produtos/criar`
- `POST /admin/produtos/criar`
- `GET /admin/produtos/editar/:id`
- `POST /admin/produtos/editar/:id`
- `POST /admin/produtos/deletar/:id`
- `POST /admin/produtos/:id/entrada`
- `POST /admin/produtos/:id/saida`
- `POST /admin/produtos/:id/ajuste`

## 5. Decisões Arquiteturais Preservadas

1. O sistema permanece monólito modular, não microserviços.
2. `public/index.php` permanece como front controller único.
3. `Core` continua neutro e não recebe regra de negócio, branding ou configuração de cliente.
4. ERP Admin e Site Público continuam no mesmo projeto, mas separados por contexto.
5. Admin e site público não compartilham layout, CSS ou JavaScript.
6. Rotas públicas pertencem ao cliente ativo.
7. Rotas administrativas pertencem ao ERP.
8. Views devem ser resolvidas por múltiplas raízes.
9. Produto, estoque, autenticação e dashboard são responsabilidades do ERP.
10. Textos, marca, contato, endereço, imagens e links comerciais pertencem ao cliente.
11. Uploads devem caminhar para segmentação por cliente.
12. Models legados foram removidos depois de validação funcional.

## 6. Roadmap Original Preservado

### Sprint 0 — Consolidação Documental

Status: executada com consolidação dos documentos oficiais e remoção de arquivos obsoletos.

### Sprint 1 — Configuração De Tenant

Status: executada com configuração do Cliente 1 em `Clients\Bmc\Config`.

### Sprint 2 — Separação De Assets

Status: executada com assets admin em `public/assets/erp/admin` e site em `public/assets/clients/bmc/site`.

### Sprint 3 — Separação De Fluxos Público/Admin

Status: executada com controllers públicos em `Clients\Bmc\Site\Controllers` e controllers admin em `Erp\Admin\Controllers`.

### Sprint 4 — Rotas E Views Por Contexto

Status: executada com `config/routes/erp.php`, `config/routes/clients.php` e views prefixadas por contexto.

### Sprint 5 — Uploads Por Tenant

Status: executada com diretório alvo `public/uploads/clients/{client}/products`. O fallback para `public/img/produtos` foi removido após validação.

### Sprint 6 — Produto Multi-Cliente

Status: executada estruturalmente com Cliente 1 em `Clients/Bmc` e Cliente 2 demonstrativo em `Clients/Demo`.

## 7. Módulos Do Produto

| Módulo | Contexto | Status |
|--------|----------|--------|
| Autenticação Admin | ERP | Implementado |
| Dashboard | ERP | Implementado |
| Produtos Admin | ERP | Implementado |
| Estoque | ERP | Implementado |
| Catálogo Público | Clientes | Implementado com listagem e página individual por slug |
| Carrinho e Checkout | ERP (`Erp\Commerce`) | Implementado com persistência em banco e pedido online multi-item |
| Conta do cliente final | ERP (`Erp\Customers`) | Implementado (login, cadastro, meus pedidos, endereços salvos) |
| Financeiro básico | ERP (`Erp\Finance`) | Implementado (status de pagamento, contas a receber, baixa manual) |
| Gateway Pix (Asaas) | ERP (`Erp\Finance\Gateways\Asaas`) | Implementado (cobrança, QR, webhook, baixa na aprovação) |
| Operação logística | ERP (`Erp\Fulfillment`) | Implementado (retirada/entrega, separação, expedição, status operacional) |
| Site Institucional | Cliente BMC | Implementado |
| Site Demonstrativo | Cliente Demo | Implementado |
| Multi-tenant por domínio | Shared (`TenantResolver`) | Implementado (`TENANT_RESOLUTION=domain`) |
| Usuários | ERP | Implementado |
| Clientes Comerciais | ERP | Implementado |
| Pedidos | ERP | Implementado (admin + detalhe + pedidos online) |
| Relatórios | ERP | Implementado |
| Auditoria | ERP | Implementado |
| Notificações por e-mail | ERP (`Erp\Notifications`) | Implementado (confirmação de pedido online + alerta admin) |

## 8. Riscos Técnicos Conhecidos

- Banco depende das migrations oficiais para tabelas novas e das tabelas de produto/categoria já existentes.
- URLs públicas usam `BASE_URL`; setups locais podem depender de `/bmc_barros/public`.
- Novos módulos administrativos exigem executar `php migrate.php run` antes da validação no navegador (inclui `stock_reservations`).
- Reserva soft cobre pedidos `draft`; ajuste manual de estoque abaixo da reserva pode impedir aprovação até regularização.

## 9. Critérios De Validação

Após mudanças funcionais, validar:

- `/`
- `/produtos`
- `/produtos/:slug`
- `/admin/login`
- `/admin/dashboard`
- `/admin/produtos`
- `/admin/estoque`
- `/admin/inventario-rapido`
- login/logout
- criação, edição e exclusão de produto
- movimentação de estoque
- renderização das views admin do ERP
- renderização das views públicas do Cliente BMC
- carregamento de CSS/JS admin
- carregamento de CSS/JS/site assets BMC
- ausência de referência hardcoded a BMC dentro de `app/Erp`
- `/admin/usuarios`
- `/admin/clientes`
- `/admin/pedidos`
- `/admin/pedidos/:id`
- `/carrinho`
- `/checkout`
- fluxo completo: adicionar ao carrinho → checkout → aprovar pedido no admin
- `/acompanhar-pedido` (referência + 4 dígitos telefone)
- `/entrar`, `/cadastro`, `/meus-pedidos`, `/minha-conta`, `/minha-conta/enderecos`
- `/admin/pedidos/:id` — editar itens/endereço em draft; reabrir cancelado (admin)
- `/admin/pedidos/:id` — forma de recebimento (draft) e status de expedição (faturado)
- `/admin/relatorios`
- `/admin/contas-a-receber`
- `/admin/auditoria`
- troca de `ACTIVE_CLIENT=bmc` para `ACTIVE_CLIENT=demo`
- com `TENANT_RESOLUTION=domain`: `bmc.local` vs `demo.local` (ver [`guides/deploy-multi-tenant.md`](guides/deploy-multi-tenant.md))

## 10. Próximos Passos

- Smoke test mínimo de pagamento antes do go-live (ver §11).
- Preparar go-live BMC conforme [`BMC_GOLIVE_CHECKLIST.md`](BMC_GOLIVE_CHECKLIST.md).
- Testes incrementais pós-MVP: estorno, vencimento e edge cases de pagamento.
- Evolução logística (Sprint 8+): troca/devolução, histórico ampliado, painel de expedição dedicado.

## 11. Decisões Do Coordenador

> Registro de escolhas operacionais e de priorização — retomar daqui na próxima sessão.

### 2026-06-07 — Pagamento Asaas e estratégia de go-live

| Decisão | Escolha |
|---------|---------|
| Sandbox Asaas | `.env` configurado; fluxo **checkout → Pix → pagamento → webhook** validado com sucesso |
| Cenários não testados | Estorno (`PAYMENT_REFUNDED`), vencimento e demais eventos de pagamento — **adiados de propósito** |
| Estratégia acordada | **1)** Finalizar o projeto → **2)** Testes incrementais (estorno, edge cases) → **3)** Go-live em produção |
| Smoke test pré-produção | Antes do go-live, validar apenas: Pix pago + webhook; aprovar pedido já pago; baixa manual; cancelamento com liberação de reserva |
| Estorno no curto prazo | Aceitável tratar manualmente (Asaas + ajuste no admin) até validar webhook de estorno depois |
| Sessão | Encerrada em 2026-06-07; retomar a partir da finalização do projeto + Sprint 7 |

## Last Update

| Campo | Valor |
|-------|-------|
| Data | 2026-06-14 |
| Status | Fase 4b concluída: `tenant:create` gera scaffold `app/Clients/{Slug}/` automaticamente; registro migrado para `config/clients.php`; produção ainda `MULTITENANT_DB=false` |
| O que foi feito | `ClientScaffoldGenerator`, `ClientRegistryWriter`, `ClientScaffoldNaming`, integração em `TenantCommandHandler` (--scaffold-client, --no-scaffold), páginas legais no scaffold, assets/uploads, `composer dump-autoload`, testes `ClientScaffoldTest`, fix SQL `markActive`/`setStatus`, tenant E2E `acme2` |
| Onde parou / O que falta | Customização visual/CNPJ/textos legais pós-scaffold; adicionar `acme2.local` em `config/tenants.php` para dev legado ou usar `MULTITENANT_DB=true`; cutover produção via checklist; checkout→Pix→webhook→mail end-to-end |
