#!/bin/sh
set -e

cd /var/www/html

if [ ! -f vendor/autoload.php ]; then
    composer install --optimize-autoloader --no-interaction --no-progress
fi

if [ ! -f .env ]; then
    cp .env.example .env
fi

if ! grep -q "^APP_KEY=base64" .env; then
    php artisan key:generate --force
fi

# aguarda o MySQL aceitar conexoes antes de migrar
# usa PDO direto: "artisan db:show" formata numeros e exige a extensao intl,
# falhando mesmo com o banco no ar (o loop nunca terminava e o Apache nao subia)
until php -r 'new PDO("mysql:host=".getenv("DB_HOST").";port=".(getenv("DB_PORT") ?: 3306).";dbname=".getenv("DB_DATABASE"), getenv("DB_USERNAME"), getenv("DB_PASSWORD"));' > /dev/null 2>&1; do
    echo "Aguardando o banco de dados..."
    sleep 2
done

php artisan migrate --force

if [ ! -L public/storage ]; then
    php artisan storage:link
fi

# descarta as views compiladas: rodar "artisan" via docker compose exec executa
# como root e deixa o .php compilado com dono root, que o Apache (www-data) nao
# consegue sobrescrever depois -- a pagina passa a devolver 500 ate o arquivo
# sumir. Limpar no boot garante que tudo seja recompilado pelo dono certo.
php artisan view:clear

chown -R www-data:www-data storage bootstrap/cache public/uploads 2>/dev/null || true

# A feature sshd do devcontainer instala o servidor, mas quem o inicia e o
# entrypoint que ela registra -- e o "entrypoint" do compose substitui esse.
# Sem isto o sshd nunca sobe e "gh codespace ssh" trava no banner exchange
# ("kex_exchange_identification"), deixando o container so acessivel pelo VS Code.
if [ -x /usr/sbin/sshd ]; then
    mkdir -p /var/run/sshd
    /usr/sbin/sshd || echo "aviso: sshd nao subiu"
fi

exec "$@"
