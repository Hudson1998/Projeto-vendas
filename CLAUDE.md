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

**Há dois registros por trás disso, e a diferença importa.** Um `<script>` de
`public/js/` carrega uma vez e vale para sempre; um `<script>` inline dentro de
`#ajax-content` (ou empilhado em `@push('scripts')`) é **re-executado a cada
troca de página**. O `ajax-nav.js` separa os dois por
`document.currentScript` — os de página são descartados na troca seguinte, os
globais ficam. Sem essa separação a lista crescia sem limite: cada visita ao
`/carrinho` deixava mais uma cópia do inicializador dele, que seguia rodando e
religando listeners em todas as páginas seguintes.

Corolário: **estado que vive fora do `#ajax-content` não é limpo pela troca.**
`body.modal-open` (o `overflow: hidden` do modal de confirmação) é o caso real
— o `#confirm-modal` some com a troca, mas a classe no `<body>` ficava, e a
página de destino abria sem rolagem. O `swapContent` remove a classe de
propósito; qualquer trava nova no `<body>` precisa do mesmo cuidado.

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

## Compra, pagamento e frete

### Os três passos deixam um JSON cada

A compra acontece em três passos, e cada um grava o seu próprio arquivo em
`storage/app/private/historico-clientes/{user_id}/pedidos/{order_id}/`. Os dois
primeiros rodam no `CartController::checkout`; o terceiro só fecha quando o
cliente paga, na tela de pagamento:

| Arquivo | Passo | Quem grava | Onde |
|---|---|---|---|
| `1-selecao.json` | peças escolhidas, variante e preço congelados | `PaginaCompra::registrarSelecao` | checkout |
| `2-pagamento.json` | cobrança emitida, frete, total, IP e localização | `PaginaPagamento::cobrar` | checkout |
| `3-conferencia.json` | resposta do banco sobre aquela cobrança | `PaginaPagamento::verificarComBanco` | tela de pagamento |

Quem escreve é `App\Support\RegistroDeCompra`; `passosRegistrados()` lê os três
de volta. A pasta é por cliente porque o destino desses dados é o documento de
histórico dele — montá-lo será ler a pasta, sem consultar o banco.

**Nada disso pode derrubar a compra.** Quando os passos rodam, o pedido já
existe, o estoque já baixou e o carrinho já esvaziou: falha de escrita vira
`Log::warning`, nunca exceção. Foi justamente o contrário que quebrava o
"Confirmar pedido" — ver a armadilha do disco `local` mais abaixo.

### Frete: piso de R$ 12,00, +R$ 5,00 por km acima de 6 km, teto de R$ 60,00

`PaginaCompra::calcularFrete` — R$ 12,00 é piso em qualquer distância; passando
de 6 km, cada km excedente custa R$ 5,00 e km quebrado conta inteiro; e R$
60,00 é teto, o que faz a conta parar de crescer a partir de 16 km. Sem o teto
uma rota interestadual chegava a milhares de reais. As quatro constantes
(`FRETE_MINIMO`, `FRETE_KM_INCLUSOS`, `FRETE_POR_KM_EXCEDENTE`,
`FRETE_MAXIMO`) ficam no topo da classe.

A tela do carrinho **não mostra a regra nem a distância** — só o valor final.

A distância **não vem do formulário** — o comprador escolheria o próprio frete.
`App\Support\RotaDeEntrega` calcula a rota entre o ponto de despacho da loja
(`Loja::enderecoDespacho()`, dos campos `envio_*`) e o endereço do perfil do
cliente. Sem geocodificador: reconhece "Cidade - UF" nas duas pontas, busca
numa tabela local de coordenadas e aplica haversine × 1,3. Cidade não listada
cai na capital da UF; mesma cidade vale 6 km (o piso); nada reconhecido também
cai no piso. Carrinho com várias lojas paga pelo trecho mais longo.

### A tela de pagamento, uma por forma

O checkout **não conclui o pagamento** — ele emite a cobrança e redireciona
para `/pedidos/{order}/pagamento` (`PagamentoController`), que desenha o
instrumento da forma escolhida:

| Forma | O que a tela mostra | Como termina |
|---|---|---|
| Pix | QR Code + copia-e-cola | "Já fiz o pagamento" → `aguardando_analise` |
| Boleto | código de barras + linha digitável + vencimento | "Já fiz o pagamento" → `aguardando_analise` |
| Cartão | formulário (número, titular, validade, CVV, parcelas) | autoriza na hora → `aprovado` |

