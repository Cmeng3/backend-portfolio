#!/bin/sh
set -eu

cd /var/www/html
: "${APP_KEY:?Set APP_KEY in Render environment variables before deploying}"
export PORT="${PORT:-10000}"

php artisan config:cache
php artisan route:cache
php artisan view:cache

# Opt in only when deploying reviewed, pending migrations. Never reset or seed production.
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force
fi

chown -R www-data:www-data storage bootstrap/cache
exec apache2-foreground
