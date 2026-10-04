#!/bin/sh
set -eu

# El volumen persistente puede contener archivos creados por comandos ejecutados como root.
if [ "$(id -u)" = '0' ]; then
    mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
    chown -R www-data:www-data storage bootstrap/cache
    find storage bootstrap/cache -type d -exec chmod 775 {} +
    find storage bootstrap/cache -type f -exec chmod 664 {} +
fi

exec docker-php-entrypoint "$@"
