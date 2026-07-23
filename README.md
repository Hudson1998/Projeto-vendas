# 👗 HR Moda Feminina — Catálogo em Laravel

Catálogo de produtos de moda feminina, migrado do site estático original (HTML/CSS/JS) para uma aplicação Laravel com os produtos armazenados em banco de dados.

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

## ⚙️ Como rodar

Requer PHP 8.2+, Composer e MySQL.

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Configure `DB_DATABASE`, `DB_USERNAME` e `DB_PASSWORD` no `.env` (crie o banco `hr_moda_feminina` antes) e depois:

```bash
php artisan migrate --seed
php artisan serve
```

A aplicação estará disponível em `http://localhost:8000`.

## 🤝 Contribuições

Contribuições são bem-vindas! Sinta-se à vontade para abrir uma issue ou enviar um pull request.

## 📄 Licença

Este projeto está sob a licença MIT.
