# Control de Calidad Interno

Dirección: <https://applab.oncosavi.com/admin/control-calidad-interno>.

El módulo está integrado en la barra lateral general de Oncosavi. Justo después
de **Gestión de Laboratorio**, el grupo **QA · Control de calidad** reúne estas
cinco pantallas. Su encabezado y su flecha permiten mostrar u ocultar las
opciones sin cambiar de pantalla. El navegador recuerda si se dejó abierto o
cerrado. Al entrar en ellas se conservan el menú del laboratorio y la misma sesión:

1. **Resumen:** últimos registros y gráficos.
2. **Mantenimiento:** tres apartados: Áreas, Productos y Pruebas. Los productos
   reúnen el nombre del control, el número de lote y su caducidad.
3. **Registro de Control de Calidad Interno:** selección del área, resultados y
   observaciones opcionales. La fecha se muestra después de guardar. Incluye
   edición y eliminación de registros según el permiso asignado.
4. **Gráficos:** consulta por área y prueba con períodos Diario, Semanal o Mensual,
   con una gráfica por nivel.
5. **Historial:** consulta de registros con filtros Área, Laboratorista, Desde y
   Hasta. Los registros eliminados no se muestran.

Los formularios, tablas, filtros, modales y gráficos son componentes nativos de
Filament. Los niveles se agregan manualmente en cada prueba; una prueba de tres
niveles muestra tres gráficos y una de cuatro muestra cuatro.

Los enlaces anteriores de `/control-calidad-interno` y de sus cinco pantallas
redirigen a las nuevas direcciones dentro de `/admin/control-calidad-interno`,
conservando los parámetros de consulta. Los enlaces privados de supervisión y
las exportaciones PDF conservan sus direcciones.

La integración de QA en la barra lateral general no requiere nuevas migraciones
ni vaciar datos. Conserva la configuración, las mediciones y los permisos de QA.

## Actualizar en Oracle

