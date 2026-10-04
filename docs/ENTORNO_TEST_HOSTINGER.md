# Instalar la rama test en Hostinger compartido

## Preparar el alojamiento

Selecciona PHP **8.3 o superior** tanto en hPanel como en SSH, con PDO MySQL,
mbstring, intl, GD, DOM/XML, cURL, ZIP y fileinfo. Necesitas Composer 2 y MySQL.
Los recursos de frontend ya están compilados en `public/build`: npm es opcional,
únicamente para volver a compilar después de editar CSS o JavaScript.

Descarga el ZIP de la rama `test` o clónala:

```bash
git clone --branch test --single-branch https://github.com/renancarranza1920/lab_oncosavi.git oncosavi
cd oncosavi
cp .env.example .env
```

En `.env` completa `APP_URL` (la URL temporal HTTPS de Hostinger sirve), `DB_HOST`,
`DB_DATABASE`, `DB_USERNAME` y `DB_PASSWORD`. Usa una base nueva para pruebas.
El ejemplo ya configura sesiones y caché en archivos, cola inmediata y correos
en el registro local. Conserva `APP_ENV=staging` y `TEST_DEMO_ENABLED=true`.

## Instalar

Estructura recomendada en la carpeta de ese sitio:

```text
carpeta-del-sitio/
├── oncosavi/       ← proyecto, .env, vendor y storage privados
└── public_html/    ← únicamente la entrada web y recursos públicos
```

Desde `oncosavi` ejecuta:

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --seed --force
php hostinger/publicar.php ../public_html
```

La carpeta `public_html` debe estar vacía o contener una publicación anterior
hecha con este script. Si hay un `index.php` de otro sitio, el script se detiene.
No subas la raíz completa del proyecto a `public_html`. El script copia solo
`public`, prepara `index.php` y enlaza los archivos persistentes de `storage`.
No copia `.env`, credenciales ni código privado; los PDFs se consultan mediante
las rutas autenticadas. También conserva la protección de reportes de `.htaccess`.

Si aparece «Hostinger tiene deshabilitado symlink en PHP», crea el enlace mediante
SSH, desde `oncosavi`, y vuelve a publicar:

```bash
mkdir -p ../public_html
ln -s ../oncosavi/storage/app/public ../public_html/storage
php hostinger/publicar.php ../public_html
```

Este enlace se crea solo una vez. No repitas `key:generate` ni el seeder para
resolver este problema. Los comandos `cat storage/...` también se ejecutan
desde `oncosavi`, no desde la carpeta del dominio.

Si tu alojamiento permite apuntar la raíz web directamente a `oncosavi/public`,
usa esa opción en lugar del script y ejecuta `php artisan storage:link`.
`storage` y `bootstrap/cache` deben ser escribibles por tu usuario PHP. No uses
permisos 777. En este alojamiento los archivos viven en esas carpetas; conserva
`storage` y `.env` al actualizar. No hay volúmenes ni contenedores.

## Qué prepara el seeder

`php artisan db:seed --force` (incluido en `migrate --seed`) instala:

- Catálogo completo de exámenes, pruebas, referencias y perfiles.
- `prueba.admin`, `prueba.recepcion` y `prueba.lab`, con sus permisos por acción.
- `soporte.superadmin`, con acceso de soporte y auditoría reservada.
- Acceso médico general `medicos`, activo en `/expediente`.
- 12 pacientes y 12 órdenes ficticias en los cinco estados; cuatro órdenes
  finalizadas con resultados y PDFs firmados de ejemplo.
- Firma, sello personal y sello institucional marcados SOLO DEMOSTRACIÓN.
- Teléfonos internacionales y varios números por paciente de ejemplo.

Se conserva el diseño de los PDFs, fechas y horas, ubicación de los tres sellos,
modo claro y oscuro, selección del teléfono de destino y WhatsApp manual.
No se envía correo ni WhatsApp durante la preparación. Los datos no tienen
validez clínica.

Las contraseñas son aleatorias, generadas en tu alojamiento, y se consultan por
SSH o con el administrador de archivos privado:

```bash
cat storage/app/private/usuarios-prueba.json
cat storage/app/private/soporte-superadmin.json
cat storage/app/private/portal-medicos-general.json
```

La bitácora general presenta «Ajuste de soporte técnico», sin acciones ni campos.
El detalle se conserva en Bitácora de soporte. En esta demo `prueba.admin` tiene
el acceso del propietario a ese detalle, además del usuario de soporte.

## Actualizaciones

Desde la carpeta del proyecto:

```bash
git pull --ff-only origin test
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan filament:clear-cached-components
php hostinger/publicar.php ../public_html
```

Si actualizas mediante ZIP, reemplaza el código y los recursos, conservando tu
`.env` y `storage`. No vuelvas a generar `APP_KEY`: se genera solo en la primera
instalación. Repetir `db:seed` después de una preparación completa no reinicia
los ejemplos ni las contraseñas. Si la base contiene datos previos sin una
preparación completa registrada, se detiene antes de cambiar esos datos.

Para volver a empezar utiliza otra base vacía y otro directorio de instalación.
La rama `main` y los datos reales de Oracle se mantienen separados.

Para editar el frontend y actualizar los recursos incluidos, en tu computadora
(o en el alojamiento si tiene Node 22+):

```bash
npm ci
npm run build
```

Después vuelve a publicar `public` con el script. No necesitas ejecutar `npm run dev`
ni mantener Node encendido en Hostinger.
