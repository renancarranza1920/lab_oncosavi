# Instalar Lameri en Hostinger

Este proyecto usa Laravel 12, PHP 8.3 y MySQL. La rama `lameri_new` conserva la funcionalidad de Oncosavi con la identidad de Laboratorio Clínico Merino. El paquete inicial contiene catálogo, perfiles, valores de referencia, roles y permisos. No incluye clientes, pacientes, órdenes, resultados, cotizaciones ni credenciales de producción.

## Una carpeta de proyecto

El ZIP se extrae en una única carpeta con la estructura del proyecto de Lameri:

```text
app.laboratorioclinicomerino.com/
├── app/
├── bootstrap/
├── config/
├── database/
├── lang/
├── public_html/      ← carpeta pública del dominio
│   ├── build/        ← CSS y JS compilados
│   ├── css/
│   ├── images/       ← logos de Merino
│   ├── js/
│   ├── vendor/
│   ├── .htaccess
│   └── index.php
├── resources/
├── routes/
├── scripts/
├── storage/          ← vacío al instalar
├── vendor/           ← dependencias PHP incluidas
├── .env.example
├── artisan
└── composer.json
```

Mantén esas carpetas juntas. `public_html/index.php` carga `../vendor/autoload.php` y `../bootstrap/app.php`; Laravel y Vite están configurados para usar `public_html`. La raíz tiene un `.htaccess` que impide servir código o credenciales si se selecciona por error como directorio público.

## Actualizar por Git desde Hostinger

La rama de producción de Lameri es **`lameri_new`** del repositorio **`renancarranza1920/lab_oncosavi`**. Los estilos y scripts compilados de `public_html/build` están versionados: Git trae el código y el frontend preparados. Hostinger no necesita Node ni subir archivos manualmente para cada cambio.

Una vez configurada una instalación Git, entra por SSH a la carpeta donde están `.git` y `artisan` y ejecuta:

```bash
cd /ruta/al/proyecto/app.laboratorioclinicomerino.com
bash scripts/update-hostinger.sh
```

El script comprueba la rama y que no hayas modificado archivos versionados, obtiene `lameri_new` con `git pull --ff-only`, pone el sistema en mantenimiento durante el cambio, aplica las migraciones pendientes y reconstruye las cachés. Ejecuta Composer de producción en cada actualización para regenerar el mapa de clases, incluso si no cambió `composer.lock`; si las dependencias ya están instaladas no vuelve a descargarlas. Retira únicamente los archivos de caché generados de Laravel y Filament antes de volver a cargar la aplicación. Conserva `.env`, `APP_KEY`, los usuarios, la base y los archivos de `storage`. No ejecuta seeders ni publica assets sobre los archivos de Git. También intenta salir de mantenimiento si falla un paso; conserva el mensaje del error para corregirlo antes de repetir la actualización.

Las tarjetas de órdenes finalizadas con un reporte guardado incluyen el icono
**Descargar PDF**, junto al visor. Descarga el archivo existente en PC, tablet
y celular con el permiso de descarga habitual, conservando sus firmas y sellos.

La terminal debe disponer de Git, PHP 8.3 o superior y **Composer 2**. Comprueba `php -v` y `composer --version`; si los ejecutables tienen otros nombres, puedes indicar sus rutas:

```bash
LAMERI_PHP_BIN=/ruta/a/php8.3 LAMERI_COMPOSER_BIN=/ruta/a/composer bash scripts/update-hostinger.sh
```

Composer también debe ejecutarse con el PHP compatible de tu hosting. Si Git detecta cambios locales o ramas divergentes, el script se detiene: revisa `git status`; no hace `reset`, no usa `--force` y no elimina esos cambios. Un sistema que ya estuviera en mantenimiento se conserva en ese estado y no se actualiza hasta que termines esa operación.

Los cambios de catálogo, referencias y permisos mediante seeders se aplican únicamente cuando la versión lo indique y quieras sincronizarlos:

```bash
php artisan db:seed --force
php artisan optimize
```

No vuelvas a ejecutar `key:generate` al actualizar una instalación existente.

## Conectar a Git una instalación que vino del ZIP

