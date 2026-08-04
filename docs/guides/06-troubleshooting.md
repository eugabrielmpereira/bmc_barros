# 06 — Troubleshooting: erros comuns

## Como usar este guia

1. Identifique o **sintoma** (403, 404, 500, redirect, SQL).
2. Siga o **diagnóstico** na ordem.
3. Consulte o módulo específico se necessário.

---

## 403 — Acesso negado (logado)

**Mensagem:** "Você não tem permissão para acessar este recurso."

**Causa mais comum:** rota existe, ACL não libera para sua `admin_role`.

| Passo | Ação |
|-------|------|
| 1 | Confirme que está logado (`admin_id` na sessão) |
| 2 | Abra `config/routes/erp.php` — rota registrada? |
| 3 | Abra `config/acl.php` — padrão para sua role? (use `*` para subpaths) |
| 4 | Verifique `usuarios.role` no banco vs `$_SESSION['admin_role']` |

**Correção:** adicione padrão em `config/acl.php`, ex.: `'/admin/categorias*'`.

Guia completo: [modules/usuarios-acl.md](modules/usuarios-acl.md).

---

## Redirect para `/admin/login`

**Causa:** `AuthMiddleware` — sem sessão admin.

| Passo | Ação |
|-------|------|
| 1 | Faça login em `/admin/login` |
| 2 | Verifique `SESSION_TIMEOUT` — sessão expirou? |
| 3 | Cookie de sessão bloqueado? (navegador, HTTPS mismatch) |

---

## 404 — Página não encontrada

| Passo | Ação |
|-------|------|
| 1 | URL correta inclui `/public`? (`BASE_URL` no `.env`) |
| 2 | Rota em `config/routes/erp.php` ou `Clients/.../routes.php`? |
| 3 | Método HTTP correto (GET vs POST)? |
| 4 | `ACTIVE_CLIENT` correto para rotas públicas? |

---

## 500 — Erro interno

| Passo | Ação |
|-------|------|
| 1 | Leia `storage/logs/php_error.log` |
| 2 | Com `APP_DEBUG=true`, tela pode mostrar stack trace |
| 3 | Causas frequentes: classe não encontrada, SQL, view inexistente |

---

## SQLSTATE — tabela não existe

**Mensagem:** `Base table or view not found`

| Passo | Ação |
|-------|------|
| 1 | Importe `database/schema/erp_product_full.sql` **ou** |
| 2 | Execute `php migrate.php run` |
| 3 | Confirme `DB_NAME` no `.env` |

---

## Erro em migration

| Passo | Ação |
|-------|------|
| 1 | Leia mensagem completa no terminal |
| 2 | Verifique se migration já foi aplicada (`migrations` table) |
| 3 | Não edite migration já aplicada em produção — crie nova migration |

---

## CSS/JS não carrega

| Passo | Ação |
|-------|------|
| 1 | `BASE_URL` aponta para pasta `public`? |
| 2 | Admin usa `public/assets/erp/admin/` |
| 3 | Site usa `public/assets/clients/{client}/site/` |
| 4 | `ACTIVE_CLIENT` corresponde ao cliente dos assets? |

---

## Imagens de produto quebradas

| Passo | Ação |
|-------|------|
| 1 | Upload em `public/uploads/clients/{client}/products/` |
| 2 | `ClientConfig` uploads path correto? |
| 3 | Permissão de escrita na pasta |

---

## Catálogo vazio / sem produtos

| Passo | Ação |
|-------|------|
| 1 | Produtos com `status = ativo`? |
| 2 | Categorias com `status = active`? |
| 3 | Rodou `php database/seeds/seed_catalog_mvp.php`? |
| 4 | Migration de categorias hierárquicas aplicada? |

---

## Carrinho / checkout falha

| Passo | Ação |
|-------|------|
| 1 | Tabelas `carts`, `cart_items` existem? |
| 2 | CSRF token no formulário? |
| 3 | Estoque suficiente ao adicionar? |
| 4 | Log em `storage/logs/php_error.log` |

---

## Pedido online não aparece no admin

| Passo | Ação |
|-------|------|
| 1 | Checkout retornou sucesso com número? |
| 2 | Liste `/admin/pedidos` — status `draft` |
| 3 | Verifique tabela `orders` no banco |

---

## Troca ACTIVE_CLIENT não muda site

| Passo | Ação |
|-------|------|
| 1 | Reinicie não necessário — só salvar `.env` |
| 2 | Valor exato: `bmc` ou `demo` (minúsculo) |
| 3 | Cliente registrado em `Shared\Support\Client`? |
| 4 | Limpe cache do navegador |

---

## PHPUnit falha

```bash
vendor/bin/phpunit -c phpunit.xml.dist
```

| Passo | Ação |
|-------|------|
| 1 | `composer install` |
| 2 | Testes de banco usam SQLite em memória — não precisa MySQL |
| 3 | Rode teste isolado: `vendor/bin/phpunit tests/Unit/MiddlewareTest.php` |

---

## Mapa rápido sintoma → arquivo

| Sintoma | Primeiro arquivo |
|---------|------------------|
| 403 | `config/acl.php` |
| Login loop | `AuthService`, sessão, `BASE_URL` |
| 404 rota admin | `config/routes/erp.php` |
| 404 rota site | `app/Clients/Bmc/routes.php` |
| SQL table missing | migrations / schema SQL |
| Estoque errado | `StockService` |
| Permissão operador | `config/acl.php` role `operator` |

---

## Exercício de diagnóstico

Cenário: você clica "Inventário Rápido" e recebe 403 estando logado como admin.

Liste os 3 arquivos que você abriria e o que procuraria em cada um.

<details>
<summary>Resposta</summary>

1. `config/routes/erp.php` — rota `/admin/inventario-rapido` existe?
2. `config/acl.php` — role `admin` tem `'/admin/inventario-rapido*'`?
3. `usuarios` no banco — seu usuário tem `role = admin`?

</details>

## Referência oficial

- [05-receita-nova-feature.md](05-receita-nova-feature.md)
- [RELEASE_CHECKLIST.md](../RELEASE_CHECKLIST.md)
