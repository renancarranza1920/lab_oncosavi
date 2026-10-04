# Rama test: instalación independiente sin Docker

Esta rama parte de `main` con el PDF actualizado y el acceso identificado de soporte.
Añade únicamente la preparación de datos ficticios para un alojamiento separado.
No conecta con Oracle ni incorpora datos reales, archivos `.env`, claves o contraseñas
de la instalación actual. Los Dockerfiles se conservan como parte del proyecto, pero
esta instalación no los necesita.

## Requisitos

PHP 8.3 o superior con PDO MySQL, mbstring, intl, GD, DOM/XML, cURL, ZIP y fileinfo;
MySQL compatible; Composer 2. Para compilar el frontend, Node 22 o superior y npm.
La compilación puede realizarse en tu computadora y luego subir `public/build`.
La web necesita una URL: usa la dirección temporal que el alojamiento te facilite,
sin necesidad de registrar un dominio nuevo.

## Instalación inicial

1. En GitHub selecciona la rama **test** y descarga el ZIP, o clona con
   `git clone --branch test --single-branch https://github.com/renancarranza1920/lab_oncosavi.git`.
2. Crea una **base nueva y vacía** y un usuario MySQL exclusivo para pruebas.
3. En una carpeta nueva copia `.env.hostinger-test.example` a `.env` y completa
   `APP_URL`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME` y `DB_PASSWORD` con los valores
   de ese alojamiento. Conserva `APP_ENV=staging` y `TEST_DEMO_ENABLED=true`.
4. Desde la carpeta del proyecto, con PHP 8.3 o superior ejecuta:

```bash
composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan oncosavi:preparar-test
php artisan optimize:clear
php artisan filament:clear-cached-components
```

Si no tienes Node en Hostinger, ejecuta `npm ci` y `npm run build` en tu computadora
y sube `public/build`, incluyendo `manifest.json`. Si no tienes Composer en el
alojamiento, prepara `vendor` con PHP 8.3 y sube esa carpeta también. Artisan debe
ejecutarse con la versión de PHP del proyecto.

5. Configura la raíz pública del sitio para que apunte a **`public`**, no a la raíz
   del proyecto. `storage` y `bootstrap/cache` deben ser escribibles por el usuario
   de PHP del alojamiento. En Hostinger son carpetas del proyecto: no hay volumen
   Docker. Conserva `storage` entre despliegues para mantener PDFs, sellos y accesos.

## Datos incluidos

`oncosavi:preparar-test` crea el catálogo de ONCOSAVI, tres usuarios de prueba,
un superadministrador de soporte identificado, 12 pacientes ficticios, 12 órdenes
repartidas entre todos los estados, resultados y 4 PDFs de órdenes finalizadas.
También crea un médico ficticio y PNG de sello personal, firma y sello institucional
marcados **SOLO DEMOSTRACIÓN**. Los correos usan `example.invalid`; los teléfonos usan
el rango de ejemplo estadounidense 202-555-01xx. No se envía ningún mensaje al preparar.

| Usuario | Rol |
| --- | --- |
| `prueba.admin` | Administrador |
| `prueba.recepcion` | Recepción |
| `prueba.lab` | Laboratorista |
| `soporte.superadmin` | Soporte identificado, con auditoría |

Las contraseñas se generan aleatoriamente, no están en Git. Léelas por SSH o en el
administrador de archivos **privado** del alojamiento:

```bash
cat storage/app/private/usuarios-prueba.json
cat storage/app/private/soporte-superadmin.json
```

El portal general `medicos` puede habilitarse para probar los expedientes con:

```bash
php artisan oncosavi:portal-medicos-general
cat storage/app/private/portal-medicos-general.json
```

En `/admin/login` usa uno de los usuarios de prueba. Recepción no puede ingresar
resultados y Laboratorista tiene firma y sello de ejemplo en su perfil. Los permisos
son los mismos que en `main`. El superadministrador se muestra en Usuarios y Roles. Sus operaciones figuran como
«Ajuste de soporte técnico» en la bitácora general; el detalle está reservado en
Bitácora de soporte. Para probar el acceso del propietario con `prueba.admin`,
ejecuta `php artisan oncosavi:autorizar-bitacora-soporte prueba.admin`.

## Repetición y separación

El comando exige el entorno `staging` (o `testing` para PHPUnit), la bandera de demo
y tablas vacías. Si encuentra usuarios, pacientes, catálogo o un sello institucional,
se detiene **antes** de crear datos. Repetirlo no borra ni reinicia información.
Para empezar otra prueba usa otra base vacía y otro `storage`. No ejecutes
`migrate:fresh`, `db:seed` ni comandos de prueba sobre la base de Oracle.

La creación de datos es explícita: instalar, arrancar o migrar no ejecuta el demo.
No fusionar la preparación de esta rama en `main`; los cambios funcionales compartidos
se incorporan primero en `main` y después se pueden llevar a `test`.
