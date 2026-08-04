# Deploy Multi-Tenant — Guia Para Iniciantes (XAMPP)

> Dois “sites” no mesmo projeto: **BMC** (loja real) e **Demo** (vitrine de teste).

---

## Antes de tudo: o que é cada coisa?

Pense no projeto como **um prédio com duas fachadas**:

| Conceito | O que é | Analogia |
|----------|---------|----------|
| **Tenant / Cliente** | Qual loja o visitante vê (BMC ou Demo) | Qual placa na porta |
| **ERP / Admin** | Painel `/admin` — produtos, pedidos, estoque | Escritório nos fundos (igual para todos) |
| **ACTIVE_CLIENT** | Variável no `.env` que escolhe a loja | Interruptor manual |
| **Domínio / Host** | Endereço no navegador (`localhost`, `bmc.local`) | Endereço da fachada |
| **Virtual Host (vhost)** | Apache diz: “este endereço abre esta pasta” | Placa na rua → portão certo |

**O Demo não está vazio.** Ele tem home, catálogo, carrinho, checkout e conta do cliente — só com visual simples (proposital). Os **produtos vêm do mesmo banco** que o BMC; o que muda é texto, cores e pasta de assets.

---

## Caminho 1 — Teste fácil (recomendado para começar)

**Sem domínio, sem Apache avançado.** Só troca uma linha no `.env`.

### Passo 1 — XAMPP ligado

1. Abra o **XAMPP Control Panel**
2. Clique **Start** em **Apache** e **MySQL** (os dois ficam verdes)

### Passo 2 — Editar o `.env`

Abra o arquivo `.env` na raiz do projeto (`c:\xampp\htdocs\bmc_barros\.env`).

Adicione (ou altere) estas linhas:

```env
ACTIVE_CLIENT=bmc
TENANT_RESOLUTION=env
```

Salve o arquivo.

### Passo 3 — Ver o site BMC

No navegador:

```
http://localhost/bmc_barros/public/
```

Você deve ver o site **BMC Barros** (layout completo, cores da marca).

Admin (sempre o mesmo):

```
http://localhost/bmc_barros/public/admin/login
```

### Passo 4 — Ver o site Demo

No `.env`, mude **só esta linha**:

```env
ACTIVE_CLIENT=demo
```

Salve. **Atualize a página** no navegador (F5).

Mesma URL:

```
http://localhost/bmc_barros/public/
```

Agora você deve ver:

- Título **“Cliente Demonstração”**
- Texto *“Cliente ativo: demo”*
- Página simples (fundo cinza claro, card branco)
- Link **“Ver catálogo”** → `/produtos` com os mesmos produtos do ERP

| URL | O que testar no Demo |
|-----|----------------------|
| `/` | Home demonstrativa |
| `/produtos` | Catálogo (mesmos produtos) |
| `/carrinho` | Carrinho |
| `/admin/login` | Admin (não muda com o tenant) |

### Passo 5 — Voltar ao BMC

No `.env`:

```env
ACTIVE_CLIENT=bmc
```

Salve e atualize o navegador.

**Pronto.** Você validou multi-tenant no modo simples.

---

## Caminho 2 — Dois sites ao mesmo tempo (domínios)

**Objetivo:** abrir BMC e Demo **sem** ficar trocando o `.env`.

- `http://bmc.local/` → BMC  
- `http://demo.local/` → Demo  

Requer 3 configurações no Windows. Faça **uma de cada vez**.

### Passo A — `.env` em modo domínio

```env
TENANT_RESOLUTION=domain
ACTIVE_CLIENT=bmc
BASE_URL=http://localhost/bmc_barros/public
```

`ACTIVE_CLIENT` vira reserva se o domínio não for reconhecido.

### Passo B — Arquivo `hosts` do Windows

O Windows precisa saber que `bmc.local` e `demo.local` são o seu PC.

1. Abra o **Bloco de Notas** como **Administrador**  
   (botão direito no Bloco de Notas → “Executar como administrador”)
2. Arquivo → Abrir → cole este caminho:

```
C:\Windows\System32\drivers\etc\hosts
```

3. No final do arquivo, adicione:

```text
127.0.0.1 bmc.local
127.0.0.1 demo.local
```

4. Salve.

**Teste:** no Prompt de Comando:

```text
ping bmc.local
```

Deve responder `127.0.0.1`. Se sim, o passo B funcionou.

### Passo C — Virtual hosts no Apache

1. Abra no editor:

```
C:\xampp\apache\conf\extra\httpd-vhosts.conf
```

2. No **final** do arquivo, cole o conteúdo de:

```
config/apache-vhosts.local.example.conf
```

(está na raiz do projeto `bmc_barros`)

3. Salve.

4. No XAMPP Control Panel: **Stop** Apache → **Start** Apache.

### Passo D — Testar no navegador

| URL | Resultado esperado |
|-----|-------------------|
| `http://bmc.local/` | Site BMC (layout completo) |
| `http://demo.local/` | Site Demo (página simples) |
| `http://bmc.local/admin/login` | Login admin |
| `http://demo.local/admin/login` | Mesmo login admin |

Se `bmc.local` não abrir:

- Apache está verde no XAMPP?
- Colou o vhost certo (caminho `C:/xampp/htdocs/bmc_barros/public`)?
- Reiniciou o Apache depois de editar?

---

## O que é normal no Demo

- Visual **minimalista** — não é bug; é vitrine de teste.
- **Mesmos produtos** do BMC — vêm do ERP/banco.
- **Admin igual** — produtos cadastrados no admin aparecem nos dois sites.
- Pedidos do Demo usam prefixo `DEMO` (config em `ClientConfig`).

---

## Resumo: qual caminho usar?

| Situação | Use |
|----------|-----|
| “Quero só ver se o Demo funciona” | **Caminho 1** — troca `ACTIVE_CLIENT` |
| “Quero BMC e Demo abertos juntos” | **Caminho 2** — domínios + vhosts |
| Produção na internet | Caminho 2 + domínios reais (`www.bmcbarros.com.br`, etc.) |

---

## Modos de resolução (referência técnica)

| `TENANT_RESOLUTION` | Comportamento |
|---------------------|---------------|
| `env` (padrão) | Usa `ACTIVE_CLIENT` do `.env` |
| `domain` | Resolve pelo endereço do navegador; mapa em `config/tenants.php` |

Mapa de hosts (`config/tenants.php`):

```php
'hosts' => [
    'localhost' => 'bmc',
    'bmc.local' => 'bmc',
    'demo.local' => 'demo',
],
```

## Produção (futuro)

1. Um deploy, um banco.
2. DNS de cada domínio no servidor.
3. `TENANT_RESOLUTION=domain` no `.env` de produção.

## Referência

- [modules/clientes-tenant.md](modules/clientes-tenant.md)
- [ARCHITECTURE.md](../../ARCHITECTURE.md) §7–9
- [PHASE_C_BACKLOG.md](../PHASE_C_BACKLOG.md) — Sprint 5
