# Recepción de muestras y reporte de resultados

Las muestras asociadas a un examen son opciones compatibles. Para pasar de
Pendiente a En proceso basta registrar al menos una muestra recibida por cada
examen que tenga muestras configuradas. Se pueden marcar varias si efectivamente
se recibieron; ya no existe el botón para marcar todas de una vez.

Si otro examen de la orden todavía no tiene muestra, se guarda la recepción
parcial, la orden permanece pendiente y el aviso identifica los exámenes faltantes.
Los exámenes sin muestras configuradas no bloquean el procesamiento.

El PDF completo y el parcial incluyen únicamente las muestras registradas para los
exámenes que aparecen en cada bloque del reporte. Las alternativas no recibidas
no se imprimen. Si no existe un registro de recepción, no se inventa una muestra
a partir del catálogo o del texto de los resultados.

## Corregir una orden existente

1. Abra la pestaña correspondiente de Órdenes y localice la orden.
2. Pulse el icono del tubo **Gestionar Muestras**.
3. Desmarque las alternativas que no se recibieron y conserve únicamente las reales.
4. Guarde y vuelva a generar el PDF con el botón habitual.

La acción está disponible en órdenes pendientes, en proceso, pausadas y finalizadas
para quienes ya tienen el permiso `procesar_muestras_orden`. En las tres últimas,
la corrección exige conservar al menos una muestra por examen configurado y
mantiene el estado, el receptor original y la fecha de toma original. Los cambios
de muestras quedan en la bitácora. No modifica resultados, autorías, firmas ni sellos.

Los PDFs guardados y descargados previamente conservan su contenido hasta que se
vuelvan a generar. La actualización no cambia automáticamente las muestras de
órdenes históricas: el laboratorio debe confirmar cuáles se recibieron realmente.

## Actualizar Oracle con Docker

Desde SSH, fuera de la consola de MySQL:

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

Este ajuste utiliza el campo existente `detalle_orden.muestras_recibidas`; no
requiere migraciones nuevas ni seeders. La reconstrucción conserva los volúmenes
de MySQL y de archivos del laboratorio. Al terminar, corrija y regenere los PDFs
afectados desde el panel.

## Comprobaciones del cambio

Pasaron 29 pruebas de recepción, permisos, diseño y regeneración de resultados
(436 aserciones), además de la compilación de los recursos con `npm run build`.
La revisión visual de un cultivo con datos de ejemplo confirma que se imprime
solo la muestra recibida y se conserva el membrete.

La suite general ejecutada tuvo 138 pruebas correctas y 3 fallos de teléfonos.
Los mismos 3 fallos se reprodujeron por separado en `c903fa8`, antes de este ajuste.
