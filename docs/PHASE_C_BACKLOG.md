# Backlog Fase C — Visão Completa (ERP + Commerce + Multi-Tenant)

> Registro das sprints da Fase C. Sprints 1–7 **concluídas** (2026-06-08).
> Estado oficial e próximos passos: [`PROJECT_CONTEXT.md`](PROJECT_CONTEXT.md).

## Ordem Recomendada De Sprints

| Prioridade | Bloco | Objetivo | Dependências |
|------------|-------|----------|--------------|
| 1 | Comunicação | E-mail de confirmação de pedido online (cliente + alerta admin) | MAIL_* no `.env`, pedido draft existente |
| 2 | Reserva de estoque | Reduzir overselling entre checkout e aprovação | Pedidos online estáveis |
| 3 | Conta do cliente final | Login público, endereços salvos, histórico de pedidos | Auth pública separada do admin |
| 4 | Pagamento manual | Status de pagamento + contas a receber + baixa manual | Pedidos aprovados |
| 5 | Gateway Pix | Integração inicial (ex.: webhook + QR estático) | Bloco pagamento manual |
| 6 | Multi-tenant por domínio | Resolver tenant por host/path sem editar `.env` | Infra Apache/vhost |
| 7 | Operação logística | Separação, expedição, retirada vs entrega | Pedidos com pagamento |
| 8 | Relatórios comerciais | Funil catálogo → carrinho → checkout → pedido | Eventos/auditoria ampliados |

## Sprint 1 — Comunicação (quick win)

Status: **implementada** (2026-06-05).

Entregáveis:

- Service de notificação desacoplado (`Erp\Notifications`).
- Template de e-mail para pedido `#N` recebido.
- Disparo após `createPublicDraft()` com feature flag no `.env` (`MAIL_ENABLED`).

Variáveis `.env`: `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM`, `MAIL_FROM_NAME`, `MAIL_ENCRYPTION`, `MAIL_ENABLED`, `MAIL_ADMIN_TO`.

## Sprint 2 — Reserva Soft De Estoque

Status: **implementada** (2026-06-05).

Entregáveis:

- Tabela `stock_reservations` por pedido `draft`.
- Validação de disponível = `estoque_atual - reservas_ativas` (`StockAvailabilityService`).
- Liberação automática ao cancelar pedido pendente; consumo ao aprovar.

## Sprint 3 — Conta Do Cliente Final

Status: **implementada** (2026-06-05).

Entregáveis:

- Tabela `customers` + `customer_addresses` (distintas de `commercial_clients`).
- Registro/login público com sessão `user_id` (`CustomerAuthService`, middleware `customer`).
- Área "Meus pedidos", perfil e endereços salvos no site (`/meus-pedidos`, `/minha-conta`).

## Sprint 4 — Financeiro Básico

Status: **implementada** (2026-06-05).

Entregáveis:

- Campos `payment_status`, `paid_at` em pedidos (migration `20260605160000_add_order_payment_fields`).
- `ReceivableService` com baixa manual (`markAsPaid`) e sincronização ao aprovar/cancelar/reabrir pedido.
- Tela admin `/admin/contas-a-receber` com listagem e baixa.
- Relatório de recebíveis em aberto em `/admin/relatorios`.
- Coluna e painel de pagamento em `/admin/pedidos` e detalhe do pedido.

## Sprint 5 — Multi-Tenant Por Domínio

Status: **implementada** (2026-06-07).

Entregáveis:

- Mapa host → client key em `config/tenants.php`.
- `TenantResolver` no bootstrap (`config/app.php`) antes das rotas — `TENANT_RESOLUTION=env|domain`.
- `BASE_URL` derivada da requisição ou `base_urls` por tenant em modo domain.
- Guia de deploy: [`guides/deploy-multi-tenant.md`](guides/deploy-multi-tenant.md).
- Testes em `TenantResolverTest`.

## Sprint 6 — Gateway Pix (Asaas)

Status: **implementada** (2026-06-07).

Entregáveis:

- Tabela `order_payment_charges` (migration `20260607120000_create_order_payment_charges`).
- Gateway genérico `PaymentGatewayInterface` + `AsaasPixGateway` + `AsaasHttpClient`.
- Cobrança Pix automática após checkout online (`PaymentChargeService`).
- QR Code e copia e cola em `/pedido-recebido` (partial compartilhado).
- Webhook `POST /webhooks/asaas` com validação `asaas-access-token`.
- Baixa automática na aprovação se Pix já recebido (`ReceivableService::applyPaymentStatusOnConfirm`).
- Painel admin exibe status da cobrança gateway em `/admin/pedidos/:id`.
- Guia: [`guides/asaas-pix-gateway.md`](guides/asaas-pix-gateway.md).
- Testes: `AsaasWebhookServiceTest`, `ReceivableServiceTest` (gateway).

Variáveis `.env`: `ASAAS_ENABLED`, `ASAAS_SANDBOX`, `ASAAS_API_KEY`, `ASAAS_WEBHOOK_TOKEN`, `ASAAS_PIX_DUE_DAYS`.

## Sprint 7 — Operação Logística

Status: **implementada** (2026-06-08).

Entregáveis:

- Campos `delivery_method`, `fulfillment_status`, `fulfillment_notes` em `orders` (migration `20260608120000_add_order_fulfillment_fields`).
- Módulo `Erp\Fulfillment` com `FulfillmentService` e rótulos de entrega/expedição.
- Checkout público: escolha **Entrega** vs **Retirada na loja** (endereço opcional em retirada).
- Admin `/admin/pedidos/:id`: painel de logística separado de pagamento e aprovação comercial.
- Coluna **Expedição** na listagem admin; status operacional em `/acompanhar-pedido` e **Meus pedidos**.
- Sincronização automática: cancelamento → `fulfillment_status=cancelled`; reabertura → `pending`.
- Testes: `FulfillmentServiceTest`, extensões em `OrderServiceTest` e `OrderTrackingServiceTest`.

## Critérios De Aceite Da Fase C

- Cliente final consegue acompanhar pedido sem contato manual.
- Admin distingue pagamento vs aprovação comercial vs expedição.
- Dois tenants acessíveis simultaneamente por domínios diferentes.
- Nenhuma regra comercial exclusiva dentro de `Clients/*` — tudo via ERP.
