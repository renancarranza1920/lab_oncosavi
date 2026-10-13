#!/usr/bin/env bash
# Actualiza la web pública; la configuración y los archivos del hosting no se versionan.
set -Eeuo pipefail
umask 022
unset GIT_DIR GIT_WORK_TREE GIT_INDEX_FILE GIT_OBJECT_DIRECTORY GIT_ALTERNATE_OBJECT_DIRECTORIES

project_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd -P)"
php_bin="${LAMERI_WEB_PHP_BIN:-php}"
backup_dir=''

fail() {
    printf '%s\n' "No se actualizó la web Lameri: $*" >&2
    exit 1
}

finish() {
    local status=$?
    if (( status )) && [[ -n "$backup_dir" ]]; then
        printf '%s\n' "La operación se detuvo; conserva el respaldo privado: $backup_dir" >&2
    fi
}
trap finish EXIT

check_origin() {
    local origin_url
    origin_url="$(git -C "$project_dir" remote get-url origin)" || fail 'Falta el remoto origin.'
    case "$origin_url" in
        https://github.com/renancarranza1920/lab_oncosavi|https://github.com/renancarranza1920/lab_oncosavi.git|git@github.com:renancarranza1920/lab_oncosavi.git|ssh://git@github.com/renancarranza1920/lab_oncosavi.git) ;;
        *) fail 'origin debe apuntar al repositorio renancarranza1920/lab_oncosavi de GitHub.' ;;
    esac
}

check_destination() {
    local relative="$1" current="$project_dir" component
    local -a components
    IFS='/' read -r -a components <<< "$relative"
    for component in "${components[@]}"; do
        current="$current/$component"
        [[ ! -L "$current" ]] || fail 'Un archivo versionado o una de sus carpetas es un enlace simbólico; revísalo antes de actualizar.'
    done
}

audit_tree() {
    local revision="$1" entry metadata file lower mode type object
    while IFS= read -r -d '' entry; do
        metadata="${entry%%$'\t'*}"
        file="${entry#*$'\t'}"
        [[ ! "$file" =~ [[:cntrl:]] ]] || fail 'La versión contiene un nombre de archivo con caracteres de control; no se instalará.'
        read -r mode type object <<< "$metadata"
        [[ "$type" == blob && ( "$mode" == 100644 || "$mode" == 100755 ) ]] || fail 'La versión contiene un enlace o submódulo que no puede instalarse.'
        lower="${file,,}"
        case "$lower" in
            .env|.env.*|*/.env|*/.env.*|lameri-publica.php|*/lameri-publica.php|config|config/*|configuracion|cache|cache/*|*/cache|*/cache/*|uploads|uploads/*|*/uploads|*/uploads/*|storage|storage/*|public_html|public_html/config|public_html/config/*|public_html/configuracion|public_html/configuracion/*|public_html/storage|public_html/storage/*|*.sql|*.sqlite|*.sqlite3|*.log|*.pem|*.key|*.p12|*.pfx|id_rsa*|id_ed25519*|*/id_rsa*|*/id_ed25519*)
                fail 'La versión intenta sustituir configuración privada, almacenamiento o archivos de datos; no se instalará.' ;;
            configuracion/.htaccess|configuracion/lameri-publica.php.example) ;;
            configuracion/*)
                fail 'La versión contiene archivos privados dentro de configuracion; no se instalará.' ;;
        esac
        check_destination "$file"
        if [[ "$lower" == public_html/api/*.php ]]; then
            git -C "$project_dir" show "$revision:$file" | "$php_bin" -l >/dev/null 2>&1 || fail 'Un archivo PHP de la API tiene un error de sintaxis; no se instalará.'
        fi
    done < <(git -C "$project_dir" ls-tree -rz --full-tree "$revision")
    git -C "$project_dir" cat-file -e "$revision:public_html/index.html" || fail 'Falta public_html/index.html en la versión.'
    git -C "$project_dir" cat-file -e "$revision:scripts/update-hostinger.sh" || fail 'Falta el actualizador en la versión.'
}

command -v git >/dev/null || fail 'Falta Git en SSH.'
command -v "$php_bin" >/dev/null || fail 'Falta PHP; configura LAMERI_WEB_PHP_BIN si tiene otra ruta.'
"$php_bin" -r 'exit(PHP_VERSION_ID >= 80200 ? 0 : 1);' || fail 'La web necesita PHP 8.2 o superior.'
[[ "$(git -C "$project_dir" rev-parse --show-toplevel 2>/dev/null)" == "$project_dir" ]] || fail 'Esta carpeta debe ser la raíz de la web conectada a Git.'
[[ -d "$project_dir/.git" && ! -L "$project_dir/.git" && ! -f "$project_dir/artisan" ]] || fail 'Este actualizador requiere un clon normal de la web pública, no la aplicación clínica ni un worktree.'
[[ "$(git -C "$project_dir" branch --show-current)" == lameri_web_publica ]] || fail 'La rama activa debe ser lameri_web_publica.'
git -C "$project_dir" diff --quiet && git -C "$project_dir" diff --cached --quiet || fail 'Hay cambios locales versionados. Revísalos con git status; no se sobrescribirán.'
check_origin
current_commit="$(git -C "$project_dir" rev-parse --verify 'HEAD^{commit}')"
audit_tree HEAD

# Se descarga una sola vez. El mismo SHA auditado es el que se instala.
git -C "$project_dir" fetch --no-tags origin lameri_web_publica
incoming_commit="$(git -C "$project_dir" rev-parse --verify 'FETCH_HEAD^{commit}')"
git -C "$project_dir" merge-base --is-ancestor HEAD "$incoming_commit" || fail 'La rama local divergió del remoto; no se hará reset ni una mezcla de cambios.'
audit_tree "$incoming_commit"
if [[ "$(git -C "$project_dir" rev-parse HEAD)" == "$incoming_commit" ]]; then
    printf '%s\n' 'La web Lameri ya está actualizada. La configuración y los archivos privados se conservaron.'
    exit 0
fi

# El respaldo completo queda fuera del dominio, accesible sólo por su propietario.
backup_candidate="$HOME/lameri-web-backups"
[[ ! -L "$backup_candidate" ]] || fail 'La carpeta de respaldos no puede ser un enlace simbólico.'
backup_base="$(realpath -m -- "$backup_candidate")"
[[ "$backup_base" != "$project_dir" && "$backup_base" != "$project_dir/"* ]] || fail 'La carpeta de respaldo debe estar fuera del proyecto web.'
mkdir -p -- "$backup_base"
chmod 700 -- "$backup_base"
backup_dir="$(umask 077; mktemp -d "$backup_base/web-$(date +%Y%m%d-%H%M%S)-XXXXXX")"
cp -a -- "$project_dir/." "$backup_dir/"
chmod 700 -- "$backup_dir"
printf '%s\n' "Respaldo privado completo: $backup_dir"

[[ "$(git -C "$project_dir" rev-parse HEAD)" == "$current_commit" && "$(git -C "$project_dir" branch --show-current)" == lameri_web_publica ]] || fail 'La rama cambió durante el respaldo; no se instalará la actualización.'
git -C "$project_dir" diff --quiet && git -C "$project_dir" diff --cached --quiet || fail 'Aparecieron cambios locales durante el respaldo; no se sobrescribirán.'
git -C "$project_dir" merge --ff-only --no-edit "$incoming_commit"
printf '%s\n' 'Web Lameri actualizada. La configuración, caché y archivos subidos se conservaron; no se modificó la base de datos.'