El ZIP de producción no incluye `.git`. Un `git pull` necesita conectar el proyecto una sola vez. Este procedimiento parte de una instalación del ZIP **de esta rama** y mantiene la carpeta actual del dominio y su `public_html`: no necesitas mover el sitio ni cambiar hPanel. Si vienes del Lameri antiguo, usa la instalación inicial con una base nueva descrita más abajo; este paso no transforma su base anterior.

Desde SSH, situado en la carpeta actual donde están `artisan`, `.env` y `public_html`:

```bash
LAMERI_GIT_TMP="$(mktemp -d)"
git clone --depth 1 --single-branch --branch lameri_new \
  https://github.com/renancarranza1920/lab_oncosavi.git "$LAMERI_GIT_TMP" &&
bash "$LAMERI_GIT_TMP/scripts/connect-hostinger-git.sh" "$PWD"
```

El conector verifica la instalación y guarda un respaldo completo privado en **`$HOME/lameri-backups`**, con acceso únicamente para tu usuario. Después copia los metadatos de Git y reemplaza exclusivamente los archivos de código versionados de `lameri_new`. Conserva `.env`, `APP_KEY`, MySQL y el almacenamiento actual, incluidas firmas, PDFs y cotizaciones. El sistema permanece en mantenimiento durante la conexión; Composer y las cachés se sincronizan antes de arrancar el código nuevo. No ejecuta seeders ni cambia claves. La carpeta de respaldo se muestra al terminar y queda fuera del directorio público y de Git.

La conexión sustituye el código de la instalación ZIP por el de la rama; cualquier ajuste local anterior queda guardado en el respaldo privado. El conector se detiene si el proyecto ya tiene `.git` o ya estaba en mantenimiento. Cuando termine correctamente, las siguientes actualizaciones se hacen **en esa misma carpeta**:

```bash
bash scripts/update-hostinger.sh
```

Si el repositorio es privado, configura una clave de lectura en GitHub/Hostinger y usa su URL SSH en `git clone`. El clon temporal puede conservarse como referencia; las siguientes actualizaciones no dependen de él.

### Alternativa: preparar otro clon y cambiar la ubicación

Si prefieres conservar la instalación ZIP completa en su ubicación anterior, también puedes preparar una carpeta nueva. Esta alternativa requiere cambiar el directorio público del dominio o mover las carpetas después; no hace falta para el procedimiento anterior.

Desde SSH, en la carpeta actual donde está `artisan`:

```bash
cd /ruta/al/proyecto/actual
LAMERI_OLD_DIR="$(pwd -P)"
LAMERI_GIT_DIR="${LAMERI_OLD_DIR}_git"

git clone --single-branch --branch lameri_new \
  https://github.com/renancarranza1920/lab_oncosavi.git "$LAMERI_GIT_DIR"

cp -p "$LAMERI_OLD_DIR/.env" "$LAMERI_GIT_DIR/.env"
cp -a "$LAMERI_OLD_DIR/vendor" "$LAMERI_GIT_DIR/vendor"
tar -C "$LAMERI_OLD_DIR/storage" --exclude='.gitignore' \
  --exclude='./framework/down' --exclude='./framework/maintenance.php' -cf - . \
  | tar -C "$LAMERI_GIT_DIR/storage" -xf -

cd "$LAMERI_GIT_DIR"
bash scripts/update-hostinger.sh
```

Usa una carpeta destino que aún no exista. El clon trae todos los archivos versionados y los assets compilados; `vendor` se copia del ZIP instalado y Composer lo sincroniza con la versión del repositorio y regenera su autoload. No copies `bootstrap/cache` del proyecto anterior. Conserva el mismo `.env`, clave y base de esta instalación: este paso no ejecuta la carga inicial ni borra los datos existentes. Si el repositorio es privado, configura una clave de lectura en GitHub/Hostinger y usa su URL SSH en `git clone`.

Antes de activar el clon, confirma que el script terminó correctamente y ejecuta `php artisan about --only=environment`. En hPanel cambia una sola vez el directorio público del dominio a **la carpeta nueva `/public_html`**; obtén su ruta exacta con:

```bash
printf '%s\n' "$LAMERI_GIT_DIR/public_html"
```

