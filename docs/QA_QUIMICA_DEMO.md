# Control de Calidad Interno

El módulo conserva su dirección independiente:
<https://applab.oncosavi.com/qa-quimica>. No aparece en el menú principal.
Ahora requiere iniciar sesión con un usuario de Oncosavi y tener los permisos
correspondientes. El antiguo acceso sin contraseña queda cerrado.

## Las cinco pantallas

1. **Resumen:** últimos controles registrados y gráficos de las pruebas.
2. **Mantenimiento:** áreas y equipos de química o hematología, productos de
   control, lotes, pruebas propias del módulo y configuraciones de niveles con
   sus valores esperados, mínimos y máximos.
3. **Registro de Control de Calidad Interno:** selección del área y equipo,
   fecha y hora automáticas, laboratorista, lecturas y observaciones opcionales.
   La sesión identifica a quien guarda el registro. Un permiso adicional permite
   asignar otro laboratorista.
4. **Gráficos:** consulta por equipo, configuración y prueba. Se dibuja cada nivel
   configurado; una configuración de tres niveles muestra tres gráficos.
5. **Historial:** búsqueda de controles, revisiones, repeticiones, correcciones,
   ediciones y registros eliminados, con descarga PDF y CSV según los permisos.

La cantidad de niveles es configurable: no está limitada a bajo, medio y alto.
Los nombres se escriben en Mantenimiento y cada prueba guarda los valores de
control de cada nivel. Los campos numéricos aceptan coma o punto decimal.

## Roles y permisos

En **Roles**, abre o crea un rol y selecciona la pestaña **Control de Calidad
Interno**, situada junto a **Permisos personalizados**.

- **Acceso y consultas:** acceder al módulo y ver el resumen, consultar gráficos,
  consultar historial y descargar PDF/CSV.
- **Mantenimiento:** administrar áreas, equipos, productos, lotes, pruebas y
  niveles.
- **Registros y revisión:** registrar, editar, eliminar, asignar otro
  laboratorista y revisar controles.

El permiso de acceso al módulo es necesario junto con cada permiso de acción.
Los controles se aplican a las direcciones y a las operaciones del servidor,
además de los botones visibles. El usuario necesita también su acceso habitual
al panel de Oncosavi.

La actualización registra estos permisos de forma aditiva. Los administradores
existentes reciben el acceso inicial; los demás roles se habilitan desde la nueva
pestaña. No se reemplazan los permisos existentes de los roles.

## Actualización en Oracle

Ejecuta el bloque completo por SSH. Construye la imagen antes de activar el
mantenimiento y recrea únicamente el contenedor de la aplicación.

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
  docker compose exec -T --user www-data app php artisan migrate --path=database/migrations/2026_11_10_000001_add_control_calidad_interno_permissions.php --force
  docker compose exec -T --user www-data app php artisan oncosavi:control-calidad-preparar
  docker compose exec -T --user www-data app php artisan permission:cache-reset
)
```

La migración indicada registra únicamente los permisos del nuevo módulo. No
regenera catálogos clínicos, usuarios, pacientes ni órdenes. La preparación
actualiza la estructura privada de Control de Calidad Interno y conserva los
controles, lecturas, lotes y límites anteriores. No ejecutes `db:seed`,
`migrate:fresh` ni `docker compose down -v` para esta actualización.

Si un paso falla, el bloque intenta sacar de mantenimiento la aplicación que él
mismo puso en ese estado. Conserva el error para resolverlo. Si Git indica cambios
locales o una rama divergente, revisa `git status`; no descartes esos archivos.

Después entra con el administrador a:
<https://applab.oncosavi.com/admin/shield/roles> para conceder los permisos al rol
que utilizará Control de Calidad Interno. Luego abre la URL independiente del
módulo.

## Preparar los controles

1. En **Mantenimiento**, identifica el área y el equipo.
2. Registra el producto de control y sus lotes: nombre, número y caducidad.
3. Selecciona las pruebas del catálogo o crea las pruebas propias que necesites.
   Estas pruebas nuevas pertenecen al módulo; no modifican las pruebas de
   resultados de pacientes.
4. Crea los niveles y escribe sus nombres. Identifica el lote utilizado en cada
   nivel, la apertura del frasco y su estabilidad después de abierto.
5. Para cada prueba, ingresa el método, la unidad y el valor esperado, mínimo y
   máximo de cada nivel, tomados de la hoja del control correspondiente.
6. Guarda la configuración. Cada cambio crea una versión; los controles ya
   registrados conservan los límites que se utilizaron al medirlos.

Los ejemplos se identifican con el rótulo **Datos de demostración**. No son
mediciones reales del laboratorio ni sustituyen la hoja del lote utilizado.

## Registrar y consultar

Selecciona el área y equipo en **Registro de Control de Calidad Interno**. El
sistema coloca la fecha, la hora y el usuario de la sesión. Copia las lecturas de
la máquina y añade una observación si corresponde. Un cero es una lectura real;
una lectura no realizada se deja vacía y se documenta su motivo.

Se mantiene la rutina de lunes, miércoles y viernes, junto con la posibilidad de
registrar controles adicionales. Las calibraciones y mantenimientos físicos
pueden documentarse en la sección de eventos dentro de Mantenimiento.

La edición conserva una versión anterior y registra al usuario que realizó el
cambio. La eliminación retira el control de las consultas activas y conserva su
antecedente en el historial con el usuario, la fecha y el motivo. No borra los
datos originales de forma definitiva.

Los gráficos separan las configuraciones y sus lotes. Presentan los valores
esperados y límites guardados; las revisiones del responsable se registran por
separado de la comparación numérica.

## Almacenamiento y mantenimiento técnico

Los datos del módulo permanecen en
`storage/app/private/qa-quimica/demo.sqlite`, dentro del volumen persistente
`laravel_storage`. Se conservó esa ubicación para actualizar las instalaciones
anteriores sin mover ni perder su información.

La sesión es ahora la de Oncosavi: se consulta el usuario y sus permisos en la
base principal. Las mediciones y configuraciones de calidad se guardan en la
base privada. No se publican en `public/storage`.

Para actualizar la copia del catálogo o reactivar el módulo:

```bash
cd ~/lab_oncosavi
docker compose exec -T --user www-data app php artisan oncosavi:control-calidad-preparar
```

Para desactivarlo sin borrar registros:

```bash
cd ~/lab_oncosavi
docker compose exec -T --user www-data app php artisan oncosavi:control-calidad-desactivar
```

Los nombres antiguos `oncosavi:qa-demo-preparar` y
`oncosavi:qa-demo-desactivar` siguen funcionando.

Para desarrollo local sin consultar el catálogo clínico:

```bash
php artisan oncosavi:control-calidad-preparar --catalogo-ejemplo
```

En Oracle utiliza el comando sin esa opción para importar las pruebas de química
y hematología del laboratorio. La preparación conserva las pruebas creadas
manualmente dentro de Control de Calidad Interno.
