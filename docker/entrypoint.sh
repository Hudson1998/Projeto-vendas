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
until php artisan db:show > /dev/null 2>&1; do
    echo "Aguardando o banco de dados..."
    sleep 2
done

php artisan migrate --force

if [ ! -L public/storage ]; then
    php artisan storage:link
fi

chown -R www-data:www-data storage bootstrap/cache public/uploads 2>/dev/null || true

exec "$@"