La carpeta anterior queda disponible como copia. Si el sitio ya registra datos durante este cambio inicial, pon la instalación anterior en mantenimiento y repite la copia de `storage` y el script antes de cambiar el directorio público; así incluyes los últimos archivos y regeneras las cachés. Cuando la ruta esté activa, abre `/admin` y comprueba el acceso. Las siguientes actualizaciones usan únicamente `bash scripts/update-hostinger.sh` dentro del clon.

Si tu plan obliga a conservar la ruta actual del dominio, puedes mantener ese nombre moviendo primero el proyecto anterior a una carpeta privada de respaldo y después el clon a la ruta original. Conserva ambos directorios; no los combines archivo por archivo. Después del movimiento, recrea el enlace público con la nueva ruta absoluta usando la sección siguiente y vuelve a ejecutar el script para recalcular las cachés.

## Enlace de storage por SSH en Hostinger

Si PHP tiene `exec` o `symlink` deshabilitados, usa la terminal SSH para crear el enlace. Desde la carpeta de `artisan`, con `PWD` apuntando a la ubicación definitiva del proyecto:

```bash
mkdir -p storage/app/public
if [ ! -e public_html/storage ] && [ ! -L public_html/storage ]; then
  ln -sT "$PWD/storage/app/public" "$PWD/public_html/storage"
fi
readlink -f public_html/storage
```

La última línea debe mostrar **la ruta de tu proyecto seguida de `/storage/app/public`**. El actualizador también crea ese enlace cuando falta y se detiene si encuentra una carpeta real o un enlace que apunte a otro lugar.

Si moviste el proyecto y el enlace antiguo apunta a la ruta anterior, reemplaza solamente el enlace, sin tocar sus archivos:

```bash
if [ -L public_html/storage ]; then
  unlink public_html/storage
  ln -sT "$PWD/storage/app/public" "$PWD/public_html/storage"
fi
```

Si `public_html/storage` es una carpeta real, conserva su contenido: cópialo a `storage/app/public`, mueve la carpeta anterior a un respaldo **fuera de `public_html`** y luego crea el enlace. No borres la carpeta para resolverlo. Los reportes se sirven mediante rutas autenticadas; el `.htaccess` bloquea `/storage/reportes`.

## Generar el ZIP desde el código

En una máquina con PHP 8.3, Composer 2, Node y Python 3, desde la raíz del repositorio:

```bash
python3 scripts/package-hostinger.py --output ../output/lameri_hostinger.zip
```

El comando compila el frontend y prepara `vendor` de producción en una carpeta temporal. Excluye `.env`, bases SQLite, dumps SQL, Git, `node_modules`, PDFs y archivos de almacenamiento del proyecto. El ZIP incluye solamente `.env.example` y el almacenamiento vacío. No necesita Node ni Composer en el servidor para instalarse desde ese ZIP. Para repetir el empaquetado después de ejecutar `npm run build`, puedes agregar `--skip-build`. Si la red bloquea los archivos ZIP de la API de GitHub, agrega `--prefer-source` para usar Git o su caché; el empaquetado también retira los repositorios internos de las dependencias.

## Preparar el hosting

