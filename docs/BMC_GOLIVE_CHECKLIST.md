# Checklist Go-Live — BMC Barros

> Executar após validação local completa ([`RELEASE_CHECKLIST.md`](RELEASE_CHECKLIST.md)) e aceite do MVP de e-commerce.

## 1. Ambiente De Produção

- [ ] Servidor com PHP 8.1+, Apache e MySQL.
- [ ] Virtual host apontando document root para `public/`.
- [ ] `.env` de produção criado (nunca versionado).
- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] `BASE_URL` com domínio real e HTTPS.
- [ ] `ACTIVE_CLIENT=bmc`
- [ ] Credenciais MySQL dedicadas (não usar `root` sem senha).

## 2. Banco E Migrations

- [ ] Banco criado no servidor.
- [ ] `php migrate.php run` executado em produção.
- [ ] Admin inicial criado via seed (`criar_admin.php`).
- [ ] Backup automático configurado (diário recomendado).

## 3. Conteúdo E Branding

- [ ] Produtos e categorias cadastrados no admin.
- [ ] Imagens em `public/uploads/clients/bmc/products/`.
- [ ] [`ClientConfig.php`](../app/Clients/Bmc/Config/ClientConfig.php) revisado: telefone, WhatsApp, endereço, horários.
- [ ] Logo e assets em `public/assets/clients/bmc/site/`.
- [ ] Textos institucionais revisados nas views BMC.

## 4. Fluxo Comercial (Smoke Test Produção)

- [ ] Home `/` carrega corretamente.
- [ ] Catálogo `/produtos` e PDP `/produtos/:slug`.
- [ ] Carrinho persistente entre sessões.
- [ ] Checkout cria pedido `#N` com origem **Site**.
- [ ] Admin aprova pedido e estoque baixa corretamente.
- [ ] Cancelamento de pedido faturado estorna estoque.

## 5. Segurança

- [ ] HTTPS ativo (certificado válido).
- [ ] `.env` fora do web root e bloqueado por Apache.
- [ ] Permissões de `public/uploads/clients/` (escrita apenas onde necessário).
- [ ] `SESSION_TIMEOUT` adequado para admin.
- [ ] Senha forte do admin ERP.
- [ ] Logs de runtime não expostos publicamente.

## 6. Operacional

- [ ] Equipe treinada em `/admin/pedidos/:id` (aprovar/cancelar).
- [ ] Processo definido: pedido online → contato cliente → aprovação.
- [ ] Contato WhatsApp/telefone visível no site alinhado ao atendimento real.

## 7. Pós Go-Live (Primeira Semana)

- [ ] Monitorar pedidos pendentes diariamente.
- [ ] Verificar erros PHP/Apache nos logs.
- [ ] Coletar feedback de clientes sobre checkout.
- [ ] Planejar próximo sprint conforme [`PHASE_C_BACKLOG.md`](PHASE_C_BACKLOG.md).

## Rollback

- Manter backup do banco antes do go-live.
- Manter cópia do `.env` e do vhost anteriores.
- Procedimento: restaurar backup + reverter vhost se necessário.
