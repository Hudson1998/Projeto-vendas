# CLAUDE.md

Orientações para trabalhar neste repositório. O **README.md** cobre instalação,
configuração e execução — não duplique aquilo aqui. Este arquivo trata de
arquitetura, convenções e das armadilhas que já custaram tempo.

## O que é o projeto

Marketplace de moda em **Laravel 11 + MySQL 8**, com três perfis: cliente
(vitrine, carrinho, pedidos), lojista (`/loja`) e administrador (`/admin`).
Front-end em HTML/CSS/JS puro servido de `public/` — **não há Vite, Node ou
build step** para a loja. A única exceção é a tela de gráficos do admin, um
app Angular em `admin-charts/` cujo bundle já vem compilado e versionado em
`public/admin-charts/`.

## Como rodar

Veja o README. Em resumo: `docker compose up -d --build`, depois
`docker compose exec app sh docker/seed-dev.sh`, e a loja responde em
`http://localhost:8000`.

Se `php` e `docker` não existirem no ambiente, o Codespace nasceu com a imagem
padrão em vez do `.devcontainer` do projeto — use a skill `rodar-local`
(`.claude/skills/rodar-local/`), que instala PHP + MariaDB e sobe o
`artisan serve`.

**Rode `artisan` sempre como `www-data`** dentro do container:
`docker compose exec -u www-data app php artisan ...`. Ver a armadilha do 500
mais abaixo.

Não existe suíte de testes (`tests/` e `phpunit.xml` não estão no repositório),
apesar do PHPUnit constar no `composer.json`. Verifique mudanças exercitando a
loja de verdade — ver "Como verificar uma mudança".

## Arquitetura

### As três camadas de PHP, e o que cada uma é de fato

| Pasta | O que é | Observação |
|---|---|---|
| `app/Models/` | Models Eloquent reais | `Product`, `Order`, `ProductVariant`, `User`, `Favorite`, `CartItem`, `Setting`, `PageVisit`, `SearchLog`… |
| `app/Classes/` | **Mistura** | A maioria são classes de domínio simples (DTOs com construtor promovido: `Produto`, `Pessoa`, `Login`, `Blusa`, `Tenis`…), modelando a hierarquia de tipos de peça. Mas **`Loja`, `Transportadora`, `Motorista` e `ADM` são Models Eloquent de verdade**, com tabela. Confira antes de assumir. |
| `app/Pages/` | Orquestradores por página | `PaginaInicial`, `PaginaCompra`, `PaginaLojaDashboard`… Concentram as consultas e as regras. Implementam os contratos de `app/Interfaces/`. |

**Controllers são finos de propósito.** O padrão é instanciar a página e
delegar:

```php
public function index(): View
{
    $paginaInicial = new PaginaInicial;

    return view('home', [
        'produtos' => $paginaInicial->vitrine(),
        ...
    ]);
}
```

Lógica nova de consulta ou de regra de negócio vai na classe de `app/Pages/`,
não no controller. `App\Http\Controllers\Controller` é uma classe abstrata
vazia — não há nada herdado ali.

### Outros pontos

- `app/Support/` — helpers de aplicação. `ImagemDePerfil` centraliza upload,
  troca e remoção de foto de perfil e logotipo (mesmas regras da foto de
  produto: `jpg|jpeg|png|webp`, até 5 MB).
- `app/Rules/` — validações brasileiras: `Cpf`, `Cnpj`, `InscricaoEstadual`.
- `app/Http/Middleware/` — `EnsureUserIsAdmin` (alias `admin`),
  `EnsureUserIsLojista` (alias `lojista`), `LogPageVisit` (alias `log.visit`).
  Os aliases são registrados em `bootstrap/app.php`.
- `app/helpers.php` — carregado via `autoload.files` do Composer.

## Convenções

### Idioma

O domínio é escrito em **português**: nomes de rota (`/produtos`, `/carrinho`,
`/cadastro`), colunas (`nome`, `preco`, `preco_promocional`, `imagem`,
`endereco`), variáveis, métodos e classes de domínio. A infraestrutura do
Laravel mantém o inglês (`Product`, `Order`, `User`, `fillable`, `casts`).
Siga o que já existe no arquivo que você está editando.

