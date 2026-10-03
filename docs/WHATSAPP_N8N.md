# WhatsApp directo con n8n en Oracle

El botón envía el PDF desde Laravel → webhook privado de n8n → servicio Baileys → WhatsApp. No abre WhatsApp Web ni requiere una cuenta de la API de Meta. El WhatsApp del laboratorio se vincula como un dispositivo mediante QR. n8n Community se aloja en su servidor y Baileys tiene licencia MIT; no se usa n8n Cloud ni un proveedor de mensajes de pago.

Esta conexión **no es oficial**: WhatsApp puede cambiar su funcionamiento, cerrar la sesión o restringir el número. No hay garantía de permanencia o entrega. Use el número del laboratorio para documentos solicitados por sus clientes; esta función no incluye envíos masivos.

## Instalar en el servidor Oracle

En su sesión SSH de Ubuntu, dentro del repositorio:

```bash
cd ~/lab_oncosavi
git switch main
git pull --ff-only origin main
bash docker/whatsapp/iniciar.sh
```

El script construye las imágenes, ejecuta las migraciones pendientes y recrea los contenedores. **Conserva la base de datos y todos los volúmenes.** No ejecuta `migrate:fresh`, seeders ni `down -v`. La construcción puede tardar varios minutos. Su `.env` debe mantener los datos de MySQL existentes (`DB_HOST=db`); no cambie contraseñas de una base ya inicializada solo para instalar esto.

La primera ejecución genera `.env.whatsapp` con claves aleatorias y permisos 600. Está excluido de Git y de la imagen. Las ejecuciones posteriores conservan el archivo y las claves. No lo pegue en chats, no lo elimine ni regenere sus claves al reconstruir.

## Vincular el teléfono

1. Entre a `https://applab.oncosavi.com/admin` como administrador.
2. Abra **Atención al Paciente → Envíos WhatsApp → Vincular WhatsApp**.
3. Espere unos segundos a que aparezca el QR.
4. En el teléfono del laboratorio: **WhatsApp → Dispositivos vinculados → Vincular un dispositivo**. Escanee el QR desde esa pantalla; no desde la cámara normal.
5. Espere a que el estado indique **conectado**. Si el QR caduca, espere a que se renueve o pulse Vincular nuevamente.

La sesión queda en el volumen `whatsapp_session`; normalmente se conserva al reiniciar o reconstruir. Si WhatsApp la revoca, deberá vincularla otra vez. No publique el QR ni los archivos de ese volumen.

## Uso del personal

- **Cotizaciones:** complete los estudios y teléfono; en el último paso pulse **Enviar PDF por WhatsApp** y confirme el destinatario. El PDF se genera y adjunta automáticamente.
- **Órdenes finalizadas:** genere el reporte, pulse el botón de compartir, seleccione **WhatsApp: envío directo del PDF**, revise el número y continúe.
- **Resultados parciales:** en Ingresar Resultados, genere el PDF parcial y pulse **Enviar PDF por WhatsApp**.
- **Envíos WhatsApp:** cada empleado ve sus solicitudes; el administrador ve todas. Solo el administrador vincula el teléfono. Se mantienen los permisos de Recepción y Laboratorista; Recepción no obtiene permiso para modificar resultados.

Los avisos muestran **enviado**, **fallido**, **enviando** o **desconocido**. “Enviado” requiere una confirmación con identificador de mensaje de WhatsApp; no significa entregado o leído. Con un estado desconocido, consulte el estado o revise el chat del teléfono antes de intentar reenviar. El sistema evita repetir el mismo documento durante cinco minutos y bloquea el reenvío automático de solicitudes inciertas durante un día. Un PDF recién generado puede tener bytes distintos: la confirmación del destinatario y la revisión del historial siguen siendo necesarias.

Se permite un envío a la vez y un intervalo mínimo de cinco segundos. Si otro envío está ocupado, el personal recibe un aviso para esperar y volver a intentarlo. Solo se admiten PDF de hasta 8 MB y teléfonos de El Salvador (+503) o Estados Unidos (+1).

