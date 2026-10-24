# Firma e impresión con QZ Tray en Hostinger

El formato ZPL y el controlador **ZDesigner ZD230-203dpi ZPL** se conservan.
QZ y el módulo de firma se cargan una sola vez en el navegador; al regresar
al tablero, Livewire no reemplaza la instancia que contiene el certificado.
La configuración de certificado y SHA512 termina antes de conectar o imprimir.
Ya no se necesita el CDN de jsrsasign.

Las firmas SHA512 se generan en PHP. El navegador recibe el certificado público
y las firmas, con sesión y permiso de impresión; no recibe la clave privada.
El par se guarda en `storage/app/private/qz/configuracion.json`, con permisos
0600 y reemplazo atómico para evitar mezclar certificados y claves.

## Primera migración desde el JavaScript antiguo

Este bloque respalda los dos archivos que contienen la configuración anterior
en una carpeta privada, restaura únicamente esos dos archivos de Git para permitir
la actualización e importa el par correspondiente sin ejecutar el JavaScript.
También admite que solo uno de los dos archivos haya sido sustituido: se comprueba
la clave contra el certificado de la vista y los dos certificados públicos
históricos de Merino. No genera una identidad nueva ni modifica la base de datos.

Ejecutar **una sola vez**, antes de sustituir el `sign-message.js` que contiene
la clave antigua:

```bash
(
set -e
cd ~/domains/app.laboratorioclinicomerino.com
export PATH="/opt/alt/php83/usr/bin:$PATH"
mkdir -p storage/app/private
LAMERI_QZ_RESPALDO="$(mktemp -d "$PWD/storage/app/private/qz-respaldo.XXXXXX")"
cp public_html/js/sign-message.js "$LAMERI_QZ_RESPALDO/sign-message.js"
cp resources/views/vendor/filament-kanban/kanban-scripts.blade.php "$LAMERI_QZ_RESPALDO/kanban-scripts.blade.php"
chmod 600 "$LAMERI_QZ_RESPALDO/sign-message.js" "$LAMERI_QZ_RESPALDO/kanban-scripts.blade.php"
git restore --source=HEAD --worktree -- public_html/js/sign-message.js resources/views/vendor/filament-kanban/kanban-scripts.blade.php
bash scripts/update-hostinger.sh
php artisan lameri:qz-configurar --desde-js="$LAMERI_QZ_RESPALDO/sign-message.js" --desde-vista="$LAMERI_QZ_RESPALDO/kanban-scripts.blade.php"
)
```

Si hay modificaciones en otros archivos, el actualizador se detiene para poder
revisarlas. Los respaldos y la configuración de QZ quedan fuera de `public_html`.
No copies su contenido a Git ni al chat. Después de configurar, recarga la página
por completo y vuelve a abrir las etiquetas. La consola muestra la huella SHA256
y la vigencia del certificado importado, sin imprimir la clave.

Las actualizaciones siguientes necesitan únicamente:

```bash
cd ~/domains/app.laboratorioclinicomerino.com &&
PATH="/opt/alt/php83/usr/bin:$PATH" bash scripts/update-hostinger.sh
```

## Si conservas los archivos PEM originales

También se puede importar directamente el par del equipo donde ya funciona QZ:

```bash
php artisan lameri:qz-configurar --certificado=/ruta/privada/digital-certificate.txt --clave=/ruta/privada/private-key.pem
```

Si la clave está dañada, no corresponde al certificado o este no está vigente,
el comando se detiene y conserva cualquier configuración válida anterior.
No se reemplazan las claves por otras ni se intenta imprimir sin firma.

## Firma válida y confianza del equipo

Los certificados históricos incluidos son **QZ Tray Demo Cert**. Una firma válida
prueba que el mensaje corresponde a ese certificado; la confianza se configura
en la instalación de QZ Tray de cada computadora. El servidor web no puede
instalarla ni convertir un certificado demo en uno oficial.

Si el sitio antiguo funciona en la misma computadora, reutiliza su mismo par
y compara la huella del certificado con **QZ Tray → Advanced → Site Manager**.
No generes otro certificado para corregir la carga de scripts: cambiaría la
identidad que esa computadora reconoce.

Si un equipo todavía no confía en ese certificado, la documentación de QZ
describe el uso de `override.crt` o de `authcert.override`. **Create New** en Site
Manager genera otro par: solo debe usarse cuando se quiera configurar una
identidad nueva y luego importar los dos archivos correspondientes al servidor.
La autorización de un sitio y la confianza de su certificado son ajustes distintos.
El certificado HTTPS de Hostinger o de `localhost` no sustituye esta confianza.

- [QZ Signing: claves demo y certificados propios](https://qz.io/docs/signing)
- [QZ Provisioning: CA y autorización de certificados](https://qz.io/docs/provisioning)
- [QZ Renewal: caché y comprobación del certificado](https://qz.io/docs/renewal)

La revisión automatizada verifica firmas reales con OpenSSL, el protocolo del
SDK y la navegación en el navegador. La confianza instalada y la impresión
física deben comprobarse en el equipo de recepción.
