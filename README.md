# ERP Produto — Multi-Cliente

Produto ERP reutilizável em PHP, com **admin ERP** e **site público por cliente** no mesmo monólito modular.

O cliente BMC Barros é a implementação inicial (`Clients/Bmc`) e não define mais o núcleo do produto.

## Stack

- PHP 8.1+
- MySQL
- Apache (XAMPP)
- Composer (PSR-4, Dotenv)
- PHPUnit 10

Framework próprio em `core/` (Router, Middleware, Request/Response), sem Laravel/Symfony.

## Arquitetura

| Contexto | Local | Responsabilidade |
|----------|-------|------------------|
| Core | `core/` | Infraestrutura HTTP, router, response, database e middleware |
| ERP | `app/Erp/` | Produto reutilizável, admin, produtos, estoque, usuários, clientes, pedidos, relatórios e auditoria |
| Clients | `app/Clients/` | Sites públicos, branding e configurações por cliente |
| Shared | `app/Shared/` | Helpers e utilitários neutros |

## Aprenda o produto

Manual pedagógico para entender como o sistema funciona (rastreio de requisições, módulos, ACL, banco):

**[docs/guides/README.md](docs/guides/README.md)** — comece por aqui se você é dev jr ou quer dominar o produto antes de evoluí-lo.

## Documentação

| Documento | Conteúdo |
|-----------|----------|
| **[docs/PROJECT_MASTER.md](docs/PROJECT_MASTER.md)** | **Brief consolidado** — visão completa do produto para planejamento (IA, onboarding) |
| **[docs/guides/README.md](docs/guides/README.md)** | Manual de aprendizado: pipeline HTTP, contextos, módulos e troubleshooting |
| **[docs/PROJECT_CONTEXT.md](docs/PROJECT_CONTEXT.md)** | Fonte oficial do contexto atual, estado implementado, módulos e próximos passos |
| **[ARCHITECTURE.md](ARCHITECTURE.md)** | Fonte oficial da arquitetura técnica atual |
| **[docs/PHASE_C_BACKLOG.md](docs/PHASE_C_BACKLOG.md)** | Registro da Fase C (Sprints 1–7 concluídas) e backlog evolutivo |
| **[docs/BMC_GOLIVE_CHECKLIST.md](docs/BMC_GOLIVE_CHECKLIST.md)** | Checklist de go-live BMC Barros |
| **[docs/RELEASE_CHECKLIST.md](docs/RELEASE_CHECKLIST.md)** | Checklist de validação local |

## Como rodar localmente

### Pré-requisitos

- XAMPP (Apache + MySQL + PHP 8.1+)
- [Composer](https://getcomposer.org/)

### Passos

1. Clone ou copie o projeto para o htdocs (ex.: `C:\xampp\htdocs\bmc_barros`).

2. Instale dependências:
   ```bash
   composer install
   ```

3. Configure o ambiente:
   ```bash
   copy .env.example .env
   ```
   Ajuste `BASE_URL`, `DB_*` e demais variáveis no `.env`.

4. Crie o banco e todas as tabelas — importe no phpMyAdmin:
   `database/schema/erp_product_full.sql`
   (banco `erp_product`). Alternativa: `php migrate.php run` em banco vazio já criado.

5. Crie o usuário admin:
   ```bash
   php database/seeds/criar_admin.php "seu@email.com" "SuaSenhaForte123"
   ```
   Insira ao menos uma categoria (descomente o seed no SQL ou cadastre pelo admin) antes de criar produtos.

6. Acesse no navegador a URL definida em `BASE_URL` (ex.: `http://localhost/bmc_barros/public`).

7. Admin: `/admin/login` — use o e-mail e senha do seed.

## Módulos Admin

- Dashboard: KPIs macro, tendência de faturamento, estoque crítico e movimentações recentes.
- Produtos: CRUD, imagens, categorias, status e movimentações de estoque.
- Estoque: histórico de entradas, saídas, ajustes e inventário rápido em lote.
- Usuários: CRUD administrativo, status e papéis.
- Clientes comerciais: cadastro operacional de clientes finais/empresas.
- Pedidos: criação administrativa, pedidos online do site, detalhe com itens, aprovação com baixa de estoque e cancelamento.
- Relatórios: resumo operacional de produtos, clientes, pedidos e estoque.
- Auditoria: consulta de eventos administrativos críticos.

## Criar Novo Cliente

1. Crie uma pasta em `app/Clients/NovoCliente`.
2. Adicione `Config/ClientConfig.php`, `Site/Controllers`, `Site/Views` e `routes.php`.
3. Registre o cliente no mapa interno de `Shared\Support\Client`.
4. Adicione assets em `public/assets/clients/novo-cliente/site`.
5. Configure `ACTIVE_CLIENT` no `.env`.

Cliente demonstrativo já disponível:

```dotenv
ACTIVE_CLIENT=demo
```

Depois volte para:

```dotenv
ACTIVE_CLIENT=bmc
```

### Testes (opcional)

```bash
vendor/bin/phpunit -c phpunit.xml.dist
```

## Publicar no GitHub

1. Crie um repositório **vazio** no GitHub (sem README inicial, para evitar conflito).
2. Na pasta do projeto:

```bash
git init
git add .
git status
git commit -m "Initial commit: ERP produto modular (Core + BMC tenant)"
git branch -M main
git remote add origin https://github.com/SEU_USUARIO/SEU_REPOSITORIO.git
git push -u origin main
```

Antes do `git add`, confira que `.env` **não** aparece no `git status` (só `.env.example`).

---

Operações detalhadas, estrutura de pastas e checklist de funcionalidades: **[docs/PROJECT_CONTEXT.md](docs/PROJECT_CONTEXT.md)**.
