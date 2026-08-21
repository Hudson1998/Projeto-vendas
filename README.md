# 👗 HR Moda Online — Catálogo em Laravel

Catálogo de produtos de moda online, migrado do site estático original (HTML/CSS/JS) para uma aplicação Laravel com os produtos armazenados em banco de dados.

## 📋 Sobre o Projeto

A vitrine (hero, coleção com filtro por categoria e busca, seção sobre e contato) é a mesma do site original, mas agora os produtos vêm de uma tabela `products` no MySQL em vez de um array fixo no JavaScript. O filtro por categoria e a busca continuam instantâneos no navegador: a página injeta os produtos do banco como JSON e o mesmo script de front-end original (`public/js/app.js`) filtra a lista sem recarregar a página.

## 🚀 Tecnologias

- **Laravel 11** (PHP) — rotas, controller e views Blade
- **MySQL** — tabela `products` via migration/seeder
- **HTML/CSS/JS** original preservado em `public/` (sem build step, sem Node/Vite necessários)

## 📁 Estrutura

```
├── app/Http/Controllers/HomeController.php   # busca produtos e categorias
├── app/Models/Product.php
├── database/migrations/                      # cria users, cache, jobs, products
├── database/seeders/ProductSeeder.php         # os 10 produtos do catálogo original
├── resources/views/layouts/app.blade.php      # header, footer, injeta JSON dos produtos
├── resources/views/home.blade.php             # hero, coleção, sobre, contato
├── public/css/styles.css
├── public/js/app.js                           # filtro/busca client-side
└── public/assets/                             # imagens dos produtos
```

## 🐳 Como rodar (Docker — recomendado)

Forma mais simples de iniciar o projeto: não precisa instalar PHP, Composer ou MySQL na máquina.

### Pré-requisitos

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) instalado e em execução (inclui o Docker Compose)
- Git (para clonar o repositório)

### Passo a passo

1. Clone o repositório e entre na pasta:
   ```bash
   git clone <url-do-repositorio>
   cd "Projeto vendas"
   ```
2. Suba os containers (a primeira vez baixa as imagens e builda a aplicação, pode demorar alguns minutos):
   ```bash
   docker compose up -d --build
   ```
3. Aguarde a inicialização. Na primeira subida o container `app` automaticamente:
   - instala as dependências do Composer;
   - copia `.env.example` para `.env` e gera a `APP_KEY`;
   - aguarda o MySQL ficar disponível e roda as migrations.

   Acompanhe o processo, se quiser, com:
   ```bash
   docker compose logs -f app
   ```
4. Acesse a aplicação em **http://localhost:8000**.
5. (Opcional) Popule o banco com dados de exemplo e o usuário admin:
   ```bash
   docker compose exec -u www-data app php artisan db:seed
   ```

Isso sobe dois containers:

- **app** — PHP 8.2 + Apache servindo o Laravel (`http://localhost:8000`)
- **db** — MySQL 8.0 (porta `3306`, dados persistidos no volume `db_data`)

As credenciais do banco usadas pelo container já vêm definidas no `docker-compose.yml` (variáveis `DB_*` do serviço `app`), sobrescrevendo as do `.env` local — não é necessário configurar nada manualmente.

### Comandos úteis

> **Rode o `artisan` como `www-data`** (`-u www-data`). Sem isso o comando roda
> como root e qualquer arquivo de cache que ele gerar — principalmente as views
> compiladas em `storage/framework/views` — fica com dono root, que o Apache não
> consegue sobrescrever depois. A página passa a devolver **500** até o arquivo
> ser removido ou o container reiniciar.

```bash
docker compose logs -f app        # acompanhar logs da aplicação
docker compose exec -u www-data app php artisan migrate --seed   # migrations + seeders
docker compose exec -u www-data app php artisan tinker            # tinker dentro do container
docker compose down               # parar os containers (mantém os dados do banco)
docker compose down -v            # parar e apagar também o volume do MySQL
```

O código-fonte é montado como volume (`.:/var/www/html`), então alterações nos arquivos refletem imediatamente sem rebuild — só é necessário `--build` novamente ao mudar o `Dockerfile` ou dependências do Composer.

## ⚙️ Como rodar sem Docker

### Pré-requisitos

- PHP 8.2 ou superior (com extensões `pdo_mysql`, `mbstring`, `gd`, `zip`, `bcmath`)
- [Composer](https://getcomposer.org/)
- MySQL 8.0 (ou compatível)

### Passo a passo

1. Instale as dependências:
   ```bash
   composer install
   ```
2. Crie o arquivo de ambiente e gere a chave da aplicação:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
3. Crie o banco `hr_moda_online` no MySQL e configure `DB_DATABASE`, `DB_USERNAME` e `DB_PASSWORD` no `.env`.
4. Rode as migrations (e, opcionalmente, os seeders):
   ```bash
   php artisan migrate --seed
   ```
5. Inicie o servidor:
   ```bash
   php artisan serve
   ```
6. Acesse **http://localhost:8000**.

## 🤝 Contribuições

Contribuições são bem-vindas! Sinta-se à vontade para abrir uma issue ou enviar um pull request.

## 📄 Licença

Este projeto está sob a licença MIT.
