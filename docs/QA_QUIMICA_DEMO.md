# Control de calidad de química: prueba independiente

El módulo de prueba se abre directamente en estas direcciones:

- Resumen: <https://applab.oncosavi.com/qa-quimica>
- Configuración: <https://applab.oncosavi.com/qa-quimica/configuracion>
- Captura del laboratorista: <https://applab.oncosavi.com/qa-quimica/mediciones/nueva>
- Gráficas: <https://applab.oncosavi.com/qa-quimica/graficas>
- Historial: <https://applab.oncosavi.com/qa-quimica/mediciones>
- Eventos del equipo: <https://applab.oncosavi.com/qa-quimica/eventos>

Son direcciones de revisión sin contraseña. El acceso no aparece en el menú
principal de Oncosavi. Quien abra la dirección puede usar la configuración y
registrar datos de demostración. Los nombres de quien registra o revisa se
escriben manualmente y no representan identidades verificadas por una sesión de
usuario.

## Separación del laboratorio en producción

Las configuraciones y mediciones de esta prueba se guardan en
`storage/app/private/qa-quimica/demo.sqlite`, dentro del volumen persistente
`laravel_storage`. Su sesión y caché también son propias de la prueba. El archivo
no se publica en `public` ni en el enlace `public/storage`.

La preparación importa una copia del catálogo de pruebas: identificación,
nombre, examen, área y unidad. Esa lectura no importa pacientes, órdenes,
resultados, usuarios ni las referencias clínicas para utilizarlas como límites
de control. Las peticiones web del módulo consultan su base separada.

La preparación crea las tablas de la prueba en SQLite. No se ejecutan
migraciones, seeders ni cambios en la base clínica. Tampoco se cambian `DB_*`,
`APP_KEY`, el contenedor `db`, sus volúmenes ni la configuración de Caddy.

La imagen incorpora las extensiones SQLite. Al actualizar es necesario
reconstruir y recrear **solo `app`**, como en las actualizaciones anteriores.
Ese paso supone el reinicio habitual de la aplicación; la prueba no necesita
otro dominio, puerto público o contenedor de MySQL.

## Publicar la versión en Oracle

Ejecuta este bloque por SSH en Oracle, cuando los cambios ya estén disponibles
en `main`. El bloque rechaza una instalación que ya estaba en mantenimiento;
solo retira el mantenimiento que él mismo activa. Construye la imagen antes de
poner la aplicación en mantenimiento y conserva el servicio `db` en ejecución.

```bash
(
  set -e
  cd ~/lab_oncosavi
  git switch main
  git pull --ff-only origin main

  if docker compose exec -T --user www-data app php -r 'exit(is_file("storage/framework/down") ? 0 : 1);'; then
    echo 'Oncosavi ya estaba en mantenimiento. Revisa ese estado antes de actualizar.' >&2
    exit 1
  fi

  docker compose build app
  docker compose exec -T --user www-data app php artisan down
  trap 'docker compose exec -T --user www-data app php artisan up' EXIT

  docker compose up -d --no-deps --force-recreate app
  docker compose exec -T --user www-data app php artisan optimize:clear
  docker compose exec -T --user www-data app php artisan filament:clear-cached-components
  docker compose exec -T --user www-data app php artisan oncosavi:qa-demo-preparar
)
```

La preparación consulta el catálogo real del laboratorio en modo de lectura,
crea la base privada si falta, añade los ejemplos iniciales y activa la ruta.
Al terminar imprime las direcciones de configuración y de captura. Repetir la
preparación conserva las mediciones que ya hayan registrado: no es un reinicio
de la prueba.

Si Git informa de archivos locales modificados o de una rama divergente,
revisa `git status` y conserva ese mensaje. No uses `git reset --hard` para
resolverlo sin revisar esos cambios.

Si un paso falla, el bloque intenta sacar de mantenimiento la aplicación que
acababa de poner en ese estado. Conserva el error para corregir la causa.
No ejecutes `docker compose down -v`, `migrate:fresh` ni `db:seed` para instalar
este módulo.

## Qué probar con el dueño y el laboratorista

1. En **Configuración**, revisa el equipo, selecciona las pruebas del catálogo y
   prepara los lotes bajo, medio y alto. Los objetivos, mínimos y máximos de los
   ejemplos son ficticios: los operativos deben confirmarse con el responsable
   y la hoja vigente del control.
2. En **Registrar mediciones**, elige la configuración y copia las lecturas de
   la máquina. El nombre, fecha y hora de medición se registran expresamente.
   Se admiten decimales con coma o punto y el cero es un resultado válido.
3. Guarda un borrador o presenta las mediciones. Una lectura no realizada debe
   quedar identificada como tal; no se rellena con cero ni con el objetivo.