Pix e boleto ganham um bloco de **espera**: anel que drena com um cronômetro de
3 minutos (`GatewayDePagamentoSimulado::MINUTOS_VALIDADE`) e, abaixo dele, o
botão "Já fiz o pagamento". O botão **consulta de verdade**: o gateway simulado
só reconhece o crédito depois de `SEGUNDOS_PARA_COMPENSAR` (30s) — antes disso
responde que não identificou e a tela continua aguardando. Zerado o cronômetro,
a cobrança expira e a tela oferece gerar outra; quem decide isso é o servidor
(`expirada()`), não o relógio do navegador.

Quem sai pelo "Pagar depois" reencontra a cobrança pelo botão **Pagar agora**
no acompanhamento, que só aparece com `status_pagamento === 'pendente'`.
Pedido já resolvido não reabre o formulário: `show()` redireciona para o
acompanhamento, senão um F5 depois de pagar pediria pagamento de novo.

O instrumento **não tem coluna no banco** — é grande e já está gravado em
`2-pagamento.json`. `PaginaPagamento::cobrancaEmitida()` lê de lá e, se o
arquivo faltar ou vier sem o payload, reemite a cobrança.

### Os códigos são gerados de verdade, sem biblioteca

Não há pacote de QR nem de código de barras no `composer.json`, e não vai
haver: um código desenhado "de enfeite" não passa em leitor nenhum.

- `App\Support\QrCode` — encoder QR completo (modo byte, correção M, versões
  1–20), saída em SVG. Validado contra o decodificador jsQR nas 20 versões.
- `App\Support\CodigoDeBarras` — Intercalado 2 de 5, a simbologia do boleto.
  Confere módulo a módulo com o JsBarcode.

**A armadilha que custou tempo:** a segunda cópia da informação de formato do
QR usa a **coluna 8 para os bits baixos e a linha 8 para os altos**. Trocar
linha por coluna gera um QR de aparência perfeita e ilegível em qualquer
leitor — o decodificador lê o nível de correção e a máscara errados e desiste
antes de chegar nos dados.

Os dois encoders sobrevivem à saída do gateway simulado: pix de verdade também
precisa virar imagem.

### O gateway é simulado, e sai inteiro

`App\Support\GatewayDePagamentoSimulado` existe porque não há adquirente nem
API de banco. Todo campo de mentira vem marcado com `simulado: true`, e o
cabeçalho do arquivo tem o passo a passo do descarte — só `PaginaPagamento` o
referencia.

**O formato, porém, é real** — é o que os encoders acima desenham. O payload
pix segue o EMV/BR Code com CRC16 correto; o boleto tem os 44 dígitos com DV
módulo 11 e a linha digitável de 47 com os DVs módulo 10; o cartão passa por
Luhn. Mentira são a chave pix, o banco emissor e a autorização.

Cartão de teste que **sempre recusa**: `4000000000000002`
(`GatewayDePagamentoSimulado::CARTAO_RECUSADO`) — sem ele a tela de erro do
cartão nunca seria exercitada. Recusa não mexe no status: o pedido continua
pendente e o cliente tenta de novo na mesma tela.

## Armadilhas conhecidas

**HTTP 500 depois de rodar `artisan` como root.** As views compiladas em
`storage/framework/views` ficam com dono root e o Apache (`www-data`) não
consegue sobrescrevê-las. Por isso todo comando leva `-u www-data`. Para sair
do buraco: `php artisan view:clear` e
`chown -R www-data:www-data storage bootstrap/cache`. O `entrypoint.sh` limpa
as views a cada boot exatamente por causa disso.

**Disco `local` inacessível ao Apache.** Um `artisan` rodado como root cria
`storage/app/private` com dono root e modo 0700; depois disso o `www-data` não
consegue nem abrir a raiz do disco, e o Flysystem estoura
`UnableToCreateDirectory` — o `throw => false` do `config/filesystems.php` não
pega esse caso, porque a falha é na construção do adapter, antes de qualquer
`put()`. É o mesmo buraco do 500 acima, com outra cara. Saída:
`chown -R www-data:www-data storage`. O diretório agora é versionado (com
`.gitignore` dentro) e o `entrypoint.sh` o recria antes do `chown`.

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
