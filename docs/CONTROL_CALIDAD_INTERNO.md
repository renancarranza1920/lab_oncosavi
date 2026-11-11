# Control de Calidad Interno

Dirección: <https://applab.oncosavi.com/control-calidad-interno>.

El módulo es un panel independiente de Filament. Utiliza el inicio de sesión de
Oncosavi y no añade accesos al menú principal. Tiene estas cinco pantallas:

1. **Resumen:** últimos registros y gráficos.
2. **Mantenimiento:** áreas y equipos, productos de control, lotes y pruebas con
   sus niveles y valores de control.
3. **Registro de Control de Calidad Interno:** selección del área, fecha y hora
   automáticas, laboratorista, resultados y observaciones opcionales. Incluye
   edición y eliminación de registros según el permiso asignado.
4. **Gráficos:** consulta por área, prueba y periodo, con una gráfica por nivel.
5. **Historial:** búsqueda y consulta de registros, incluidos los eliminados.

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

1. En **Mantenimiento**, registra el área (Química o Hematología) y el nombre del
   equipo.
2. Agrega el producto de control y sus lotes, con nombre, número y caducidad.
3. Crea la prueba, indica su unidad y agrega los niveles. Para cada nivel,
   selecciona el lote y escribe el valor esperado, mínimo y máximo.
4. En **Registro**, selecciona el área y equipo. Aparecerán las pruebas y niveles
   configurados. La fecha y la hora se asignan al guardar; el usuario actual
   aparece como laboratorista por defecto.
5. Completa los resultados y, si corresponde, las observaciones. Se admiten
   decimales con coma o punto; el cero es un resultado válido.
6. Consulta los gráficos y el historial. Cambiar los valores de control en
   Mantenimiento no altera los límites que ya quedaron guardados con un resultado.

La edición conserva la fecha original y los valores de control de la medición.
La eliminación retira el registro de las consultas activas y de los gráficos;
permanece identificado como eliminado en el historial.
