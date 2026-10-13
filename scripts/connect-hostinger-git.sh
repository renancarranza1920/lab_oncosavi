#!/usr/bin/env bash
# Conecta un ZIP ya instalado a Git conservando la carpeta pública y sus datos.
set -Eeuo pipefail
umask 077

source_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd -P)"
php_bin="${LAMERI_PHP_BIN:-php}"
composer_bin="${LAMERI_COMPOSER_BIN:-composer}"
maintenance_owned=0
owner_created=0
backup_dir=''

fail() {
    printf '%s\n' "No se conectó Lameri a Git: $*" >&2
    exit 1
}

[[ $# == 1 ]] || fail 'Uso: bash /ruta/al/clon/scripts/connect-hostinger-git.sh /ruta/al/proyecto/instalado'
target_dir="$(cd -- "$1" && pwd -P)"
owner_file="$target_dir/storage/framework/lameri-git-connect.owner"

finish() {
    local status=$?
    trap - EXIT INT TERM
    if (( maintenance_owned )); then
        if ! (cd -- "$target_dir" && "$php_bin" artisan up --no-interaction); then
            printf '%s\n' 'No se pudo salir de mantenimiento. Revisa el error y ejecuta php artisan up en el proyecto.' >&2
            status=1
        fi
    fi
    if (( owner_created )); then
        rm -f -- "$owner_file"
    fi
    if (( status )) && [[ -n "$backup_dir" ]]; then
        printf '%s\n' "La conexión se detuvo. Respaldo privado conservado: $backup_dir" >&2
    fi
    exit "$status"
}

[[ ! -e "$target_dir/.git" && ! -L "$target_dir/.git" ]] || fail 'El proyecto ya tiene Git; usa scripts/update-hostinger.sh.'
[[ "$source_dir" != "$target_dir" && "$source_dir" != "$target_dir/"* ]] || fail 'El clon temporal debe estar fuera del proyecto instalado.'
[[ -d "$source_dir/.git" && ! -L "$source_dir/.git" ]] || fail 'Ejecuta este script desde un clon nuevo, no desde el ZIP ni un worktree.'
[[ -f "$target_dir/artisan" && -f "$target_dir/.env" && -f "$target_dir/vendor/autoload.php" && -d "$target_dir/storage" && -d "$target_dir/public_html" ]] || fail 'La carpeta destino debe ser la raíz del proyecto ZIP instalado, con artisan, .env, vendor, storage y public_html.'
[[ ! -e "$owner_file" && ! -L "$owner_file" ]] || fail 'Hay una conexión a Git en curso o un marcador anterior; revísalo antes de continuar.'
command -v git >/dev/null || fail 'Falta Git en SSH.'
command -v "$php_bin" >/dev/null || fail 'Falta PHP 8.3.'
"$php_bin" -r 'exit(PHP_VERSION_ID >= 80300 ? 0 : 1);' || fail 'Se necesita PHP 8.3 o superior.'
command -v "$composer_bin" >/dev/null || fail 'Falta Composer 2 en SSH.'
composer_version="$("$composer_bin" --version --no-ansi)" || fail 'No se pudo ejecutar Composer.'
[[ "$composer_version" == *"Composer version 2."* || "$composer_version" == *"Composer 2."* ]] || fail 'Se necesita Composer 2.'
[[ "$(git -C "$source_dir" rev-parse --show-toplevel)" == "$source_dir" && "$(git -C "$source_dir" branch --show-current)" == 'lameri_new' ]] || fail 'El clon fuente debe estar en lameri_new.'
git -C "$source_dir" diff --quiet && git -C "$source_dir" diff --cached --quiet || fail 'El clon fuente tiene cambios locales.'
[[ -z "$(git -C "$source_dir" config --get core.worktree || true)" ]] || fail 'El clon fuente usa otra carpeta de trabajo; crea un clon normal.'
git -C "$source_dir" ls-files --error-unmatch public_html/build/manifest.json scripts/update-hostinger.sh >/dev/null || fail 'Faltan el frontend compilado o el actualizador en el clon.'

# El repositorio nunca puede sustituir las credenciales ni archivos operativos.
while IFS= read -r -d '' file; do
    case "$file" in
        .env.example) ;;
        .env|.env.*|public_html/storage|public_html/storage/*|*.sql|*.sqlite|*.sqlite3)
            fail "El clon contiene un archivo de datos que no debe instalarse: $file" ;;
        storage/*|bootstrap/cache/*)
            [[ "${file##*/}" == '.gitignore' ]] || fail "El clon contiene almacenamiento o caché operativo: $file" ;;
    esac
done < <(git -C "$source_dir" ls-files -z)

cd -- "$target_dir"
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
    status=$?
    if (( status == 10 )); then
        fail 'El proyecto ya estaba en mantenimiento; termina esa operación antes de conectarlo.'
    fi
    fail 'La instalación actual no pudo arrancar. Corrígela antes de cambiar su código.'
fi

backup_base="$(realpath -m -- "$HOME/lameri-backups")"
[[ "$backup_base" != "$target_dir" && "$backup_base" != "$target_dir/"* && "$backup_base" != "$source_dir" && "$backup_base" != "$source_dir/"* ]] || fail 'La carpeta de respaldo debe estar fuera del proyecto y del clon.'
mkdir -p -- "$backup_base" storage/framework
chmod 700 -- "$backup_base"
backup_dir="$(mktemp -d "$backup_base/proyecto-$(date +%Y%m%d-%H%M%S)-XXXXXX")"
trap finish EXIT
trap 'exit 130' INT
trap 'exit 143' TERM
(set -o noclobber; printf '%s\n' "$$" > "$owner_file") || fail 'Otro proceso comenzó a conectar este proyecto.'
owner_created=1
maintenance_owned=1
"$php_bin" artisan down --retry=60 --no-interaction

# Respaldo completo fuera del dominio antes de sustituir cualquier código.
cp -a -- "$target_dir/." "$backup_dir/"
chmod 700 -- "$backup_dir"
printf '%s\n' "Respaldo privado completo: $backup_dir"

# El índice del clon define exactamente qué código se restaura; los archivos
# ajenos a Git (.env, firmas, PDFs, sesiones y otros datos) se conservan.
umask 022
cp -a -- "$source_dir/.git" "$target_dir/.git"
git restore --source=HEAD --worktree -- .

# El actualizador reconoce sólo el mantenimiento activado por este proceso.
# En ese caso no arranca el código nuevo con el autoload/cachés antiguos antes
# de que Composer y la limpieza de cachés los hayan sincronizado.
LAMERI_UPDATE_OWNED_MAINTENANCE=1 bash scripts/update-hostinger.sh
maintenance_owned=0
printf '%s\n' 'Proyecto conectado a lameri_new en la misma carpeta. Las próximas actualizaciones usan bash scripts/update-hostinger.sh.'
