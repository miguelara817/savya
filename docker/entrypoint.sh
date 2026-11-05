#!/bin/sh
set -eu

if [ ! -f .env ]; then
    echo "Falta el archivo .env montado en el contenedor." >&2
    exit 1
fi

mkdir -p storage/app/private storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
php artisan storage:link --force
php artisan migrate --force
php artisan optimize
exec /usr/bin/supervisord -c /etc/supervisord.conf
