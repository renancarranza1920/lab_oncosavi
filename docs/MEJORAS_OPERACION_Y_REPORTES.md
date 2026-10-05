# Actualización de atención y documentos

## Órdenes

Con `IMPRESION_ETIQUETAS_HABILITADA=false` (valor predeterminado), crear una orden regresa al listado sin filtros. El regreso desde Kanban tampoco añade una búsqueda por ID. Las etiquetas se conservan ocultas y se pueden reactivar con esa misma opción.

Las acciones de muestras, pausa, reanudación, finalización, cancelación o restauración conservan la pestaña elegida. Completar desde Ingresar resultados regresa al listado sin seleccionar una pestaña. El detalle de una orden usa los colores del tema también al pasar el cursor en modo oscuro.

## Teléfonos

En Clientes y en la creación rápida de pacientes desde una orden, Contacto permite **Agregar otro teléfono**, seleccionar país y distinguir Móvil/Fijo. Hay códigos de países frecuentes y **Otro país**, donde se introduce el código internacional. El número se ingresa sin ese código; el sistema valida y almacena ambos juntos.

La migración copia el teléfono existente como primer contacto. Al compartir resultados finales o parciales se pregunta a qué número enviar: se debe elegir un contacto o escribir otro, sin selección automática, incluso si hay un único teléfono. También se puede elegir solo correo si el paciente tiene uno registrado. Se puede cambiar el orden de los contactos. Clientes, órdenes y expedientes muestran la lista; las búsquedas encuentran también los teléfonos secundarios. Las cotizaciones admiten los mismos países para su teléfono de WhatsApp.

## Acceso médico compartido y contraseñas

La cuenta general tiene usuario **medicos** y consulta todos los pacientes en `/expediente`, con acceso de lectura al historial y los PDFs guardados. No es un usuario del administrador ni un médico que se pueda asignar a las órdenes.

En **Atención al Paciente → Médicos → Acceso médico general**, un administrador puede habilitarla, definir una contraseña compartida o cambiarla. Dejar la contraseña vacía conserva la actual. Cambiarla cierra las sesiones anteriores. Se mantienen disponibles los accesos médicos individuales ya configurados.

Para habilitar la cuenta inicialmente desde Oracle:

```bash
docker compose -f docker-compose.yml exec -T app php artisan oncosavi:portal-medicos-general
docker compose -f docker-compose.yml exec -T app cat storage/app/private/portal-medicos-general.json
```

El comando genera una contraseña aleatoria, guarda su hash en la base y deja los datos de acceso en ese archivo privado. Repetirlo conserva una cuenta ya configurada. `--renovar` genera otra contraseña y revoca sesiones. Si cambias la contraseña desde el panel, utiliza la nueva contraseña: el archivo contiene únicamente la generada por el comando.

Los formularios de contraseña del administrador, usuarios, perfil, restablecimiento y portal médico tienen un botón para mostrar u ocultar lo escrito. Las contraseñas guardadas nunca se precargan.

## Resultados y solicitud de exámenes

El PDF de resultados usa membrete azul marino/celeste, el logo del laboratorio, datos del paciente, muestras registradas, un cangrejo tenue recortado por el borde izquierdo y ONCOSAVI vertical a la derecha. Conserva pruebas, resultados, referencias, matrices, observaciones, firmas y sellos. El encabezado y el pie se repiten en cada página.

El catálogo imprimible se presenta como una solicitud con espacios para paciente, edad, fecha, sexo y firma del médico, tres columnas, círculos y áreas/perfiles. Incluye indicaciones generales al pie. **Incluir precios en la solicitud de exámenes** permite imprimir además las tarifas. Se conservan todos los nombres: un catálogo que exceda dos páginas legibles muestra un aviso y no se corta silenciosamente.

Los PDFs ya guardados conservan su contenido. Para aplicar el nuevo diseño a una orden existente, vuelve a generar su PDF desde Órdenes; el portal y las descargas mostrarán ese mismo archivo actualizado.

## Despliegue en Oracle sin borrar volúmenes

```bash
set -e
cd ~/lab_oncosavi
git switch main
git pull --ff-only origin main
docker compose -f docker-compose.yml build app
docker compose -f docker-compose.yml up -d --wait db
docker compose -f docker-compose.yml run --rm --no-deps app php artisan migrate --force
docker compose -f docker-compose.yml up -d --no-deps --force-recreate app
docker compose -f docker-compose.yml exec -T app php artisan optimize:clear
docker compose -f docker-compose.yml exec -T app php artisan filament:clear-cached-components
docker compose -f docker-compose.yml exec -T app php artisan oncosavi:portal-medicos-general
docker compose -f docker-compose.yml ps
```

Las migraciones se ejecutan antes de iniciar la nueva aplicación. No se ejecutan seeders ni se eliminan volúmenes. No se necesita cambiar Caddy.

### Sello institucional del laboratorio

En **Mi perfil → Sello del laboratorio**, únicamente el rol `admin` puede subir,
reemplazar o retirar el sello institucional (PNG, máximo 2 MB). Es compartido por
el laboratorio, separado del sello y firma personales de cada usuario. Se incluye
al generar resultados completos y parciales; los PDFs ya guardados deben regenerarse.
La imagen y su configuración se guardan dentro de `storage`, en el volumen persistente
`laravel_storage`, por lo que sobreviven a la reconstrucción del contenedor. No es
necesario copiar `sello.png` por SSH: se carga desde este apartado y se pulsa Guardar.

## Actualización segura del contenedor

El arranque del contenedor corrige el propietario y los permisos de `storage` y
`bootstrap/cache`, incluyendo archivos preexistentes del volumen. Ejecutar Artisan
como `www-data` evita volver a crear logs y cachés con propietario root. Es necesario
aplicar `php artisan migrate --force` después de actualizar: la tabla
`cliente_telefonos` se crea mediante migración, conservando los teléfonos existentes.

### Fechas y posición de sellos en resultados

El encabezado conserva el diseño y muestra fecha y hora de **registro** (creación de
la orden) e **impresión** (generación del PDF), en hora de El Salvador. Los sellos
institucional y personal se imprimen en la franja inferior de cada página. La firma
personal queda centrada encima de su sello, con separación para que el logo y el
texto sean legibles. La firma conserva sus medidas anteriores: solo cambia su
posición vertical, respetando los márgenes transparentes de la imagen.
Las tablas extensas reservan esta franja para conservar sus
resultados y firmas en la misma página. Los documentos ya guardados se mantienen;
para ver estos cambios es necesario regenerarlos.
