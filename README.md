# 👗 HR Moda Online

Marketplace de moda em Laravel 11: vitrine pública com catálogo, carrinho e
checkout, painel do lojista (produtos, pedidos, logística) e painel
administrativo com gráficos.

---

## 📑 Índice

- [Sobre o projeto](#-sobre-o-projeto)
- [Tecnologias](#-tecnologias)
- [Estrutura de pastas](#-estrutura-de-pastas)
- [Pré-requisitos](#-pré-requisitos)
- [Instalação com Docker (recomendado)](#-instalação-com-docker-recomendado)
- [Instalação sem Docker](#️-instalação-sem-docker)
- [Configuração do `.env`](#-configuração-do-env)
- [Banco de dados: migrations e seeders](#-banco-de-dados-migrations-e-seeders)
- [Contas de acesso](#-contas-de-acesso)
- [Mapa de telas](#-mapa-de-telas)
- [Painel de gráficos (Angular)](#-painel-de-gráficos-angular)
- [Comandos úteis](#-comandos-úteis)
- [Solução de problemas](#-solução-de-problemas)
- [GitHub Codespaces](#-github-codespaces)
- [Licença](#-licença)

---

## 📋 Sobre o projeto

O sistema tem três perfis de usuário, cada um com o seu próprio conjunto de
telas:

| Perfil | O que faz | Onde entra |
|---|---|---|
| **Visitante / cliente** | Navega a vitrine, filtra por categoria, busca, favorita, monta o carrinho, finaliza a compra, acompanha e avalia pedidos | `/` |
| **Lojista** | Cadastra a loja (com KYC), gerencia produtos e variantes, acompanha pedidos, separa/embala/envia, cadastra transportadoras e motoristas | `/loja` |
| **Administrador** | Vê o dashboard geral, gráficos de acesso/receita/satisfação, clientes, faturamento e o catálogo completo | `/admin` |

A vitrine renderiza os produtos no cliente: a página injeta o catálogo como
JSON e `public/js/app.js` faz o filtro por categoria e a busca sem recarregar.
Não há build step de front-end para a loja — o CSS e o JS em `public/` são
servidos direto.

---

## 🚀 Tecnologias

- **PHP 8.2+** e **Laravel 11** (11.55) — rotas, controllers, Blade
- **MySQL 8.0** (ou MariaDB 11.4, compatível) — todo o estado da aplicação
- **HTML/CSS/JS puro** em `public/` — vitrine, admin e dashboards, sem Vite/Node
- **Angular 22 + Chart.js** em `admin-charts/` — apenas a tela de gráficos do
  admin; o bundle já vem compilado e versionado em `public/admin-charts/`
- **Docker + Docker Compose** — ambiente completo (app + banco)

Sessões, cache e filas usam o driver `database`, ou seja: **não é preciso
Redis nem qualquer serviço extra.**

---

## 📁 Estrutura de pastas

```
├── app/
│   ├── Classes/           # classes de domínio (Produto, Loja, Roupa, Calçado…)
│   ├── Http/Controllers/  # Home, Product, Cart, Order, Admin, LojaDashboard, Auth…
│   ├── Http/Middleware/   # EnsureUserIsAdmin, EnsureUserIsLojista, LogPageVisit
│   ├── Interfaces/        # contratos das páginas (Compra, Pagamento, Análise…)
│   ├── Models/            # Eloquent: Product, ProductVariant, Order, Favorite…
│   ├── Pages/             # objetos de página (PaginaInicial, PaginaCompra…)
│   ├── Rules/             # validações brasileiras: Cpf, Cnpj, InscricaoEstadual
│   └── Support/           # ImagemDePerfil, NumberAbbreviator
├── admin-charts/          # projeto Angular dos gráficos (código-fonte)
├── bootstrap/app.php      # rotas, middleware aliases, trust proxies
├── config/                # configuração do Laravel
├── database/
│   ├── migrations/        # 34 migrations: users, products, orders, lojas…
│   └── seeders/           # catálogo, taxonomia, admin e massa de teste
├── docker/                # entrypoint, seed-dev, auto-pull, configs Apache/PHP
├── public/
│   ├── admin-charts/      # bundle Angular já compilado (versionado)
│   ├── assets/            # imagens do catálogo
│   ├── css/  js/          # front-end da loja e dos painéis
│   └── uploads/           # fotos enviadas (produtos, perfis, logotipos)
├── resources/views/       # Blade: home, products, cart, orders, admin, loja…
├── routes/web.php         # todas as rotas da aplicação
├── docker-compose.yml     # serviços app (PHP+Apache) e db (MySQL)
└── Dockerfile             # imagem php:8.2-apache com as extensões necessárias
```

---

## ✅ Pré-requisitos

Escolha **um** dos dois caminhos abaixo.

### Caminho A — Docker (recomendado)

| Ferramenta | Versão | Observação |
|---|---|---|
| [Docker Desktop](https://www.docker.com/products/docker-desktop/) | atual | já inclui o Docker Compose v2 |
| Git | qualquer | para clonar o repositório |

Nada mais precisa estar instalado: PHP, Composer e MySQL vivem dentro dos
containers.

### Caminho B — instalação local

| Ferramenta | Versão | Observação |
|---|---|---|
| PHP | **8.2 ou superior** | com as extensões listadas abaixo |
| [Composer](https://getcomposer.org/) | 2.x | gerenciador de dependências PHP |
| MySQL | **8.0** (ou MariaDB 11.4) | banco da aplicação |
| Git | qualquer | para clonar o repositório |

**Extensões PHP obrigatórias** (as mesmas que o `Dockerfile` compila, mais as
que o Laravel 11 exige de fábrica):

```
pdo  pdo_mysql  mbstring  tokenizer  xml  dom  simplexml  xmlwriter
ctype  fileinfo  session  openssl  curl  json  filter  gd  zip  bcmath  exif
```

Verifique o que você já tem com:

```bash
php -m
```

### Opcional — Node.js

Só é necessário para **recompilar** a tela de gráficos do admin. O bundle
pronto já está versionado, então quem só quer rodar o sistema pode ignorar.

| Ferramenta | Versão |
|---|---|
| Node.js | **22.22.3+**, 24.15+ ou 26+ (exigência do Angular 22) |
| npm | 8+ |

---

## 🐳 Instalação com Docker (recomendado)

### 1. Clone o repositório

```bash
git clone <url-do-repositorio>
cd Projeto-vendas
```

### 2. Suba os containers

```bash
docker compose up -d --build
```

A primeira execução baixa as imagens, compila as extensões PHP e roda o
`composer install` — pode levar alguns minutos.

### 3. Aguarde a inicialização automática

O `docker/entrypoint.sh` executa sozinho, nesta ordem:

1. `composer install` (se `vendor/` não existir);
2. copia `.env.example` → `.env` (se `.env` não existir);
3. gera a `APP_KEY` (se ainda não houver);
4. **espera o MySQL aceitar conexões**;
5. roda `php artisan migrate --force`;
6. cria o link `public/storage`;
7. limpa as views compiladas e ajusta o dono de `storage/`, `bootstrap/cache`
   e `public/uploads` para `www-data`.

Acompanhe o processo com:

```bash
docker compose logs -f app
```

Espere pela linha do Apache subindo. Enquanto aparecer
`Aguardando o banco de dados...`, o MySQL ainda está inicializando o datadir
(na primeira vez isso pode passar de 2 minutos).

### 4. Popule o banco

As migrations rodam sozinhas, mas **os seeders não**. Sem eles a loja abre
vazia. Rode a massa de desenvolvimento completa:

```bash
docker compose exec app sh docker/seed-dev.sh
```

Ou só o mínimo (11 produtos base + taxonomia + usuário admin):

```bash
docker compose exec -u www-data app php artisan db:seed
```

> ⚠️ **Sempre rode `artisan` como `www-data`** (`-u www-data`). Sem isso o
> comando roda como root e os arquivos de cache que ele gerar — em especial as
> views compiladas em `storage/framework/views` — ficam com dono root. O Apache
> não consegue sobrescrevê-los e a loja passa a devolver **HTTP 500** até o
> arquivo ser removido ou o container reiniciar. O `seed-dev.sh` já corrige as
> permissões no fim.

### 5. Acesse

**http://localhost:8000**

Os containers em execução são:

| Container | Serviço | Porta no host |
|---|---|---|
| `hr_moda_app` | PHP 8.2 + Apache servindo o Laravel | `8000` → 80 |
| `hr_moda_db` | MySQL 8.0 (volume `db_data`) | `3306` |

O código-fonte é montado como volume (`.:/var/www/html`), então **alterações
nos arquivos refletem na hora**, sem rebuild. Só é preciso `--build` de novo ao
mudar o `Dockerfile` ou as dependências do Composer.

---

## ⚙️ Instalação sem Docker

### 1. Clone e instale as dependências PHP

```bash
git clone <url-do-repositorio>
cd Projeto-vendas
composer install
```

### 2. Crie o arquivo de ambiente

```bash
cp .env.example .env
php artisan key:generate
```

O `.env` **não é versionado** (está no `.gitignore`) — esse passo é
obrigatório.

### 3. Crie o banco de dados

```sql
CREATE DATABASE hr_moda_online
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER 'hr_user'@'127.0.0.1' IDENTIFIED BY 'hr_password';
GRANT ALL PRIVILEGES ON hr_moda_online.* TO 'hr_user'@'127.0.0.1';
FLUSH PRIVILEGES;
```

Se usar essas credenciais, ajuste o `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hr_moda_online
DB_USERNAME=hr_user
DB_PASSWORD=hr_password
```

> São exatamente as mesmas credenciais do `docker-compose.yml`, de propósito:
> o mesmo `.env` serve nos dois caminhos.

### 4. Rode as migrations e os seeders

```bash
php artisan migrate
php artisan db:seed                                   # produtos base, taxonomia e admin
php artisan db:seed --class=CatalogoTesteSeeder       # catálogo com variantes
php artisan db:seed --class=CatalogoQuinhentosSeeder  # completa até 500 produtos
php artisan db:seed --class=TestDataSeeder            # contas de teste
php artisan db:seed --class=VolumeTesteSeeder         # 50 lojas, 100 clientes, pedidos
php artisan db:seed --class=InteracoesTesteSeeder     # favoritos, avaliações, buscas
```

**A ordem importa**: `InteracoesTesteSeeder` depende dos clientes de
`TestDataSeeder` e dos produtos de `CatalogoTesteSeeder`.

### 5. Crie o link de storage e ajuste permissões

```bash
php artisan storage:link
chmod -R 775 storage bootstrap/cache public/uploads
```

Uploads de foto de produto, foto de perfil e logotipo de loja são gravados em
`public/uploads` — a pasta **precisa** ter permissão de escrita para o usuário
que roda o PHP. Documentos de KYC do lojista vão para
`storage/app/private/lojistas-kyc`.

### 6. Suba o servidor

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

Acesse **http://localhost:8000**.

---

## 🔧 Configuração do `.env`

Variáveis relevantes (as demais podem ficar como no `.env.example`):

| Variável | Padrão | Para que serve |
|---|---|---|
| `APP_NAME` | `HR Moda Online` | nome exibido na aplicação |
| `APP_ENV` | `local` | ambiente; use `production` ao publicar |
| `APP_KEY` | *(vazio)* | **gere com `php artisan key:generate`** |
| `APP_DEBUG` | `true` | telas de erro detalhadas; deixe `false` em produção |
| `APP_URL` | `http://localhost:8000` | base para links e assets |
| `APP_LOCALE` | `pt_BR` | idioma da aplicação |
| `DB_CONNECTION` | `mysql` | driver do banco |
| `DB_HOST` | `127.0.0.1` | **no Docker é `db`** — já definido no compose |
| `DB_PORT` | `3306` | porta do MySQL |
| `DB_DATABASE` | `hr_moda_online` | nome do banco |
| `DB_USERNAME` / `DB_PASSWORD` | `root` / *(vazio)* | credenciais; ajuste conforme o seu MySQL |
| `SESSION_DRIVER` | `database` | sessões na tabela `sessions` |
| `CACHE_STORE` | `database` | cache na tabela `cache` |
| `QUEUE_CONNECTION` | `database` | filas na tabela `jobs` |
| `MAIL_MAILER` | `log` | e-mails vão para `storage/logs/laravel.log`, não são enviados |

> **No Docker você não precisa mexer nas variáveis `DB_*`.** O
> `docker-compose.yml` injeta `DB_HOST=db`, `DB_DATABASE=hr_moda_online`,
> `DB_USERNAME=hr_user` e `DB_PASSWORD=hr_password` no ambiente do container,
> e essas sobrescrevem o que estiver no `.env`.

Limite de upload configurado em `docker/php/uploads.ini`: **20 MB** por arquivo
(`memory_limit` de 256M). A aplicação valida imagens de até **5 MB** nos
formatos `jpg`, `jpeg`, `png` e `webp`.

---

## 🗄️ Banco de dados: migrations e seeders

### Migrations

São 34 migrations em `database/migrations/`, criando entre outras: `users`,
`products`, `product_variants`, `product_classes`, `product_subclasses`,
`orders`, `order_items`, `cart_items`, `favorites`, `product_reviews`,
`lojas`, `transportadoras`, `motoristas`, `settings`, `page_visits`,
`search_logs`, além das tabelas de `cache`, `jobs` e `sessions`.

```bash
php artisan migrate            # aplica as pendentes
php artisan migrate:status     # lista o que já rodou
php artisan migrate:fresh      # apaga tudo e recria (perde os dados)
```

### Seeders

| Seeder | Roda com `db:seed`? | O que cria |
|---|---|---|
| `ProductSeeder` | ✅ sim | os 11 produtos do catálogo original |
| `ProductTaxonomySeeder` | ✅ sim | classes e subclasses de produto |
| `AdminSeeder` | ✅ sim | o usuário administrador |
| `CatalogoTesteSeeder` | ❌ `--class` | catálogo de teste com variantes (grade de tamanhos e cores) |
| `CatalogoQuinhentosSeeder` | ❌ `--class` | completa o catálogo até **500 produtos** com estoque |
| `TestDataSeeder` | ❌ `--class` | 3 clientes, 1 lojista (com KYC de marcação) e pedidos |
| `VolumeTesteSeeder` | ❌ `--class` | **50 lojas, 100 clientes**, transportadoras, motoristas e histórico de pedidos |
| `InteracoesTesteSeeder` | ❌ `--class` | favoritos, avaliações, buscas, carrinhos em aberto e visitas |

`php artisan db:seed` roda só os três primeiros (via `DatabaseSeeder`). Os
demais precisam de `--class=NomeDoSeeder`.

O atalho que roda tudo na ordem certa é o `docker/seed-dev.sh` — ele ainda
espera as migrations terminarem antes de começar e corrige as permissões no
final.

**Massa esperada depois do seed completo:** ~500 produtos, ~4.900 variantes,
~155 usuários, ~354 pedidos e ~51 lojas.

> ⚠️ Os seeders de massa **não foram feitos para rodar duas vezes** sobre os
> mesmos dados. Para recomeçar do zero, use `php artisan migrate:fresh` (ou
> `docker compose down -v`) antes de semear de novo.

---

## 🔑 Contas de acesso

Criadas pelos seeders:

| Perfil | E-mail | Senha | Criado por |
|---|---|---|---|
| **Administrador** | `admin@hrmoda.com.br` | `AdminHR@2026` | `AdminSeeder` (roda no `db:seed`) |
| **Lojista** | `lojista@teste.com` | `Teste@1234` | `TestDataSeeder` |
| **Cliente** | `ana@teste.com` | `Teste@1234` | `TestDataSeeder` |
| **Cliente** | `beatriz@teste.com` | `Teste@1234` | `TestDataSeeder` |
| **Cliente** | `carla@teste.com` | `Teste@1234` | `TestDataSeeder` |
| Clientes em volume | `cliente001@cliente.teste` … `cliente100@cliente.teste` | `Teste@1234` | `VolumeTesteSeeder` |
| Lojistas em volume | `lojista01@loja.teste` … `lojista50@loja.teste` | `Teste@1234` | `VolumeTesteSeeder` |

Contas novas podem ser criadas pela própria interface: `/cadastro` (cliente) e
`/cadastro/lojista` (lojista — exige CPF/CNPJ válido e upload dos documentos
de KYC).

> 🔒 Estas credenciais são **de desenvolvimento**. Troque-as antes de qualquer
> uso real.

---

## 🗺️ Mapa de telas

### Público

| Rota | Tela |
|---|---|
| `/` | vitrine: hero, coleção com filtro e busca, sobre e contato |
| `/produtos/{id}` | página do produto, com variantes, avaliações e a loja que vende |
| `/lojas/{id}` | vitrine da loja |
| `/login` · `/cadastro` · `/cadastro/lojista` | autenticação |

### Cliente autenticado

| Rota | Tela |
|---|---|
| `/carrinho` | carrinho e checkout |
| `/pedidos` | histórico de pedidos, cancelamento e avaliação |
| `/pedidos/acompanhar` | rastreamento da entrega |
| `/favoritos` | produtos favoritados |
| `/perfil` | dados pessoais, endereço e foto |

### Lojista (`/loja`, exige `role = lojista`)

Dashboard, produtos, pedidos (separar → embalar → enviar), clientes,
transportadoras e motoristas, perfil da loja com logotipo e bio.

### Administrador (`/admin`, exige `role = admin`)

Dashboard, gráficos, clientes, e-mails, faturamento e catálogo (criar e editar
produtos).

> Acessar `/carrinho`, `/favoritos`, `/admin` ou `/loja` sem estar logado
> devolve **302** para o login. Isso é o comportamento correto, não uma falha.

---

## 📊 Painel de gráficos (Angular)

A tela `/admin/graficos` é uma aplicação Angular 22 com Chart.js que consome os
endpoints JSON do `AdminController` (`/admin/graficos/acessos`,
`/receita`, `/volume-compras`, `/satisfacao`, `/vendas-categoria`).

**O bundle compilado já está versionado em `public/admin-charts/browser/`** —
para rodar o sistema você não precisa de Node.js nem de `npm install`.

Só recompile ao alterar o código em `admin-charts/`:

```bash
cd admin-charts
npm install
npm run build      # gera direto em ../public/admin-charts
```

O `angular.json` já aponta `outputPath` para `../public/admin-charts` e
`baseHref` para `/admin-charts/`. Commite o resultado do build junto com a
alteração do fonte.

Durante o desenvolvimento, `npm start` sobe o dev server do Angular, mas os
endpoints continuam vindo do Laravel — mantenha a aplicação PHP no ar em
paralelo.

---

## 🛠️ Comandos úteis

### Docker

```bash
docker compose up -d                 # subir
docker compose up -d --build         # subir reconstruindo a imagem
docker compose logs -f app           # acompanhar os logs da aplicação
docker compose logs -f db            # acompanhar os logs do MySQL
docker compose down                  # parar (mantém os dados do banco)
docker compose down -v               # parar e apagar o volume do MySQL
docker compose exec app bash         # shell dentro do container
```

### Artisan (dentro do Docker, sempre como `www-data`)

```bash
docker compose exec -u www-data app php artisan migrate
docker compose exec -u www-data app php artisan db:seed
docker compose exec -u www-data app php artisan tinker
docker compose exec -u www-data app php artisan route:list
docker compose exec -u www-data app php artisan view:clear
docker compose exec -u www-data app php artisan optimize:clear
```

Sem Docker, é o mesmo sem o prefixo: `php artisan migrate`, etc.

### Banco

```bash
docker compose exec db mysql -uhr_user -phr_password hr_moda_online
```

### Qualidade de código

```bash
./vendor/bin/pint          # formatação (Laravel Pint)
```

---

## 🚨 Solução de problemas

<details>
<summary><b>A página devolve HTTP 500 depois que rodei um comando artisan</b></summary>

Você rodou o `artisan` como root e as views compiladas ficaram com dono root —
o Apache (`www-data`) não consegue sobrescrevê-las.

```bash
docker compose exec app php artisan view:clear
docker compose exec app chown -R www-data:www-data storage bootstrap/cache
```

Reiniciar o container (`docker compose restart app`) também resolve, porque o
entrypoint limpa as views no boot. Para não repetir: use sempre `-u www-data`.
</details>

<details>
<summary><b>`dependency failed to start: db is unhealthy`</b></summary>

A primeira inicialização do datadir do MySQL demora. O healthcheck já está
configurado com 30 tentativas e `start_period` de 30s, mas em máquina lenta
pode não bastar. Rode de novo:

```bash
docker compose up -d
```

Na segunda tentativa o datadir já existe e o banco sobe em segundos.
</details>

<details>
<summary><b>`SQLSTATE[HY000] [2002] Connection refused`</b></summary>

O banco ainda não aceitou conexões, ou o `DB_HOST` está errado.

- **No Docker**, `DB_HOST` tem de ser `db` (o compose já injeta isso).
- **Sem Docker**, tem de ser `127.0.0.1` e o MySQL precisa estar rodando.

Confira com `docker compose ps` (ou `systemctl status mysql`).
</details>

<details>
<summary><b>MariaDB local recusa conexão em 127.0.0.1</b></summary>

Alguns pacotes (notadamente o do Alpine) vêm com `skip-networking` ligado: o
servidor sobe só no socket unix e o Laravel, que usa TCP, quebra. Edite
`/etc/my.cnf.d/mariadb-server.cnf`, comente `skip-networking` e adicione:

```ini
bind-address=127.0.0.1
port=3306
```

Depois reinicie o serviço.
</details>

<details>
<summary><b>A loja abre sem nenhum produto</b></summary>

As migrations rodam automaticamente, mas os seeders não. Rode
`docker compose exec app sh docker/seed-dev.sh` (ou os `db:seed` da seção
[Banco de dados](#-banco-de-dados-migrations-e-seeders)).
</details>

<details>
<summary><b>Upload de foto falha ou a imagem não aparece</b></summary>

`public/uploads` precisa existir e ser gravável:

```bash
docker compose exec app chown -R www-data:www-data public/uploads
# sem Docker:
chmod -R 775 public/uploads
```

Arquivos acima de 20 MB são barrados pelo PHP (`docker/php/uploads.ini`) e
acima de 5 MB pela validação da aplicação.
</details>

<details>
<summary><b>A porta 8000 ou 3306 já está em uso</b></summary>

Altere o mapeamento no `docker-compose.yml` (por exemplo `"8080:80"` e
`"3307:3306"`) e ajuste `APP_URL` no `.env`.
</details>

<details>
<summary><b>`Please provide a valid cache path` ou erros de permissão</b></summary>

As pastas de cache do Laravel não existem ou não são graváveis:

```bash
mkdir -p storage/framework/{cache,sessions,views} bootstrap/cache
chmod -R 775 storage bootstrap/cache
```
</details>

<details>
<summary><b>Página sem imagens ou sem JS atrás de um proxy HTTPS</b></summary>

Já está tratado: o `bootstrap/app.php` confia no `X-Forwarded-Proto` de
qualquer proxy (`trustProxies(at: '*')`). Se ainda ocorrer, confirme que
`APP_URL` usa `https://` com o domínio correto.
</details>

---

## ☁️ GitHub Codespaces

O repositório traz um `.devcontainer/` que reaproveita o mesmo
`docker-compose.yml`, então o Codespace já nasce pronto:

- serviço `app`, workspace em `/var/www/html`;
- porta **80** encaminhada e aberta no navegador automaticamente;
- `postCreateCommand` roda `docker/seed-dev.sh` (o volume do MySQL nasce vazio,
  sem isso a loja abriria sem produtos);
- `postStartCommand` roda `docker/auto-pull.sh`, que traz os pushes do `main` a
  cada 30s e limpa as views compiladas quando algo muda (log em
  `/tmp/auto-pull.log`);
- a feature `sshd` fica instalada para `gh codespace ssh`.

Se o Codespace tiver sido criado **sem** o devcontainer do projeto (imagem
padrão, sem `php` nem `docker`), existe um script alternativo que instala
PHP 8.4 + MariaDB e sobe o `artisan serve`:

```bash
sh .claude/skills/rodar-local/scripts/subir-sem-docker.sh --seed
```

Ele é idempotente — reexecute sem `--seed` depois de um restart, que ele só
religa o que caiu.

---

## 🤝 Contribuições

Contribuições são bem-vindas. Abra uma issue ou envie um pull request.

Antes de commitar, rode o formatador:

```bash
./vendor/bin/pint
```

---

## 📄 Licença

Este projeto está sob a licença MIT.
