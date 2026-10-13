#!/usr/bin/env bash
# Actualiza una instalación Git de Lameri sin reemplazar .env ni datos de storage.
set -Eeuo pipefail

project_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd -P)"
cd -- "$project_dir"
php_bin="${LAMERI_PHP_BIN:-php}"
composer_bin="${LAMERI_COMPOSER_BIN:-composer}"
restore_maintenance=0
connect_owner_file="$project_dir/storage/framework/lameri-git-connect.owner"
owned_maintenance=0

fail() {
    printf '%s\n' "No se actualizó Lameri: $*" >&2
    exit 1
}

finish() {
    local status=$?
    trap - EXIT INT TERM
    if (( restore_maintenance )); then
        if ! "$php_bin" artisan up --no-interaction; then
            printf '%s\n' 'No se pudo salir de mantenimiento. Revisa el error y ejecuta php artisan up.' >&2
            status=1
        fi
    fi
    if (( status )); then
        printf '%s\n' 'La actualización se detuvo. Conserva el error mostrado para corregirlo; no se hizo reset ni se restauró la base.' >&2
    fi
    exit "$status"
}

command -v "$composer_bin" >/dev/null || fail 'Falta Composer 2; configura LAMERI_COMPOSER_BIN si usa otro nombre.'
composer_version="$("$composer_bin" --version --no-ansi)" || fail 'No se pudo ejecutar Composer.'
[[ "$composer_version" == *"Composer version 2."* || "$composer_version" == *"Composer 2."* ]] || fail 'La terminal necesita Composer 2.'

command -v git >/dev/null || fail 'Falta Git en la terminal SSH.'
command -v "$php_bin" >/dev/null || fail 'Falta PHP 8.3; configura LAMERI_PHP_BIN si usa otro nombre.'
"$php_bin" -r 'exit(PHP_VERSION_ID >= 80300 ? 0 : 1);' || fail 'La terminal necesita PHP 8.3 o superior.'
[[ -f .env && -f vendor/autoload.php ]] || fail 'Primero configura .env y vendor en la instalación Git; consulta README_HOSTINGER.md.'
[[ "$(git rev-parse --show-toplevel 2>/dev/null)" == "$project_dir" ]] || fail 'Esta carpeta debe ser la raíz de un clon Git.'
[[ "$(git branch --show-current)" == 'lameri_new' ]] || fail 'La rama activa debe ser lameri_new.'
git diff --quiet && git diff --cached --quiet || fail 'Hay archivos versionados modificados. Revísalos con git status antes de actualizar.'
git remote get-url origin >/dev/null || fail 'Falta el remoto origin del repositorio lab_oncosavi.'

# Se comprueba el avance y Composer antes de poner el sitio en mantenimiento.
git fetch --no-tags origin lameri_new
git merge-base --is-ancestor HEAD FETCH_HEAD || fail 'La rama local divergió del remoto; no se hará reset ni un merge automático.'

# Sólo el conector que activó mantenimiento puede delegar su estado a este hijo.
if [[ "${LAMERI_UPDATE_OWNED_MAINTENANCE:-0}" == '1' ]]; then
    [[ -f "$connect_owner_file" && ! -L "$connect_owner_file" && "$(< "$connect_owner_file")" == "$PPID" ]] || fail 'El mantenimiento delegado no pertenece al proceso conector.'
    owned_maintenance=1
fi

# En una actualización normal se comprueba la instalación anterior. El conector
# acaba de cambiar el código de un ZIP y aún conserva el autoload viejo: el
# primer arranque del código nuevo se realiza después de sincronizar Composer.
if (( ! owned_maintenance )); then
if "$php_bin" <<'PHP'
<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
exit($app->isDownForMaintenance() ? 10 : 0);
PHP
then
    :
else
    maintenance_status=$?
    if (( maintenance_status == 10 )); then
        fail 'El sistema ya está en mantenimiento; termina esa operación antes de actualizar.'
    fi
    fail 'La aplicación no pudo arrancar antes de actualizar. Revisa .env, PHP y las dependencias.'
fi
fi

# El enlace lo crea SSH; no depende de exec/symlink habilitados en PHP.
mkdir -p storage/app/public bootstrap/cache
if [[ -L public_html/storage ]]; then
    [[ "$(readlink -f public_html/storage)" == "$(cd storage/app/public && pwd -P)" ]] || fail 'public_html/storage apunta a otra carpeta; corrígelo siguiendo la guía.'
elif [[ -e public_html/storage ]]; then
    fail 'public_html/storage ya existe como carpeta/archivo. Conserva su contenido y revisa la guía; no se sobrescribirá.'
else
    ln -sT "$project_dir/storage/app/public" "$project_dir/public_html/storage"
fi
chmod -R u+rwX storage bootstrap/cache

trap finish EXIT
trap 'exit 130' INT
trap 'exit 143' TERM
restore_maintenance=1
if (( ! owned_maintenance )); then
"$php_bin" artisan down --retry=60 --no-interaction
fi

git pull --ff-only --no-rebase origin lameri_new
# Composer --no-scripts no limpia los manifests de Laravel. Se retiran sólo
# caches generados antes de que un proveedor o componente eliminado pueda cargar.
generated_caches=(
    bootstrap/cache/config.php bootstrap/cache/services.php bootstrap/cache/packages.php
    bootstrap/cache/events.php bootstrap/cache/blade-icons.php
)
shopt -s nullglob
generated_caches+=(bootstrap/cache/routes-v*.php bootstrap/cache/filament/panels/*.php)
shopt -u nullglob
rm -f -- "${generated_caches[@]}"

# Aunque el lock no cambie, se regenera el mapa de clases de App para clases movidas.
# Los assets públicos se publican en Git: no se reescriben desde Composer.
"$composer_bin" install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress --no-scripts

git ls-files --error-unmatch public_html/build/manifest.json >/dev/null || fail 'Falta el frontend compilado versionado; la publicación debe incluir public_html/build.'
"$php_bin" <<'PHP'
<?php
$manifest = json_decode(file_get_contents('public_html/build/manifest.json'), true);
if (!$manifest) exit(1);
foreach ($manifest as $entry) {
    foreach (array_merge([$entry['file']], $entry['css'] ?? []) as $asset) {
        if (!is_file('public_html/build/'.$asset)) {
            fwrite(STDERR, "Falta un recurso compilado: $asset\n");
            exit(1);
        }
    }
}
PHP

"$php_bin" artisan config:clear --no-interaction
"$php_bin" artisan package:discover --no-interaction
"$php_bin" artisan migrate --force --no-interaction
"$php_bin" artisan optimize --no-interaction
"$php_bin" artisan up --no-interaction
restore_maintenance=0
printf '%s\n' 'Lameri actualizado. .env, archivos clínicos y credenciales se conservaron; no se ejecutaron seeders.'