**Comentários em português, mas sem acentos** (`nao`, `e`, `sao`,
`configuracao`). Strings de dados e texto de interface levam acento normal.

### Comentários explicam o *porquê*, não o *quê*

O código deste repositório comenta decisão e armadilha, não mecânica. Exemplo
real, de `bootstrap/app.php`:

```php
// Confia no cabecalho X-Forwarded-Proto do proxy que termina o TLS.
// Sem isso, atras de um proxy https (Codespaces, load balancer) o
// Laravel enxerga a requisicao como http e asset()/url() devolvem
// links http dentro de uma pagina https -- o navegador bloqueia como
// conteudo misto e a loja abre sem imagens e sem o JS do admin.
$middleware->trustProxies(at: '*');
```

Mantenha essa densidade: quando resolver um bug não óbvio, deixe registrado o
que quebrava sem aquilo. Não comente o que o código já diz.

### Formatação

```bash
./vendor/bin/pint
```

Rode antes de commitar. É a única ferramenta de qualidade configurada.

## Front-end

### Layouts Blade — são quatro, escolha pelo contexto

| Layout | Usado por | Quando |
|---|---|---|
| `layouts.app` | home, produto, carrinho, pedidos, favoritos, perfil, vitrine da loja | telas públicas e de cliente (header completo da loja) |
| `layouts.admin` | tudo em `/admin` | painel administrativo |
| `layouts.loja` | tudo em `/loja` | painel do lojista |
| `layouts.base` | login, cadastro, cadastro de lojista | header mínimo, só "voltar à loja" |

### `asset_v()` em vez de `asset()` para CSS e JS

`app/helpers.php` define `asset_v()`, que anexa `?v=<filemtime>` à URL. Sem
isso o navegador serve CSS/JS antigos do cache depois de uma alteração. Use
`asset_v()` para arquivos de `public/css` e `public/js`; `asset()` continua
correto para imagens.

### Navegação AJAX — o detalhe que quebra JS de página

`public/js/ajax-nav.js` substitui o conteúdo de `#ajax-content` sem recarregar
a página. Isso significa que **`DOMContentLoaded` não dispara em navegação
interna**. Script de página tem de se registrar:

```js
registerPageInit(function () {
  // roda agora e a cada navegacao AJAX
});
```

É o padrão já usado em `app.js`, `flash.js`, `loja-dashboard.js` e
`lojista-cadastro.js`. Ignorar isso produz o bug clássico "funciona ao dar F5,
não funciona ao clicar no menu".

### A vitrine renderiza no cliente

A home injeta o catálogo como JSON e `public/js/app.js` faz filtro por
categoria e busca sem ida ao servidor. **Não procure `<a href>` de produto no
HTML** para saber se há catálogo — procure `"nome":"` (com a massa completa,
~530 ocorrências).

## Banco e dados de teste

- 34 migrations em `database/migrations/`. Nomes datados no padrão
  `AAAA_MM_DD_NNNNNN_descricao.php`.
- `php artisan db:seed` roda apenas `ProductSeeder`, `ProductTaxonomySeeder` e
  `AdminSeeder`. Os seeders de massa exigem `--class=` e têm **ordem de
  dependência** — `InteracoesTesteSeeder` precisa de `TestDataSeeder` e
  `CatalogoTesteSeeder`. A ordem correta está em `docker/seed-dev.sh`; use o
  script em vez de reconstruí-la à mão.
- Os seeders de massa **não são idempotentes**. Para recomeçar:
  `php artisan migrate:fresh` (ou `docker compose down -v`).
- Massa esperada após o seed completo: ~500 produtos, ~4.900 variantes,
  ~155 usuários, ~354 pedidos, ~51 lojas.
- Admin semeado: `admin@hrmoda.com.br` / `AdminHR@2026`. Demais contas de teste
  usam `Teste@1234`. A tabela completa está no README.

Estoque vive em `product_variants`, não em `products` — as colunas
`estoque`/`tamanhos`/`cores` foram removidas de `products` por migration.

