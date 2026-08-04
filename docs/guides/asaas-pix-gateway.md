# Gateway Pix — Asaas (Sandbox e Produção)

> Integração ERP genérica em `Erp\Finance\Gateways\Asaas`. Nenhuma referência a tenant/marca no Core.

## Variáveis `.env`

```env
ASAAS_ENABLED=true
ASAAS_SANDBOX=true
ASAAS_API_KEY=$aact_hmlg_...
ASAAS_WEBHOOK_TOKEN=seu-token-seguro-com-32-caracteres-ou-mais
ASAAS_PIX_DUE_DAYS=3
ASAAS_USER_AGENT=ErpCommerce/1.0
```

| Variável | Descrição |
|----------|-----------|
| `ASAAS_ENABLED` | Liga/desliga cobrança Pix no checkout |
| `ASAAS_SANDBOX` | `true` → `api-sandbox.asaas.com`; `false` → produção |
| `ASAAS_API_KEY` | Chave em Integrações > API (sandbox ou produção) |
| `ASAAS_WEBHOOK_TOKEN` | Token do webhook no painel Asaas (header `asaas-access-token`) |
| `ASAAS_PIX_DUE_DAYS` | Vencimento da cobrança Pix (1–30 dias) |

## Fluxo

1. Cliente conclui checkout → pedido `draft` + reserva de estoque.
2. Se `ASAAS_ENABLED=true`, o ERP cria cliente/cobrança Pix na API Asaas.
3. Página `/pedido-recebido` exibe QR Code e Pix copia e cola.
4. Asaas envia webhook `POST /webhooks/asaas` com evento `PAYMENT_RECEIVED`.
5. Admin aprova pedido: se Pix já foi pago, baixa automática; senão, fica `pending`.

## Webhook local (ngrok)

O Asaas precisa alcançar sua URL pela internet:

1. Suba o ngrok apontando para o vhost local, ex.: `ngrok http 80 --host-header=bmc.local`
2. No painel Asaas (sandbox): **Integrações > Webhooks**
   - URL: `https://SEU-ID.ngrok-free.app/webhooks/asaas`
   - Token: mesmo valor de `ASAAS_WEBHOOK_TOKEN`
   - Eventos: `PAYMENT_RECEIVED`, `PAYMENT_CONFIRMED`, `PAYMENT_OVERDUE`, `PAYMENT_REFUNDED`
3. Em produção use HTTPS no domínio real (`https://seudominio.com.br/webhooks/asaas`).

## Teste manual sandbox

1. `php migrate.php run` (tabela `order_payment_charges`).
2. Configure `.env` com chave sandbox.
3. Faça checkout com CPF/CNPJ válido.
4. Na página de confirmação, copie o Pix ou escaneie o QR.
5. No sandbox Asaas, simule pagamento ou use o app de testes.
6. Confirme webhook em **Integrações > Logs de webhook**.
7. Aprove o pedido no admin e verifique status de pagamento.

## Segurança

- Chave API e webhook token **somente** no `.env` (nunca no front-end).
- Webhook valida header `asaas-access-token` com `hash_equals`.
- Dados de cartão não passam pelo ERP (somente Pix nesta sprint).
- Produção exige HTTPS.

## Referências

- [Documentação Asaas — Cobranças](https://docs.asaas.com/reference/criar-nova-cobranca)
- [Webhooks Asaas](https://docs.asaas.com/docs/webhook-para-cobrancas)
