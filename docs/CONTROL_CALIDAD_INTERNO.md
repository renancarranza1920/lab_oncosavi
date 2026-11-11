# Control de Calidad Interno

Dirección: <https://applab.oncosavi.com/control-calidad-interno>.

El módulo es un panel independiente de Filament. Utiliza el inicio de sesión de
Oncosavi y no añade accesos al menú principal. Tiene estas cinco pantallas:

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
  docker compose exec -T --user www-data app php artisan permission:cache-reset
)
```

La primera migración crea las tablas `cci_*` del módulo en la base de datos de
Oncosavi. La segunda registra los siete permisos y retira los permisos de las
funciones descartadas de la implementación anterior. Los permisos de los demás
módulos se conservan.

La instalación comienza sin registros ni catálogos de demostración. Los datos se
configuran desde **Mantenimiento**. No se ejecutan seeders ni se modifican las
órdenes, pacientes o resultados clínicos.

La implementación anterior y sus rutas `/qa-quimica` se retiraron. Sus archivos
de almacenamiento no se consultan ni se importan en este panel nuevo. Los
comandos antiguos `oncosavi:qa-demo-preparar` y sus alias también se retiraron;
esta versión se instala con las dos migraciones indicadas.

## Configurar los permisos

Abre **Roles** en Oncosavi y selecciona la pestaña **Control de Calidad Interno**,
junto a **Permisos personalizados**:

- Acceder al módulo y ver el resumen.
- Administrar mantenimiento.
- Registrar controles.
- Editar registros.
- Eliminar registros.
- Consultar gráficos.
- Consultar historial.

El permiso de acceso es necesario junto con cada permiso de acción. Los
administradores existentes reciben los permisos iniciales; los demás roles se
habilitan desde esta pestaña. Las acciones se verifican también en el servidor.

## Empezar a utilizarlo

1. En **Mantenimiento → Áreas**, escribe el nombre del área y su equipo. Puedes
   crear las áreas que necesites, por ejemplo Química, Hematología o Inmunología.
2. En **Productos**, registra el nombre del control, número de lote y caducidad
   en el mismo formulario. Puedes agregar más lotes con el mismo producto.
3. Crea la prueba, indica su unidad y agrega los niveles. Para cada nivel,
   selecciona el lote y escribe el valor esperado, mínimo y máximo.
4. En **Registro**, selecciona el área y equipo. Aparecerán las pruebas y niveles
   configurados. La fecha y la hora se asignan y muestran al guardar; el usuario
   de la sesión se registra automáticamente sin aparecer en el formulario.
5. Completa los resultados y, si corresponde, las observaciones. Se admiten
   decimales con coma o punto; el cero es un resultado válido.
6. En **Gráficos**, elige Diario para el día actual, Semanal para la semana actual
   de lunes a domingo, o Mensual para el mes actual. Se usa la hora de El Salvador.
   Cambiar los valores de control en
   Mantenimiento no altera los límites que ya quedaron guardados con un resultado.

La edición conserva la fecha original, el laboratorista y los valores de control
de la medición. La eliminación retira el registro de las consultas, del historial
y de los gráficos.