Não existe tabela de transações: o histórico de pagamento são as colunas
`forma_pagamento`, `status_pagamento`, `codigo_pagamento` e `verificado_banco`
em `orders`; a entrega são `status_separacao`, `entrega_propria`,
`transportadora_id` e `motorista_id` na mesma tabela.

## Uploads

Fotos de produto, de perfil e logotipos vão para **`public/uploads`**, e o
banco guarda o caminho relativo `uploads/nome.ext`, que `asset()` resolve
direto na view. Use `App\Support\ImagemDePerfil` para perfil e logotipo — ela
já apaga a imagem anterior ao substituir (sem isso cada troca deixa um órfão).

Documentos de KYC do lojista são diferentes: vão para o disco `local`
(`storage/app/private/lojistas-kyc/{user_id}/`), fora do alcance público.

Limites: 20 MB no PHP (`docker/php/uploads.ini`), 5 MB na validação da
aplicação.

## Armadilhas conhecidas

**HTTP 500 depois de rodar `artisan` como root.** As views compiladas em
`storage/framework/views` ficam com dono root e o Apache (`www-data`) não
consegue sobrescrevê-las. Por isso todo comando leva `-u www-data`. Para sair
do buraco: `php artisan view:clear` e
`chown -R www-data:www-data storage bootstrap/cache`. O `entrypoint.sh` limpa
as views a cada boot exatamente por causa disso.

**`db is unhealthy` no primeiro boot.** A inicialização do datadir do MySQL
passa de 2 minutos em máquina lenta. O healthcheck já tem 30 retries e
`start_period` de 30s; se ainda assim falhar, rode `docker compose up -d` de
novo — na segunda vez o datadir já existe.

**MariaDB local recusando 127.0.0.1.** O pacote do Alpine vem com
`skip-networking` ligado: sobe só no socket unix e o Laravel quebra com
`SQLSTATE[HY000] [2002]`. A skill `rodar-local` já corrige isso.

**302 não é bug.** `/carrinho`, `/favoritos`, `/admin` e `/loja` redirecionam
para o login quando não há sessão. Isso é o comportamento correto.

**URLs que não existem.** O cadastro é `/cadastro`, não `/register`. Não há
`/produtos` sem id — só `/produtos/{id}`. Um 404 nessas duas é erro de chute.

**`php artisan route:list --columns`** não é aceito na versão de PHP do
ambiente Alpine.

## Como verificar uma mudança

Um servidor que responde na porta não prova que a loja funciona:

```bash
curl -s -o /dev/null -w "%{http_code}\n" http://127.0.0.1:8000/           # 200
curl -s -o /dev/null -w "%{http_code}\n" http://127.0.0.1:8000/produtos/1 # 200
curl -s -o /dev/null -w "%{http_code}\n" http://127.0.0.1:8000/admin      # 302
```

Para um fluxo autenticado de verdade: pegue o `_token` de `/login`, poste com
cookie jar e então chame `/admin/stats`, que devolve JSON com os números reais.

## Gráficos do admin (Angular)

`admin-charts/` é um app Angular 22 + Chart.js que consome
`/admin/graficos/{acessos,receita,volume-compras,satisfacao,vendas-categoria}`.
O bundle compilado está **versionado** em `public/admin-charts/browser/`, então
rodar o sistema não exige Node.

Ao alterar o fonte, recompile e **commite o build junto**:

```bash
cd admin-charts && npm install && npm run build
```

O `angular.json` já aponta `outputPath` para `../public/admin-charts` e
`baseHref` para `/admin-charts/`. Exige Node 22.22.3+, 24.15+ ou 26+.

## Git

Commits em **português**, no imperativo, descrevendo o efeito para o usuário —
siga o histórico (`Corrige upload de foto quebrado na tela de Configurações`,
`Busca da vitrine so dispara quando o usuario confirma`). O trabalho vai direto
na `main`; o Codespace roda `docker/auto-pull.sh`, que traz cada push
automaticamente e limpa as views compiladas.

Não commite `.env` (ignorado), `vendor/`, `node_modules/` nem
`.claude/settings.local.json`.