1. En hPanel selecciona PHP **8.3** para el dominio. Habilita `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `dom`, `fileinfo`, `curl`, `intl`, `bcmath`, `gd` y `zip`.
2. Crea una base MySQL **nueva** y un usuario con acceso a esa base. Guarda el nombre completo y el usuario que muestra hPanel.
3. Sube y extrae `lameri_hostinger.zip` en la ubicación del proyecto de Lameri. El documento raíz del dominio debe ser la carpeta **`app.laboratorioclinicomerino.com/public_html`**, siguiendo la estructura anterior. Si usas la ubicación existente, coloca `app`, `bootstrap`, `vendor`, etc. junto a su `public_html` existente. El dominio debe servir directamente el contenido de `public_html`, sin `/public_html` en las URLs.
4. Activa HTTPS para `app.laboratorioclinicomerino.com`.

Si el plan de Hostinger no permite elegir el directorio público, usa la estructura existente del dominio: coloca las carpetas privadas en el padre de su `public_html` y los archivos públicos dentro de `public_html`. Evita poner `app`, `vendor` o `.env` dentro de la carpeta pública.

## Configurar e inicializar

Abre la terminal SSH y entra a la raíz donde está `artisan`:

```bash
cd /ruta/al/proyecto/app.laboratorioclinicomerino.com
cp .env.example .env
```

Edita `.env` con el administrador de archivos o tu editor SSH. Configura:

```dotenv
APP_URL=https://app.laboratorioclinicomerino.com
APP_ENV=production
APP_DEBUG=false
DB_HOST=127.0.0.1
DB_DATABASE=nombre_completo_de_hpanel
DB_USERNAME=usuario_completo_de_hpanel
DB_PASSWORD="contraseña_de_mysql"
LAMERI_ADMIN_NICKNAME=lameri
LAMERI_ADMIN_NAME="Administración Lameri"
LAMERI_ADMIN_EMAIL=tu_correo_real
LAMERI_ADMIN_PASSWORD="una_contraseña_nueva_y_larga"
LABORATORIO_CORREO=correo_del_laboratorio
```

Confirma `DB_HOST` con hPanel si muestra otro servidor. `LAMERI_ADMIN_EMAIL` debe ser un correo válido. Las contraseñas con espacios o símbolos deben ir entre comillas. Deja `SESSION_SECURE_COOKIE=true` usando HTTPS; la instalación usa sesiones en MySQL, caché de archivos y tareas síncronas para funcionar sin procesos de cola.

Para enviar correos de recuperación de contraseña, configura un servidor SMTP real: cambia `MAIL_MAILER=smtp` y completa `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_SCHEME` y `MAIL_FROM_ADDRESS` según tu proveedor. El valor inicial `MAIL_MAILER=log` registra los mensajes localmente y no los envía.

Después ejecuta con el PHP 8.3 de tu hosting:

```bash
php -v
php artisan key:generate --force
php artisan migrate --force
php artisan db:seed --force
ln -sT "$PWD/storage/app/public" "$PWD/public_html/storage"
php artisan optimize
chmod 600 .env
chmod -R ug+rwX storage bootstrap/cache
```

Usa la base nueva para esta inicialización. El seeder instala la estructura clínica y el administrador definido en `.env`; no importa ningún dump ni historial de clientes u órdenes. Puedes repetir `db:seed` para sincronizar el catálogo y permisos: conserva las credenciales del administrador existente.

El comando `ln -sT` crea `public_html/storage` desde SSH, sin depender de las funciones deshabilitadas de PHP. Si ya existe, sigue la sección **Enlace de storage por SSH en Hostinger** antes de continuar. Los reportes clínicos se descargan mediante rutas autenticadas; el `.htaccess` bloquea su lectura directa por `/storage/reportes`. Las firmas y sellos cargados desde el panel se guardan en el disco público de Laravel.

## Entrar al sistema y al portal médico

- Administración: `https://app.laboratorioclinicomerino.com/admin`, usando el nickname y contraseña configurados para el administrador.
- Portal médico: `https://app.laboratorioclinicomerino.com/expediente`.

El catálogo y el registro de acceso médico general se preparan con el seeder, pero su contraseña se crea durante la instalación:

```bash
php artisan lameri:portal-medicos-general
```

La contraseña se guarda en `storage/app/private/portal-medicos-general.json`; consúltala desde SSH o el administrador de archivos privado. También puedes administrarla desde **Médicos → Acceso médico general**. Los accesos individuales se configuran en el módulo Médicos y mantienen sus permisos para consultar expedientes y PDFs.

Una vez creado el administrador, retira el valor de `LAMERI_ADMIN_PASSWORD` de `.env` y vuelve a ejecutar `php artisan config:cache`; su contraseña ya está almacenada como hash en MySQL.

## Comprobar la instalación

Abre `/admin`, inicia sesión y revisa exámenes, perfiles, referencias, roles y Médicos. El tablero de órdenes y los listados de pacientes deben estar vacíos. Abre `/expediente` y comprueba que los estilos, logos y modo oscuro de Merino cargan correctamente. Genera una orden de prueba y revisa captura de resultados, firma, PDF y acceso médico antes de empezar a registrar pacientes reales; elimina esa prueba desde el sistema.

Para revisar un error, consulta `storage/logs/laravel.log` y verifica PHP 8.3, credenciales MySQL, `APP_KEY`, el enlace `public_html/storage` y permisos de `storage`/`bootstrap/cache`. Mantén `APP_DEBUG=false` en producción.
