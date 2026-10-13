# Recuperar autores de resultados anteriores

La versión actual conserva el autor de los resultados que no cambian al guardar o completar una orden. Regenerar un PDF es una lectura y consulta las imágenes actuales del autor guardado en cada resultado. La instalación inicial de Lameri no incluye resultados históricos.

Esta herramienta sirve para una instalación que conserve resultados afectados por versiones anteriores: esas versiones podían guardarlos al descargar un PDF parcial y asignar el usuario que lo solicitaba, reemplazando al laboratorista original. Subir sellos después no corrige una autoría ya reemplazada.

El comando `lameri:reparar-autoria-resultados` repara una orden específica usando la bitácora general y los detalles archivados en `registros_soporte`. Solo recupera un autor si el último cambio registrado modificó exclusivamente `user_id` y, opcionalmente, `updated_at`. Tolera guardados posteriores del mismo usuario que solo actualizaron la fecha. Comprueba la creación del resultado, su historial y que los datos actuales coincidan. No adivina autores por nombre ni por quién descarga el PDF. Si un ID fue reutilizado después del reinicio operativo, distingue la creación de la fila actual de los registros antiguos.

Si algún resultado no tiene evidencia suficiente, cambió valores clínicos o su autor anterior ya no existe, se bloquea **toda la reparación de esa orden** y se muestra el motivo, sin cambios. No borres bitácoras antes de reparar.

## Revisión desde PHP CLI

Desde SSH, entra a la raíz donde está `artisan`. Sustituye `ORDEN_ID` por el número de la orden y `USUARIO_ID` por el ID del usuario que quedó asignado por error:

```bash
php artisan lameri:reparar-autoria-resultados ORDEN_ID --desde-usuario=USUARIO_ID
```

La revisión muestra los IDs de resultados, los autores recuperables y los IDs de las actividades que sustentan la reparación. No modifica datos. Si la evidencia corresponde a la corrección necesaria, aplica en mantenimiento, sustituyendo los mismos valores:

```bash
(
  set -e
  php artisan down
  trap 'php artisan up' EXIT
  php artisan lameri:reparar-autoria-resultados ORDEN_ID --desde-usuario=USUARIO_ID --ejecutar
)
```

Se guarda un respaldo de las filas originales y los cambios previstos en `storage/app/private/reparaciones/autoria-resultados/FECHA-UUID/datos.json`, con directorio 0700 y archivo 0600. La ejecución cambia únicamente `user_id`, conserva valores, referencias, fechas, usuarios e imágenes y registra cada reparación con el ID de la evidencia y la ruta del respaldo. Ante un fallo del respaldo o la bitácora, la transacción no deja ninguna autoría modificada. Repetir el comando después de reparar devuelve cero cambios.

Por último, en Órdenes, **regenera el PDF final** con **Incluir sellos y firmas** activado. Los PDFs guardados no se modifican durante la reparación; regenerarlos incorpora las firmas y sellos vigentes de los autores recuperados. El portal médico visualizará ese mismo archivo actualizado.

La instalación y la actualización del proyecto se describen en [README_HOSTINGER.md](../README_HOSTINGER.md). Usa el código de `lameri_new` y conserva los archivos privados de `storage`. No se requieren seeders para ejecutar esta reparación. El alias `oncosavi:reparar-autoria-resultados` se mantiene por compatibilidad.
