#!/usr/bin/env bash
# Conecta la web instalada a Git en la misma carpeta del dominio.
set -Eeuo pipefail
umask 077
unset GIT_DIR GIT_WORK_TREE GIT_INDEX_FILE GIT_OBJECT_DIRECTORY GIT_ALTERNATE_OBJECT_DIRECTORIES

source_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd -P)"
php_bin="${LAMERI_WEB_PHP_BIN:-php}"
backup_dir=''

fail() {
    printf '%s\n' "No se conectó la web Lameri a Git: $*" >&2
    exit 1
}

finish() {
    local status=$?
    if (( status )) && [[ -n "$backup_dir" ]]; then
        printf '%s\n' "La operación se detuvo; conserva el respaldo privado: $backup_dir" >&2
    fi
}
trap finish EXIT

[[ $# == 1 ]] || fail 'Uso: bash /ruta/al/clon/scripts/connect-hostinger-git.sh /ruta/al/dominio'
target_dir="$(cd -- "$1" && pwd -P)"
[[ ! -e "$target_dir/.git" && ! -L "$target_dir/.git" ]] || fail 'Esta web ya tiene Git; usa bash scripts/update-hostinger.sh desde la raíz del dominio.'
[[ "$source_dir" != "$target_dir" && "$source_dir" != "$target_dir/"* && "$target_dir" != "$source_dir/"* ]] || fail 'El clon temporal y la web instalada deben estar en carpetas separadas.'
[[ -f "$target_dir/public_html/index.html" ]] || fail 'La carpeta destino debe ser el padre de public_html de la web instalada.'
[[ ! -f "$target_dir/artisan" ]] || fail 'Este conector es para la web pública, no para la aplicación clínica Laravel.'
[[ -d "$source_dir/.git" && ! -L "$source_dir/.git" ]] || fail 'Ejecuta este conector desde un clon nuevo normal, no desde el ZIP ni un worktree.'
command -v git >/dev/null || fail 'Falta Git en SSH.'
command -v "$php_bin" >/dev/null || fail 'Falta PHP; configura LAMERI_WEB_PHP_BIN si tiene otra ruta.'
"$php_bin" -r 'exit(PHP_VERSION_ID >= 80200 ? 0 : 1);' || fail 'La web necesita PHP 8.2 o superior.'
[[ "$(git -C "$source_dir" rev-parse --show-toplevel)" == "$source_dir" && "$(git -C "$source_dir" branch --show-current)" == lameri_web_publica ]] || fail 'El clon fuente debe ser la raíz de la rama lameri_web_publica.'
git -C "$source_dir" diff --quiet && git -C "$source_dir" diff --cached --quiet || fail 'El clon fuente tiene cambios locales; crea un clon nuevo.'
[[ -z "$(git -C "$source_dir" config --get core.worktree || true)" ]] || fail 'El clon fuente usa otra carpeta de trabajo; crea un clon normal.'
origin_url="$(git -C "$source_dir" remote get-url origin)" || fail 'Falta origin en el clon.'
case "$origin_url" in
    https://github.com/renancarranza1920/lab_oncosavi|https://github.com/renancarranza1920/lab_oncosavi.git|git@github.com:renancarranza1920/lab_oncosavi.git|ssh://git@github.com/renancarranza1920/lab_oncosavi.git) ;;
    *) fail 'origin debe apuntar al repositorio renancarranza1920/lab_oncosavi de GitHub.' ;;
esac
git -C "$source_dir" ls-files --error-unmatch public_html/index.html scripts/update-hostinger.sh >/dev/null || fail 'El clon debe incluir la página y el actualizador versionados.'
source_commit="$(git -C "$source_dir" rev-parse --verify 'HEAD^{commit}')"

# Sólo se permite instalar código. También se revisan los ancestros locales
# para que un enlace simbólico no redirija las escrituras fuera del dominio.
while IFS= read -r -d '' entry; do
    metadata="${entry%%$'\t'*}"
    file="${entry#*$'\t'}"
    [[ ! "$file" =~ [[:cntrl:]] ]] || fail 'El clon contiene un nombre de archivo con caracteres de control; no se instalará.'
    read -r mode type object <<< "$metadata"
    [[ "$type" == blob && ( "$mode" == 100644 || "$mode" == 100755 ) ]] || fail 'El clon contiene un enlace o submódulo que no puede instalarse.'
    lower="${file,,}"
    case "$lower" in
        .env|.env.*|*/.env|*/.env.*|lameri-publica.php|*/lameri-publica.php|config|config/*|configuracion|cache|cache/*|*/cache|*/cache/*|uploads|uploads/*|*/uploads|*/uploads/*|storage|storage/*|public_html|public_html/config|public_html/config/*|public_html/configuracion|public_html/configuracion/*|public_html/storage|public_html/storage/*|*.sql|*.sqlite|*.sqlite3|*.log|*.pem|*.key|*.p12|*.pfx|id_rsa*|id_ed25519*|*/id_rsa*|*/id_ed25519*)
            fail 'El clon intenta sustituir configuración privada, almacenamiento o archivos de datos; no se instalará.' ;;
        configuracion/.htaccess|configuracion/lameri-publica.php.example) ;;
        configuracion/*)
            fail 'El clon contiene archivos privados dentro de configuracion; no se instalará.' ;;
    esac
    current="$target_dir"
    IFS='/' read -r -a components <<< "$file"
    for component in "${components[@]}"; do
        current="$current/$component"
        [[ ! -L "$current" ]] || fail 'Un archivo que se instalaría o una de sus carpetas es un enlace simbólico; revísalo primero.'
    done
    if [[ "$lower" == public_html/api/*.php ]]; then
        git -C "$source_dir" show "$source_commit:$file" | "$php_bin" -l >/dev/null 2>&1 || fail 'Un archivo PHP de la API tiene un error de sintaxis; no se instalará.'
    fi
done < <(git -C "$source_dir" ls-tree -rz --full-tree "$source_commit")

backup_candidate="$HOME/lameri-web-backups"
[[ ! -L "$backup_candidate" ]] || fail 'La carpeta de respaldos no puede ser un enlace simbólico.'
backup_base="$(realpath -m -- "$backup_candidate")"
[[ "$backup_base" != "$target_dir" && "$backup_base" != "$target_dir/"* && "$backup_base" != "$source_dir" && "$backup_base" != "$source_dir/"* ]] || fail 'La carpeta de respaldo debe estar fuera del dominio y del clon.'
mkdir -p -- "$backup_base"
chmod 700 -- "$backup_base"
backup_dir="$(mktemp -d "$backup_base/web-inicial-$(date +%Y%m%d-%H%M%S)-XXXXXX")"
cp -a -- "$target_dir/." "$backup_dir/"
chmod 700 -- "$backup_dir"
printf '%s\n' "Respaldo privado completo: $backup_dir"

# Se restauran sólo los archivos versionados del clon. No se limpia ni borra
# la configuración real, la caché, los uploads ni otros archivos del hosting.
umask 022
cp -a -- "$source_dir/.git" "$target_dir/.git"
[[ "$(git -C "$target_dir" rev-parse HEAD)" == "$source_commit" ]] || fail 'El clon cambió durante el respaldo; no se instalará una versión distinta de la auditada.'
git -C "$target_dir" restore --source="$source_commit" --worktree -- .
printf '%s\n' 'Web conectada a lameri_web_publica en la misma carpeta. Para actualizar: bash scripts/update-hostinger.sh'
if [[ ! -f "$target_dir/configuracion/lameri-publica.php" ]]; then
    printf '%s\n' 'Para activar el catálogo, completa configuracion/lameri-publica.php siguiendo la guía; este comando no crea credenciales ni conecta a la base.'
fi
