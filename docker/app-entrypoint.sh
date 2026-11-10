#!/bin/sh
set -eu

# El volumen persistente puede contener archivos creados por comandos ejecutados como root.
if [ "$(id -u)" = '0' ]; then
    mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
    chown -R www-data:www-data storage bootstrap/cache
    find storage bootstrap/cache -type d -exec chmod 775 {} +
    find storage bootstrap/cache -type f -exec chmod 664 {} +
    # El entorno QA público usa datos de prueba en un almacenamiento privado.
    # Conservar sus permisos restrictivos también al recrear el contenedor.
    if [ -d storage/app/private/qa-quimica ]; then
        find storage/app/private/qa-quimica -type d -exec chmod 700 {} +
        find storage/app/private/qa-quimica -type f -exec chmod 600 {} +
    fi
fi

exec docker-php-entrypoint "$@"
