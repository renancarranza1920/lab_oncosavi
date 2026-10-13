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
php artisan storage:link
php artisan optimize
chmod 600 .env
chmod -R ug+rwX storage bootstrap/cache
```

Usa la base nueva para esta inicialización. El seeder instala la estructura clínica y el administrador definido en `.env`; no importa ningún dump ni historial de clientes u órdenes. Puedes repetir `db:seed` para sincronizar el catálogo y permisos: conserva las credenciales del administrador existente.

El enlace `storage:link` crea `public_html/storage`. Los reportes clínicos se descargan mediante rutas autenticadas; el `.htaccess` bloquea su lectura directa por `/storage/reportes`. Las firmas y sellos cargados desde el panel se guardan en el disco público de Laravel.

Si el hosting rechaza los enlaces simbólicos, solicita que habiliten `symlink` para PHP o créalo por SSH con `ln -s ../storage/app/public public_html/storage`, desde la raíz del proyecto. No copies reportes clínicos al directorio público para sustituir ese enlace.

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
