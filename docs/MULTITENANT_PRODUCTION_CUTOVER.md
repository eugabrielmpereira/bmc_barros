# Checklist de Ativação Multi-Tenant em Produção

**Status:** Documento operacional — **não executar automaticamente**.  
**Pré-requisito:** Fases 2 e 3 validadas em dev/staging com `MULTITENANT_DB=true`.

---

## 1. Pré-validação em staging

- [ ] `php migrate_master.php run` aplicado no servidor de staging
- [ ] `php migrate_master.php seed` executado (ou registro manual equivalente)
- [ ] Cada tenant de produção possui registro em `tenants`, `tenant_domains`, `tenant_database`
- [ ] `tenant_asaas_config.webhook_token_hash` preenchido para cada loja com Pix ativo
- [ ] DNS apontando `loja.exemplo.com.br` → servidor correto
- [ ] Vhosts/hosts locais testados: `bmc.local` → BMC, `demo.local` → Demo
- [ ] Suite de integração passando:
  ```bash
  ERP_RUN_INTEGRATION=1 MULTITENANT_DB=true vendor/bin/phpunit tests/Feature/
  ```

---

## 2. Variáveis de ambiente (servidor)

Manter globais:

```env
MULTITENANT_DB=true
MASTER_DB_NAME=erp_master
TENANT_RESOLUTION=domain
DB_HOST=...
DB_USER=...
DB_PASS=...
APP_ENV=production
APP_DEBUG=false
```

Remover ou ignorar em runtime (legado):

- `DB_NAME` — usado apenas para fallback CLI / modo legado; BMC continua registrado como `erp_product` no mestre
- `ACTIVE_CLIENT` — substituído pela resolução de domínio (fallback dev apenas)

Por tenant (registrados no mestre, **não** no `.env` global):

- Database name, domínios, Asaas API key, webhook token, SMTP

---

## 3. Ordem de cutover (janela de manutenção recomendada)

1. **Backup** completo de `erp_product` e `erp_master`
2. Aplicar migrations mestre pendentes: `php migrate_master.php run`
3. Confirmar seed/registro BMC apontando para `erp_product` (zero rename)
4. Configurar `tenant_domains` com domínios de produção reais
5. Configurar webhook Asaas de cada loja apontando para:
   ```
   https://{dominio-da-loja}/webhooks/asaas
   ```
   (URL única por produto; roteamento por `asaas-access-token`)
6. Alterar `.env` de produção: `MULTITENANT_DB=true`, `TENANT_RESOLUTION=domain`
7. Recarregar PHP-FPM / Apache
8. Smoke test por tenant (ver §4)
9. Monitorar logs por 24–48h

---

## 4. Smoke test pós-cutover (por tenant)

| # | Verificação | Esperado |
|---|-------------|----------|
| 1 | Acessar home pelo domínio do tenant | Branding correto (`ClientConfig`) |
| 2 | Catálogo / carrinho | Dados do database do tenant |
| 3 | Checkout + Pix | Cobrança no Asaas da conta do tenant |
| 4 | Webhook simulado (sandbox) | Pedido correto marcado como pago |
| 5 | Admin login | Usuários do database do tenant |
| 6 | Tenant Demo (se ativo) | Isolado de BMC (produtos/pedidos distintos) |

---

## 5. Rollback de emergência

Se falha crítica após cutover:

1. Reverter `.env`:
   ```env
   MULTITENANT_DB=false
   TENANT_RESOLUTION=env
   ACTIVE_CLIENT=bmc
   DB_NAME=erp_product
   ```
2. Recarregar PHP-FPM / Apache
3. Sistema volta ao modo banco único legado (BMC em `erp_product`)
4. Investigar causa antes de nova tentativa

**Nota:** Dados escritos em `erp_tenant_demo` ou outros tenants durante cutover permanecem nos databases isolados — rollback não os mergeia automaticamente.

---

## 6. Critérios de go/no-go

| Go | No-go |
|----|-------|
| Todos smoke tests OK | Qualquer vazamento cross-tenant detectado |
| Webhook roteia por token corretamente | Host desconhecido retorna dados de outro tenant |
| Suspensão retorna 503 | Login admin falha para BMC |
| Logs sem erro de conexão mestre | Migration mestre não aplicada |

---

## 7. Pós-cutover (Fases 4–5)

- Fase 4: CLI `tenant:create` para novos clientes
- Fase 5: `AsaasConfig`/`MailConfig` ler do mestre; criptografia de segredos
- Remover dependência de `config/tenants.php` `hosts` quando todos os domínios estiverem no mestre

---

*Última revisão: 2026-06-14 — Fase 3 implementada, cutover produção pendente validação manual.*