4. Abre las **Gráficas** de una prueba: aparecen sus tres niveles por separado,
   con el lote y los límites de esa versión. No se inventan desviaciones
   estándar ni reglas estadísticas a partir de un intervalo del fabricante.
5. Prueba una lectura fuera de límites, su revisión y una repetición. La
   repetición queda relacionada con el registro original y conserva el valor
   inicial. Una corrección documenta qué se corrigió sin borrar el antecedente.
6. Registra un evento de calibración o mantenimiento si corresponde y relaciona
   sus controles anteriores o posteriores. El módulo documenta el evento: la
   acción física sigue realizándose en la máquina.
7. Consulta el historial y descarga el informe PDF o la tabla CSV compatible con
   Excel. Los archivos se generan con los registros de esta prueba.

El calendario de lunes, miércoles y viernes corresponde al control de calidad.
Se pueden documentar mediciones adicionales cuando el procedimiento lo
requiera. Guardar un resultado dentro de límites y revisar un registro son
acciones distintas; ninguna de ellas ejecuta una calibración del equipo ni
autoriza automáticamente resultados de pacientes.

Al cambiar configuración se conserva una versión anterior para reconstruir los
controles históricos con sus objetivos y límites originales. La apertura y la
estabilidad de cada frasco se consideran junto con la caducidad del lote; se
aplica la fecha límite más próxima.

Las pantallas y el PDF utilizan textos de uso diario para el laboratorista. Los
datos simulados se identifican con el rótulo «Datos de demostración». Las
configuraciones de ejemplo instaladas con la primera versión también muestran
los textos actualizados, sin reescribir sus mediciones ni los límites históricos.
No es necesario borrar registros ni volver a preparar los ejemplos.

## Actualizar el catálogo o reactivar la prueba

Después de añadir pruebas al catálogo principal, vuelve a ejecutar:

```bash
cd ~/lab_oncosavi
docker compose exec -T --user www-data app php artisan oncosavi:qa-demo-preparar
```

La copia de catálogo se actualiza sin borrar las mediciones anteriores. El
comando activa también el acceso si estaba desactivado. Las configuraciones y
registros anteriores conservan sus datos históricos.

## Cerrar temporalmente el acceso público

```bash
cd ~/lab_oncosavi
docker compose exec -T --user www-data app php artisan oncosavi:qa-demo-desactivar
```

Las rutas dejan de estar disponibles y los datos se conservan. El acceso
principal `/admin` y las operaciones clínicas continúan por sus rutas habituales.

## Datos persistentes y copia de respaldo

Conserva todo `storage/app/private/qa-quimica`, incluido su archivo de activación
cuando deba mantenerse disponible. Los directorios privados se mantienen con
permisos `0700` y los archivos con `0600`, pertenecientes a `www-data` dentro del
contenedor. No copies la base ni los respaldos a `public/storage`.

Para guardar una copia consistente de SQLite, utiliza `VACUUM INTO` a través
de PHP. La copia considera una base que pueda tener un diario WAL activo; no se
hace una copia aislada del archivo mientras se está escribiendo.

```bash
cd ~/lab_oncosavi
docker compose exec -T --user www-data app php <<'PHP'
<?php
$qaBackupRoot = '/var/www/html/storage/app/private/qa-quimica';
$qaDatabase = $qaBackupRoot . '/demo.sqlite';
if (!is_file($qaDatabase)) {
    fwrite(STDERR, "Todavía no existe la base de la prueba.\n");
    exit(1);
}
$qaBackupDirectory = $qaBackupRoot . '/backups';
if (!is_dir($qaBackupDirectory) && !mkdir($qaBackupDirectory, 0700, true)) {
    throw new RuntimeException('No se pudo crear la carpeta de respaldo.');
}
chmod($qaBackupDirectory, 0700);
$qaDestination = $qaBackupDirectory . '/qa-' . gmdate('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.sqlite';
$qaConnection = new PDO('sqlite:' . $qaDatabase);
$qaConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$qaConnection->exec('PRAGMA busy_timeout = 5000');
$qaConnection->exec('VACUUM INTO ' . $qaConnection->quote($qaDestination));
chmod($qaDestination, 0600);
echo "Respaldo privado creado: $qaDestination\n";
PHP
```

## Preparación local sin la base clínica

En desarrollo o pruebas automatizadas se puede preparar el catálogo ilustrativo:

```bash
php artisan oncosavi:qa-demo-preparar --catalogo-ejemplo
```

Esta opción no necesita importar el catálogo clínico. En Oracle utiliza la
preparación normal para que las pruebas seleccionables procedan del catálogo
del laboratorio.