## Actualizaciones siguientes

```bash
cd ~/lab_oncosavi
git switch main
git pull --ff-only origin main
bash docker/whatsapp/iniciar.sh
```

Desde esta instalación, use **ambos** archivos de Compose y de entorno para operar los servicios. Ejecutar únicamente el Compose base con `--remove-orphans` quitaría n8n y WhatsApp.

Para revisar o reiniciar:

```bash
cd ~/lab_oncosavi
docker compose --env-file .env --env-file .env.whatsapp -f docker-compose.yml -f docker-compose.whatsapp.yml ps
docker compose --env-file .env --env-file .env.whatsapp -f docker-compose.yml -f docker-compose.whatsapp.yml logs --tail=50 n8n whatsapp
docker compose --env-file .env --env-file .env.whatsapp -f docker-compose.yml -f docker-compose.whatsapp.yml restart n8n whatsapp
```

## Servicios privados y datos

No requiere cambiar Caddy ni abrir nuevos puertos en Oracle. Baileys no publica ningún puerto en el host; n8n escucha en `127.0.0.1:5678`. La base sigue privada. La comunicación interna exige claves distintas para el webhook y para Baileys. No se permiten rutas, URLs de archivos, grupos ni comandos arbitrarios en la solicitud.

El flujo se importa, publica y activa automáticamente en la primera ejecución. Las credenciales de n8n se guardan cifradas en su volumen; se elimina el archivo temporal de importación. No se guardan ejecuciones con mensajes/PDF, ni se imprimen QR, claves, teléfonos o cuerpos de mensajes en los logs del servicio. El registro de ONCOSAVI conserva usuario, tipo de documento, orden, estado y teléfono cifrado; no duplica el PDF ni el texto del mensaje. El servicio conserva únicamente identificadores y estados de envío para evitar duplicados.

La instalación no cambia el almacenamiento de los reportes PDF existentes del proyecto. Para producción, conserve HTTPS y desactive `APP_DEBUG`; no comparta los reportes o sus rutas públicas fuera de los destinatarios previstos.

n8n tiene un límite de 1 CPU y 1.5 GB; Baileys, 0.75 CPU y 768 MB. Son límites máximos, no consumo permanente. La imagen de n8n incluye ARM64 para Oracle A1; el servicio usa Node 22 ARM64 sin Chromium. Estos límites no cambian la forma ni la facturación de su instancia Oracle.

## Editor de n8n (opcional)

El personal no necesita entrar al editor. Si quiere administrarlo, desde PowerShell en su otra computadora:

```powershell
ssh -i "$env:USERPROFILE\.ssh\oci_gilbe_2026" -L 5678:127.0.0.1:5678 ubuntu@159.54.143.181
```

Mantenga esa terminal abierta y visite `http://localhost:5678`. Cree la cuenta propietaria de n8n si la solicita. El flujo publicado se llama **ONCOSAVI · envío privado de PDF por WhatsApp**. No publique el editor con Caddy ni habilite el guardado de ejecuciones que contengan PDF. Para verificar el flujo, use un documento ficticio y su propio número, porque ejecutarlo manualmente con un número real envía el mensaje.

## Referencias

- [n8n: instalación con Docker](https://github.com/n8n-io/n8n/tree/master/docker/images/n8n).
- [Baileys: conexión por QR, sesiones y envío de documentos](https://github.com/WhiskeySockets/Baileys).

## Pruebas del proyecto

```bash
DB_CONNECTION=sqlite DB_DATABASE=:memory: php -d memory_limit=512M vendor/bin/phpunit tests/Feature/WhatsAppTest.php tests/Feature/RolesAccionesTest.php
cd docker/whatsapp
npm ci
npm test
```

Las pruebas usan un proveedor simulado y datos ficticios. No vinculan una cuenta ni envían mensajes reales. Antes de uso operativo, vincule el teléfono y haga una prueba con un PDF ficticio y un destinatario autorizado.
