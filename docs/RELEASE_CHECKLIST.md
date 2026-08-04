# Checklist De Entrega Local

## Ambiente

- Confirmar PHP 8.1+ no XAMPP.
- Confirmar Apache e MySQL ativos.
- Confirmar `.env` criado a partir de `.env.example`.
- Confirmar `BASE_URL`, `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` e `ACTIVE_CLIENT`.

## Banco

- Importar `database/schema/erp_product_full.sql` no phpMyAdmin (banco `erp_product`) **ou** executar `php migrate.php run` em banco vazio.
- Confirmar `.env` com `DB_NAME=erp_product`.
- Executar `php database/seeds/criar_admin.php "admin@exemplo.com" "SenhaForte123"`.
- Confirmar tabelas: `usuarios`, `login_attempts`, `produtos`, `categorias`, `stock_movements`, `stock_reservations`, `commercial_clients`, `orders`, `order_items`, `audit_logs`, `carts`, `cart_items`.

## Validação Admin

- Acessar `/admin/login`.
- Validar login/logout.
- Validar `/admin/dashboard`.
- Validar CRUD de produtos.
- Validar entrada, saída e ajuste de estoque.
- Validar `/admin/estoque`.
- Validar CRUD de usuários.
- Validar CRUD de clientes comerciais.
- Validar criação de pedido administrativo (1 produto).
- Validar `/admin/pedidos` com coluna de origem (Admin / Site).
- Validar `/admin/pedidos/:id` com itens, dados do cliente e ações de aprovar/cancelar.
- Validar aprovação de pedido online com baixa de estoque.
- Validar `/admin/relatorios`.
- Validar `/admin/auditoria`.

## Validação Pública (Commerce)

Com `ACTIVE_CLIENT=bmc`:

- Validar `/`, `/produtos` e `/produtos/:slug`.
- Adicionar produto ao carrinho via `/carrinho/adicionar`.
- Validar `/carrinho` (atualizar quantidade e remover item).
- Validar `/checkout` e envio de pedido com múltiplos itens.
- Com `MAIL_ENABLED=true` e credenciais Mailtrap no `.env`, confirmar 2 e-mails no sandbox: confirmação ao cliente e alerta em `MAIL_ADMIN_TO`.
- Criar pedido online que reserve estoque; tentar segundo checkout acima do disponível e confirmar bloqueio; cancelar primeiro pedido e validar liberação.
- Confirmar pedido `#N` em `/admin/pedidos` com origem **Site** e status **Pendente**.
- Aprovar pedido no admin e confirmar baixa de estoque.

Com `ACTIVE_CLIENT=demo`:

- Validar `/`, `/produtos`, `/carrinho` e `/checkout`.
- Confirmar carregamento de CSS em `/assets/clients/demo/site/css/style.css`.

## Segurança E Arquivos

- Confirmar que `.env` não deve ser publicado.
- Confirmar que logs de runtime não devem ser versionados.
- Confirmar permissões de `public/uploads/clients`.
- Confirmar que `APP_DEBUG=false` será usado em produção.

## Testes

```bash
composer dump-autoload
vendor/bin/phpunit -c phpunit.xml.dist
```

Esperado: suite unitária verde (integração MySQL opcional com `ERP_RUN_INTEGRATION=1`).
