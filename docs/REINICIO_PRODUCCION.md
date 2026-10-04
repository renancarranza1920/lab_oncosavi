# Inicio de operaciones en Oracle

Este cambio pone **Exámenes** antes de **Perfiles** al crear una orden y limita la
bitácora al rol `admin`. La migración retira los permisos de bitácora de los roles
no administrativos; la autorización también rechaza URLs directas y permisos
individuales antiguos. Conserva los demás permisos.

## Datos del reinicio

El comando `oncosavi:reiniciar-operaciones --ejecutar` vacía, en este orden:

1. Resultados, detalles de exámenes y detalles de perfiles de las órdenes.
2. Órdenes.
3. Teléfonos de clientes y clientes.
4. Médicos registrados.

Las tablas y sus relaciones se conservan. Los IDs de clientes, órdenes y sus
dependientes comienzan nuevamente en 1. Los números de expediente vuelven a
iniciar en `001` para cada prefijo de iniciales y año.

Se conserva el acceso compartido **medicos**, con su contraseña y configuración.
Ocupa el ID interno 1 y no aparece en la lista de médicos registrados; el primer
médico nuevo tendrá el ID 2. Se cierran las sesiones del portal médico para evitar
que una sesión antigua termine asociada a un ID reutilizado. Pueden volver a
entrar con la misma contraseña. Las sesiones del personal permanecen.

No modifica usuarios, contraseñas del personal, firmas, sellos personales, sello
institucional, exámenes, pruebas, perfiles, precios, referencias, cupones,
cotizaciones ni configuración. Conserva la bitácora y registra el reinicio.
Desvincula las referencias de auditoría a los registros eliminados, conservando
sus IDs anteriores en el detalle, para no atribuirlas a los nuevos registros.

Antes de borrar, guarda las filas de las siete tablas en
`storage/app/private/reinicios/FECHA-IDENTIFICADOR/datos.json` y mueve la carpeta de
PDFs de órdenes a ese mismo respaldo privado. Los PDFs antiguos dejan de estar
publicados y no se reutilizan al empezar de nuevo los IDs. Las imágenes y sellos
permanecen en sus ubicaciones. Este respaldo es de los datos operativos y sus
PDFs; no sustituye una copia completa de la base y el volumen.

El reinicio elimina **todas** las operaciones existentes al ejecutarlo; no aplica
un corte por fecha. Es una operación puntual para antes de iniciar las ventas
reales. Actualizar el código o ejecutar migraciones no vacía las operaciones.

Sin `--ejecutar`, el comando solo muestra cantidades y no cambia nada:

```bash
docker compose exec -T --user www-data app php artisan oncosavi:reiniciar-operaciones
```

Requiere mantenimiento para ejecutar el vaciado. Si encuentra una tabla adicional
que depende de órdenes, clientes o médicos, se detiene antes de borrar, para no
eliminar datos ajenos al alcance solicitado.

## Actualizar y ejecutar en Oracle

Desde SSH, en el servidor que ya tiene la aplicación y sus volúmenes:

```bash
set -e
cd ~/lab_oncosavi
git switch main
git pull --ff-only origin main
docker compose build app
docker compose exec -T --user www-data app php artisan down
docker compose up -d --no-deps --force-recreate app
docker compose exec -T --user www-data app php artisan migrate --force
docker compose exec -T --user www-data app php artisan optimize:clear
docker compose exec -T --user www-data app php artisan filament:clear-cached-components
docker compose exec -T --user www-data app php artisan oncosavi:reiniciar-operaciones --ejecutar
docker compose exec -T --user www-data app php artisan up
```

La reconstrucción se hace antes del mantenimiento. El volumen de `storage`
conserva el mantenimiento al recrear el contenedor. `set -e` detiene la secuencia
si algún comando falla; no reabras el sistema hasta corregir ese fallo. Al
terminar, el comando imprime la ruta del respaldo privado. Guarda esa ruta.

No ejecutes `migrate:fresh`, `db:seed` ni `docker compose down -v`: no forman parte
de esta actualización y podrían afectar los datos que se deben conservar.

Las actualizaciones siguientes deben omitir el comando de reinicio para conservar
las ventas reales. Estos comandos no cambian `.env` ni vuelven a crear usuarios.
