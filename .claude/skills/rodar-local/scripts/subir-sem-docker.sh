#!/bin/sh
# Sobe a loja num ambiente Alpine sem Docker (Codespace que nasceu com a
# imagem padrao em vez do .devcontainer do projeto).
#
# E idempotente: pode rodar de novo depois de um restart da maquina sem
# reinstalar nada nem duplicar dados.
#
# Uso:  sh .claude/skills/rodar-local/scripts/subir-sem-docker.sh [--seed]
#       --seed  popula a massa de desenvolvimento (500 produtos, pedidos etc.)
set -e

RAIZ=$(CDPATH= cd -- "$(dirname -- "$0")/../../../.." && pwd)
SEMEAR=0
[ "$1" = "--seed" ] && SEMEAR=1

# ---------------------------------------------------------------- PHP --
# O composer.json exige php ^8.2; o Alpine 3.23 empacota o 8.4, que serve.
# Os pacotes espelham as extensoes que o Dockerfile compila com
# docker-php-ext-install, mais as que o Laravel 11 exige de fabrica
# (tokenizer, xml, session, openssl, ctype, fileinfo).
if ! command -v php >/dev/null 2>&1; then
    echo ">> instalando php 8.4 + extensoes"
    sudo apk add --no-cache \
        php84 php84-pdo php84-pdo_mysql php84-pdo_sqlite php84-sqlite3 \
        php84-mbstring php84-tokenizer php84-xml php84-dom php84-xmlwriter \
        php84-simplexml php84-fileinfo php84-session php84-openssl \
        php84-curl php84-gd php84-zip php84-bcmath php84-ctype php84-iconv \
        php84-phar php84-opcache
    # o apk instala o binario como "php84"; o artisan chama "php"
    sudo ln -sf /usr/bin/php84 /usr/local/bin/php
fi

# ------------------------------------------------------------ MariaDB --
# Substitui o servico "db" do compose. O MariaDB 11.4 fala o protocolo do
# MySQL 8, entao o driver mysql do Laravel conecta sem ajuste no .env.
if ! command -v mariadbd-safe >/dev/null 2>&1; then
    echo ">> instalando mariadb"
    sudo apk add --no-cache mariadb mariadb-client
fi

# o "sudo" no teste nao e enfeite: o datadir e mysql:mysql e fechado, entao um
# "[ -d ... ]" como usuario comum da falso por permissao e reinicializa tudo.
if ! sudo test -d /var/lib/mysql/mysql; then
    echo ">> inicializando o datadir"
    sudo mkdir -p /run/mysqld /var/lib/mysql
    sudo chown -R mysql:mysql /run/mysqld /var/lib/mysql
    sudo mariadb-install-db --user=mysql --datadir=/var/lib/mysql >/dev/null
fi

# O PACOTE DO ALPINE VEM COM "skip-networking" LIGADO: o servidor sobe so no
# socket unix e o Laravel, que usa DB_HOST=127.0.0.1, morre com
# "SQLSTATE[HY000] [2002] Can't connect to server on '127.0.0.1'".
# Sem esta troca nada mais abaixo funciona.
if grep -q '^skip-networking' /etc/my.cnf.d/mariadb-server.cnf; then
    echo ">> habilitando tcp em 127.0.0.1:3306"
    sudo sed -i 's/^skip-networking/#skip-networking\nbind-address=127.0.0.1\nport=3306/' \
        /etc/my.cnf.d/mariadb-server.cnf
    sudo mariadb-admin shutdown 2>/dev/null || true
    sleep 3
fi

if ! pgrep mariadbd >/dev/null 2>&1; then
    echo ">> subindo o mariadb"
    sudo sh -c 'nohup mariadbd-safe --datadir=/var/lib/mysql --user=mysql \
        > /tmp/mysqld.log 2>&1 &'
    espera=0
    until sudo mariadb -e 'SELECT 1' >/dev/null 2>&1; do
        espera=$((espera + 1))
        if [ "$espera" -gt 60 ]; then
            echo "!! mariadb nao subiu; ultimas linhas de /tmp/mysqld.log:"
            tail -20 /tmp/mysqld.log
            exit 1
        fi
        sleep 1
    done
fi

# Mesmo banco/usuario/senha do docker-compose.yml, para que o .env versionado
# sirva nos dois caminhos sem edicao.
echo ">> garantindo banco e usuario"
sudo mariadb -e "
CREATE DATABASE IF NOT EXISTS hr_moda_online
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'hr_user'@'localhost' IDENTIFIED BY 'hr_password';
CREATE USER IF NOT EXISTS 'hr_user'@'127.0.0.1' IDENTIFIED BY 'hr_password';
GRANT ALL PRIVILEGES ON hr_moda_online.* TO 'hr_user'@'localhost';
GRANT ALL PRIVILEGES ON hr_moda_online.* TO 'hr_user'@'127.0.0.1';
FLUSH PRIVILEGES;"

# ------------------------------------------------------------ Laravel --
cd "$RAIZ"

# Nao ha composer nesta imagem; o vendor/ do repositorio e a unica fonte.
if [ ! -f vendor/autoload.php ]; then
    echo "!! vendor/ ausente e nao ha composer nesta imagem."
    echo "   instale com: sudo apk add --no-cache composer && composer install"
    exit 1
fi

echo ">> migrations"
php artisan migrate --force

if [ "$SEMEAR" = "1" ]; then
    # Mesma ordem do docker/seed-dev.sh: InteracoesTesteSeeder depende de
    # TestDataSeeder (clientes) e de CatalogoTesteSeeder (produtos/variantes).
    echo ">> massa de desenvolvimento"
    php artisan db:seed --force
    for classe in CatalogoTesteSeeder CatalogoQuinhentosSeeder TestDataSeeder \
                  VolumeTesteSeeder InteracoesTesteSeeder; do
        echo ">>   $classe"
        php artisan db:seed --class="$classe" --force
    done
fi

echo ">> subindo o servidor em 0.0.0.0:8000"
php artisan serve --host=0.0.0.0 --port=8000