Ejecuta el bloque completo por SSH:

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
  docker compose exec -T --user www-data app php artisan migrate --path=database/migrations/2026_11_11_000001_create_internal_quality_control_tables.php --force
  docker compose exec -T --user www-data app php artisan migrate --path=database/migrations/2026_11_11_000002_remove_unrequested_control_calidad_permissions.php --force
  docker compose exec -T --user www-data app php artisan migrate --path=database/migrations/2026_11_11_000003_add_control_calidad_export_permission.php --force
  docker compose exec -T --user www-data app php artisan migrate --path=database/migrations/2026_11_12_000001_enable_quality_control_for_lab_roles.php --force
  docker compose exec -T --user www-data app php artisan migrate --path=database/migrations/2026_11_12_000002_add_granular_quality_control_maintenance_permissions.php --force
  docker compose exec -T --user www-data app php artisan permission:cache-reset
)
```

La primera migración crea las tablas `cci_*` del módulo en la base de datos de
Oncosavi. La segunda registra los siete permisos y retira los permisos de las
funciones descartadas de la implementación anterior. Los permisos de los demás
módulos se conservan. La tercera agrega el permiso de exportación PDF, que se
concede inicialmente a los administradores existentes.
La cuarta habilita los permisos completos de QA a los roles existentes Admin,
Laboratorista y Superadmin. No elimina datos ni reemplaza permisos clínicos.
La quinta separa las acciones de mantenimiento y conserva una sola vez los
accesos de quienes ya tenían la administración completa, incluidos los permisos
asignados directamente. Los usuarios de un rol siguen heredando sus permisos;
no reciben copias directas de las asignaciones de ese rol.

La instalación comienza sin registros ni catálogos de demostración. Los datos se
configuran desde **Mantenimiento**. No se ejecutan seeders ni se modifican las
órdenes, pacientes o resultados clínicos.

La implementación anterior y sus rutas `/qa-quimica` se retiraron. Sus archivos
de almacenamiento no se consultan ni se importan en este panel nuevo. Los
comandos antiguos `oncosavi:qa-demo-preparar` y sus alias también se retiraron;
esta versión se instala con las cinco migraciones indicadas.

## Configurar los permisos

Abre **Roles** en Oncosavi y selecciona la pestaña **Control de Calidad Interno**,
junto a **Permisos personalizados**:

- Acceder al módulo y ver el resumen.
- Consultar mantenimiento.
- Áreas y equipos: agregar, editar o eliminar, de forma independiente.
- Productos y lotes: agregar, editar o eliminar, de forma independiente.
- Pruebas y niveles: agregar, editar o eliminar, de forma independiente.
- Registrar controles.
- Editar registros.
- Eliminar registros.
- Consultar gráficos.
- Consultar historial.
- Exportar PDF de datos y gráficos.

El permiso de acceso es necesario junto con cada permiso de acción. Los
roles Admin, Laboratorista y Superadmin reciben los permisos iniciales; los demás roles se
habilitan desde esta pestaña. Las acciones se verifican también en el servidor.

Hay 17 permisos de QA. El permiso **Consultar mantenimiento** permite abrir sus
tres apartados, pero no autoriza cambios por sí solo. Además del acceso al módulo
y de consulta, marca únicamente las acciones que necesite cada rol. Por ejemplo,
puedes permitir editar productos sin permitir agregar o eliminar productos, o
permitir administrar áreas sin permitir cambiar pruebas.

Laboratorista, Admin y Superadmin conservan todos los permisos iniciales. Después
de actualizar, el administrador puede desmarcar cualquier acción desde Roles;
abrir las pantallas no vuelve a conceder los permisos retirados. Los permisos de
edición y eliminación de **registros** son independientes de los de mantenimiento.

Eliminar una configuración requiere su permiso y confirmación. No se permite
eliminar áreas con pruebas o controles, lotes utilizados por niveles, ni pruebas
con mediciones guardadas, incluso si esas mediciones se eliminaron del historial.
Los catálogos y mediciones existentes no se borran al aplicar esta actualización.

## Empezar a utilizarlo

1. En **Mantenimiento → Áreas**, escribe el nombre del área y su equipo. Puedes
   crear las áreas que necesites, por ejemplo Química, Hematología o Inmunología.
2. En **Productos**, registra el nombre del control, número de lote y caducidad
   en el mismo formulario. Puedes agregar más lotes con el mismo producto.
3. Crea la prueba, indica su unidad y agrega los niveles. Para cada nivel,
   selecciona el lote y escribe el mínimo y el máximo. El valor esperado se
   calcula automáticamente como `(mínimo + máximo) / 2` y no se edita manualmente.
   El nombre del nivel ofrece Bajo, Medio y Alto; también puedes escribir otro
   nombre o conservar los nombres personalizados que ya utilizas.
4. En **Registro**, selecciona el área y equipo. Aparecerán las pruebas y niveles
   configurados. La fecha y la hora se asignan y muestran al guardar; el usuario
   de la sesión se registra automáticamente sin aparecer en el formulario.
   Las lecturas se distribuyen en tres columnas en computadora, dos en pantallas
   medianas y una en móvil, conservando la unidad y los valores de cada nivel.
5. Completa los resultados y, si corresponde, las observaciones. Se admiten
   decimales con coma o punto; el cero es un resultado válido.
6. En **Gráficos**, elige Diario para el día actual, Semanal para la semana actual
   de lunes a domingo, o Mensual para el mes actual. Se usa la hora de El Salvador.
   La franja sombreada muestra el rango entre el mínimo y el máximo. Las
   mediciones fuera del rango aparecen como una X roja. El botón **Ampliar**
   abre cualquier gráfico a pantalla completa; **Cerrar** o Escape devuelve
   la vista normal.
   Cambiar los valores de control en
   Mantenimiento no altera los límites que ya quedaron guardados con un resultado.

La edición conserva la fecha original, el laboratorista y los valores de control
de la medición. La eliminación retira el registro de las consultas, del historial
y de los gráficos.

## Datos ficticios para probar el módulo

La carga de demostración es opcional y se utiliza antes de iniciar la captura
real. La puesta en producción se deja vacía siguiendo el apartado siguiente.

Después de actualizar, ejecuta por SSH:

```bash
cd ~/lab_oncosavi
docker compose exec -T --user www-data app php artisan oncosavi:cci-demo-crear
```

El comando utiliza un usuario administrador existente autorizado para CCI y
añade áreas y productos identificados con **[DEMO]**, pruebas de química y
hematología, tres niveles y lecturas de los últimos quince días, incluido hoy.
Incluye 32 registros con 384 lecturas: cada nivel tiene mediciones fuera de
rango seguidas de una medición dentro del rango después de un ajuste simulado.
Hoy se incluyen ambas mediciones para ver la secuencia también en Diario.
No crea pacientes, órdenes ni usuarios. Repetir el comando conserva los cambios
que hayas realizado en la demostración y no duplica los datos.

Si ya cargaste la demostración anterior, ejecuta primero
`php artisan oncosavi:cci-demo-limpiar` y después
`php artisan oncosavi:cci-demo-crear` dentro del contenedor. Así se reemplazan
solo los datos de la demostración por los casos actualizados.

Si necesitas indicar el usuario, añade `--usuario=ID`, donde `ID` es el número de
un usuario existente con permisos de mantenimiento y registro de CCI.

Al terminar las pruebas, este único comando retira los datos creados para la
demostración:

```bash
cd ~/lab_oncosavi && docker compose exec -T --user www-data app php artisan oncosavi:cci-demo-limpiar
```

La limpieza identifica los elementos creados mediante un registro privado en el
volumen persistente de la aplicación (`storage/app/private/cci-demo.json`).
Conserva ese archivo junto con la base de datos si haces un respaldo o traslado
durante las pruebas. Conserva los catálogos, controles y datos reales. Si un área
real está usando un lote de demostración, el comando informa de esa relación
antes de borrar nada.

## Exportaciones PDF

El permiso **Exportar PDF de datos y gráficos** se asigna desde Roles, en la
pestaña Control de Calidad Interno. El usuario necesita también el permiso de
la pantalla que consulta; incluir gráficos requiere el permiso de gráficos.

Hay botones en Resumen, Mantenimiento, Registro, Gráficos e Historial. En las
consultas de registros se puede elegir **Solo datos** o **Datos y gráficos**.
Mantenimiento exporta el apartado seleccionado, y Gráficos exporta la prueba y
el período aplicados. Cada registro y cada gráfico también tienen acceso PDF.
El historial conserva sus filtros de área, laboratorista, fechas y búsqueda.
Los archivos con gráficos incluyen sus límites históricos, la franja de control
y las X rojas. Para consultas de más de 300 registros se solicita acortar el
período, sin recortar datos silenciosamente.

## Consulta privada para supervisión

La consulta independiente usa un enlace privado sin contraseña, compartido con
el propietario en la entrega y dentro del PDF de presentación. Permite ver
una tabla compacta con registro, fecha, área, equipo, laboratorista y estado.
**Ver registro** abre las lecturas detalladas. Permite filtrar por área,
laboratorista y fechas, y buscar por los datos de los controles. Excluye
registros eliminados y no inicia una sesión del sistema.

El apartado **Gráficos** utiliza los mismos datos y motor visual del módulo:
se seleccionan área y prueba, se conservan los filtros y se puede ampliar cada
gráfico. La franja de control y las X rojas también aparecen en esta consulta.

Los supervisores pueden guardar PDF del registro individual, de los registros
seleccionados o de la consulta filtrada, con opción de incluir gráficos. La
selección se limita a los registros activos que pertenecen a los filtros de esa
consulta. Estas descargas validan el enlace privado por separado; no habilitan
la exportación general del panel ni acciones de edición o eliminación.

El repositorio contiene únicamente la huella de verificación del enlace en
`config/control_calidad.php`. La variable opcional `CCI_SUPERVISION_TOKEN_HASH`
permite sustituir o revocar el acceso. El enlace completo y el PDF que lo incluye
se entregan en privado; no se publican en GitHub.

## Presentación para supervisores

Las capturas reales se conservan en `docs/control-calidad/capturas/`. La
presentación reúne las cinco pantallas, la configuración de niveles, los
gráficos ampliados, la exportación y la consulta de supervisión con párrafos
breves. Todas las capturas muestran datos ficticios.

La dirección privada de descarga se entrega al propietario. Para descargar el
PDF desde el sistema se requiere sesión, acceso al módulo, mantenimiento y
exportación. El documento incluye el enlace de consulta sin contraseña.
Las actualizaciones conservan los datos existentes. Para iniciar el trabajo
real con QA vacío, utiliza una sola vez el comando del siguiente apartado.

## Iniciar QA vacío para la configuración manual

Después de actualizar y aplicar la migración de acceso a los roles, ejecuta una
sola vez:

```bash
cd ~/lab_oncosavi && docker compose exec -T --user www-data app php artisan oncosavi:cci-vaciar --force
```

Este comando elimina toda la configuración y las mediciones de QA: áreas,
equipos, productos, lotes, pruebas, niveles, valores y registros de control,
incluidos los registros eliminados desde la interfaz. Los usuarios, permisos,
pacientes, órdenes y resultados clínicos se conservan. Antes de borrar se
guarda un respaldo JSON privado en `storage/app/private/cci-respaldos/`.

La limpieza no se ejecuta al actualizar ni al aplicar migraciones. No repitas
este comando después de que el laboratorio comience su carga real. Para las
actualizaciones siguientes utiliza únicamente los comandos de actualización.

Con QA vacío, el laboratorista empieza en **Mantenimiento**: registra las áreas
y equipos, agrega los productos con sus lotes y configura las pruebas, unidades
y niveles. Después puede comenzar a ingresar mediciones desde **Registro**.
