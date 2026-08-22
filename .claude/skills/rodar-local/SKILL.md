---
name: rodar-local
description: Sobe a loja HR Moda Online neste Codespace e verifica que ela responde. Use quando pedirem para rodar, subir, iniciar ou testar o projeto no navegador, ou quando "php" e "docker" nao existirem no ambiente.
---

# Rodar a loja

## Primeiro: descubra em qual dos dois ambientes voce esta

```sh
command -v php docker
```

- **Achou os dois** → o Codespace subiu com o `.devcontainer/` correto (imagem
  `php:8.2-apache`, workspace em `/var/www/html`). Use o caminho oficial:
  `docker compose up -d` e pronto. O `entrypoint.sh` roda o `composer install`
  e as migrations; o `postCreateCommand` roda o `docker/seed-dev.sh`.
- **Nao achou nenhum** → o Codespace nasceu com a **imagem Alpine padrao** em
  `/workspaces/Projeto-vendas`. Nao existe `/var/www/html`, nem `docker`, nem
  `php`, nem `composer`, e o seed nunca rodou. Siga a secao abaixo. Nao perca
  tempo tentando `docker compose up`: o binario nao esta instalado.

## Ambiente Alpine sem Docker

```sh
sh .claude/skills/rodar-local/scripts/subir-sem-docker.sh --seed
```

O script instala PHP 8.4 + extensoes, sobe um MariaDB 11.4 no lugar do
servico `db`, cria `hr_moda_online`/`hr_user` com as **mesmas credenciais do
`docker-compose.yml`** (por isso o `.env` versionado serve nos dois caminhos e
nao deve ser editado), roda as migrations e sobe o `artisan serve` em
`0.0.0.0:8000`.

E idempotente: rode de novo apos um restart da maquina, **sem** `--seed`, que
ele so religa o que caiu. Use `--seed` apenas quando o banco estiver vazio —
os seeders nao sao feitos para rodar duas vezes sobre a mesma massa.

O script bloqueia no `artisan serve`. Para trabalhar em cima, rode em segundo
plano e acompanhe o log:

```sh
nohup sh .claude/skills/rodar-local/scripts/subir-sem-docker.sh > /tmp/loja.log 2>&1 &
```

### A pegadinha que custa tempo

O pacote do MariaDB no Alpine vem com **`skip-networking` ligado**: o servidor
sobe so no socket unix e o Laravel, que usa `DB_HOST=127.0.0.1`, quebra com
`SQLSTATE[HY000] [2002] Can't connect to server on '127.0.0.1'`. O script
comenta essa linha em `/etc/my.cnf.d/mariadb-server.cnf` e liga
`bind-address=127.0.0.1` / `port=3306`. Sem isso, nada funciona.

Outros detalhes: **nao ha `composer`** nesta imagem — o `vendor/` do
repositorio e a unica fonte (se sumir: `sudo apk add --no-cache composer &&
composer install`). E `php artisan route:list` **nao aceita** `--columns`
nesta versao.

## Nao pare no "subiu": exercite a loja

Um servidor que responde na porta nao prova que a loja funciona. Confira:

```sh
curl -s -o /dev/null -w "%{http_code}\n" http://127.0.0.1:8000/          # 200
curl -s -o /dev/null -w "%{http_code}\n" http://127.0.0.1:8000/produtos/1 # 200
curl -s -o /dev/null -w "%{http_code}\n" http://127.0.0.1:8000/admin      # 302
```

- **A home embute o catalogo como JSON e renderiza no cliente.** Nao procure
  `<a href>` de produto no HTML para saber se ha catalogo — procure `"nome":"`
  (devem vir ~530 ocorrencias).
- `/carrinho`, `/favoritos`, `/admin`, `/loja` respondem **302** para o login.
  Isso e o comportamento correto, nao uma falha.
- O cadastro e `/cadastro` (nao `/register`), e **nao existe** `/produtos` sem
  id. Um 404 nessas duas URLs e erro de chute, nao bug da loja.
- Para um fluxo autenticado de verdade, o admin semeado e
  `admin@hrmoda.com.br` / `AdminHR@2026`. Pegue o `_token` do `/login`, poste
  com cookie jar e entao chame `/admin/stats`, que devolve JSON com os
  numeros reais.
- Massa esperada apos o `--seed`: 500 produtos, 4.932 variantes, 155 usuarios,
  354 pedidos, 51 lojas.

## Ver no navegador

Aba **Ports** do VS Code, porta **8000**. O `.devcontainer` encaminha a **80**
(o Apache de dentro do container), nao a 8000 do `artisan serve` — no ambiente
Alpine pode ser preciso adicionar a 8000 a mao.
