# Reinicio operativo de Lameri

La instalación inicial se hace sobre una base nueva con [README_HOSTINGER.md](../README_HOSTINGER.md) y no requiere este reinicio. Esta guía describe una herramienta de vaciado puntual para retirar datos operativos de prueba antes de iniciar las ventas reales. No forma parte de una actualización normal.

El sistema coloca **Exámenes** antes de **Perfiles** al crear una orden y limita la bitácora al rol `admin`. La migración retira los permisos de bitácora de los roles no administrativos; la autorización también rechaza URLs directas y permisos individuales antiguos. Conserva los demás permisos.

## Datos del reinicio

El comando `lameri:reiniciar-operaciones --ejecutar` vacía, en este orden:

1. Resultados, detalles de exámenes y detalles de perfiles de las órdenes.
2. Órdenes.
3. Teléfonos de clientes y clientes.
4. Médicos registrados.

Las tablas y sus relaciones se conservan. Los IDs de clientes, órdenes y sus dependientes comienzan nuevamente en 1. Los números de expediente vuelven a iniciar en `001` para cada prefijo de iniciales y año.

Se conserva el acceso compartido **medicos**, con su contraseña y configuración. Si ya existe, ocupa el ID interno 1 y no aparece en la lista de médicos registrados; el primer médico nuevo tendrá el ID 2. Se cierran las sesiones del portal médico para evitar que una sesión antigua termine asociada a un ID reutilizado. Pueden volver a entrar con la misma contraseña. Las sesiones del personal permanecen.

No modifica usuarios, contraseñas del personal, firmas, sellos personales, sello institucional, exámenes, pruebas, perfiles, precios, referencias, cupones, cotizaciones ni configuración. Conserva la bitácora y registra el reinicio. Desvincula las referencias de auditoría a los registros eliminados, conservando sus IDs anteriores en el detalle, para no atribuirlas a los nuevos registros.

Antes de borrar, guarda las filas de las tablas operativas en `storage/app/private/reinicios/FECHA-IDENTIFICADOR/datos.json` y mueve la carpeta de PDFs de órdenes a ese mismo respaldo privado. Los PDFs antiguos dejan de estar publicados y no se reutilizan al empezar de nuevo los IDs. Las imágenes y sellos permanecen en sus ubicaciones. Este respaldo es de los datos operativos y sus PDFs; no sustituye una copia completa de MySQL y de `storage`.

Si la instalación conserva `envios_whatsapp` de una versión anterior, incluye ese historial en el respaldo. Conserva los envíos y sus referencias al personal; la relación histórica `orden_id` queda vacía al borrar las órdenes. Así no se atribuye un envío anterior a una nueva orden con el mismo ID. No crea la tabla ni reactiva un módulo de WhatsApp. Solo admite la relación conocida `ON DELETE SET NULL`; una relación diferente detiene el reinicio.

El reinicio elimina **todas** las operaciones existentes al ejecutarlo; no aplica un corte por fecha. Actualizar el código o ejecutar migraciones no vacía las operaciones. Si encuentra una tabla adicional que depende de órdenes, clientes o médicos, se detiene antes de borrar.

## Revisar antes de ejecutar

Desde SSH, entra a la raíz del proyecto donde está `artisan`. Sin `--ejecutar`, solo muestra cantidades y no cambia nada:

```bash
php artisan lameri:reiniciar-operaciones
```

Confirma que únicamente hay operaciones de prueba que deban retirarse. Guarda una copia completa de la base y de `storage` fuera de la carpeta pública antes del vaciado.

## Ejecutar el vaciado puntual

El código debe estar actualizado desde `lameri_new` según [README_HOSTINGER.md](../README_HOSTINGER.md). No ejecutes esta secuencia si hay ventas reales que deban conservarse.

```bash
(
  set -e
  php artisan down
  php artisan lameri:reiniciar-operaciones --ejecutar
  php artisan up
)
```

El comando requiere mantenimiento. `set -e` dentro de los paréntesis detiene la secuencia si falla un comando, sin cerrar la sesión SSH. Si falla, revisa el error y el estado de la base y los PDFs antes de reabrir el sistema con `php artisan up`. Al terminar, el comando imprime la ruta del respaldo privado: guárdala.

No ejecutes `migrate:fresh` ni el seeder completo como parte de este vaciado: podrían afectar datos que deben conservarse. Las actualizaciones siguientes deben omitir el comando de reinicio para conservar las ventas reales. Esta secuencia no cambia `.env` ni vuelve a crear usuarios.

El nombre vigente es `lameri:reiniciar-operaciones`; el alias `oncosavi:reiniciar-operaciones` se mantiene por compatibilidad.
