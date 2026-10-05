# Recuperar autores de resultados anteriores

Las versiones anteriores guardaban automáticamente los resultados al descargar
un PDF parcial. Como el guardado asignaba el usuario que lo solicitaba, descargar
desde soporte podía reemplazar al laboratorista original. Regenerar el PDF ahora
es una lectura y consulta las imágenes actuales del autor guardado en cada resultado.
Subir sellos después no corrige una autoría que ya fue reemplazada.

El comando `oncosavi:reparar-autoria-resultados` repara una orden específica usando
la bitácora general y los detalles archivados en `registros_soporte`. Solo recupera
un autor si el último cambio registrado modificó exclusivamente `user_id` y,
opcionalmente, `updated_at`. Tolera guardados posteriores del mismo usuario que
solo actualizaron la fecha. Comprueba la creación del resultado, su historial y
que los datos actuales coincidan. No adivina autores por nombre ni por quién
descarga el PDF. Si un ID fue reutilizado después del reinicio operativo, distingue
la creación de la fila actual de los registros antiguos.

Si algún resultado no tiene evidencia suficiente, cambió valores clínicos o su
autor anterior ya no existe, se bloquea **toda la reparación de esa orden** y se
muestra el motivo, sin cambios. No se deben borrar bitácoras antes de reparar.

## Revisión y aplicación en Oracle

Para la orden 2 que quedó asignada al usuario 12:

```bash
cd ~/lab_oncosavi
docker compose exec -T --user www-data app php artisan oncosavi:reparar-autoria-resultados 2 --desde-usuario=12
```

La revisión muestra los IDs de resultados, los autores recuperables y los IDs de
las actividades que sustentan la reparación. No modifica datos. Si la revisión
es satisfactoria, aplicar en mantenimiento:

```bash
(
  set -e
  cd ~/lab_oncosavi
  docker compose exec -T --user www-data app php artisan down
  trap 'docker compose exec -T --user www-data app php artisan up' EXIT
  docker compose exec -T --user www-data app php artisan oncosavi:reparar-autoria-resultados 2 --desde-usuario=12 --ejecutar
)
```

Se guarda un respaldo de las filas originales y los cambios previstos en
`storage/app/private/reparaciones/autoria-resultados/<fecha-uuid>/datos.json`,
con directorio 0700 y archivo 0600. La ejecución cambia únicamente `user_id`,
conserva valores, referencias, fechas, usuarios e imágenes y registra cada
reparación con el ID de la evidencia y la ruta del respaldo. Ante un fallo del
respaldo o la bitácora, la transacción no deja ninguna autoría modificada. Repetir
el comando después de reparar devuelve cero cambios.

Por último, en Órdenes, **regenerar el PDF final** con **Incluir sellos y firmas**
activado. Los PDFs guardados no se modifican durante la reparación; regenerarlos
es lo que incorpora las firmas y sellos vigentes de los autores recuperados.
El portal médico visualizará ese mismo archivo actualizado.

## Actualizar main en Oracle

```bash
(
  set -e
  cd ~/lab_oncosavi
  git switch main
  git pull --ff-only origin main
  docker compose build app
  docker compose exec -T --user www-data app php artisan down
  trap 'docker compose exec -T --user www-data app php artisan up' EXIT
  docker compose up -d --no-deps --force-recreate app
  docker compose exec -T --user www-data app php artisan optimize:clear
  docker compose exec -T --user www-data app php artisan filament:clear-cached-components
)
```

Este cambio no requiere migraciones ni seeders. No elimina volúmenes ni modifica
otras órdenes. El comando de reparación está disponible también sin Docker:
`php artisan oncosavi:reparar-autoria-resultados <orden> --desde-usuario=<id>`.
