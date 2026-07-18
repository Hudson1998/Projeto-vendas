# 👗 Moda Feminina — E-commerce

Loja virtual de roupas de moda feminina, oferecendo uma experiência de compra moderna, rápida e segura.

## 📋 Sobre o Projeto

Este projeto é uma plataforma de e-commerce voltada para a venda de roupas femininas. O sistema permite que clientes naveguem pelo catálogo de produtos, adicionem itens ao carrinho e finalizem suas compras de forma prática e intuitiva.

## 🚀 Tecnologias Utilizadas

- **JavaScript** — Interatividade e lógica do front-end
- **.NET** — API e regras de negócio no back-end
- **MySQL** — Banco de dados relacional para armazenamento de produtos, clientes e pedidos
- **Docker** — Containerização da aplicação para facilitar o deploy e a execução em qualquer ambiente

## ✨ Funcionalidades

- Catálogo de produtos com fotos, descrições e preços
- Carrinho de compras
- Cadastro e login de clientes
- Gerenciamento de pedidos
- Painel administrativo para controle de estoque e produtos

## 🐳 Como Executar com Docker

```bash
# Clone o repositório
git clone https://github.com/seu-usuario/seu-repositorio.git

# Acesse a pasta do projeto
cd seu-repositorio

# Suba os containers
docker-compose up -d
```

A aplicação estará disponível em `http://localhost:3000` (ajuste a porta conforme sua configuração).

## 🗄️ Banco de Dados

O MySQL roda em um container Docker. As credenciais e a string de conexão devem ser configuradas no arquivo de variáveis de ambiente (`.env`), que não deve ser versionado no repositório.

## 📁 Estrutura do Projeto

```
├── frontend/        # Interface do usuário (JavaScript)
├── backend/         # API .NET
├── database/        # Scripts SQL e migrations
├── docker-compose.yml
└── README.md
```

## 🤝 Contribuições

Contribuições são bem-vindas! Sinta-se à vontade para abrir uma issue ou enviar um pull request.

## 📄 Licença

Este projeto está sob a licença MIT. Veja o arquivo `LICENSE` para mais detalhes.

---

Feito com 💜 por [Seu Nome](https://github.com/seu-usuario)
