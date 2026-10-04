# Portal de expedientes para médicos

URL: `https://applab.oncosavi.com/expediente`. Usa el mismo dominio y contenedor del sistema; no requiere otro servicio ni cambios en Caddy.

## Acceso general para todos los médicos

El acceso compartido usa el usuario **medicos**. Después de migrar, entra a **Atención al Paciente → Médicos → Acceso médico general**, habilítalo y define una contraseña para compartir. Consulta todos los expedientes y PDFs, sin acceso al administrador. El comando `oncosavi:portal-medicos-general` también permite habilitarlo con una contraseña aleatoria guardada en `storage/app/private/portal-medicos-general.json`.

Cambiar la contraseña cierra las sesiones existentes. Los botones de contraseña permiten mostrar u ocultar lo escrito; no revelan contraseñas guardadas. Consulta [la guía de actualización](MEJORAS_OPERACION_Y_REPORTES.md) para los comandos de Oracle y la gestión de la cuenta compartida.

## Accesos individuales opcionales

1. Como administrador, entra a **Atención al Paciente → Médicos**. Registra al médico si todavía no existe.
2. Usa **Acceso al portal**, habilita el acceso y define una contraseña de al menos 8 caracteres. Su usuario aparece como `MED-ID` en la tabla y el formulario.
3. Elige el alcance: **Permitir consultar todos los pacientes** habilita el expediente general. Apagado, solo muestra pacientes y órdenes asociados a ese médico mediante el campo Médico de cada orden.
4. Para usar una misma contraseña, selecciona varios médicos y usa **Habilitar portal con una misma contraseña** en las acciones de selección. Los usuarios siguen siendo individuales.
5. Entrega a cada médico el enlace, su usuario y la contraseña. Las contraseñas no se muestran después de guardarlas. Se pueden cambiar o deshabilitar desde la misma acción.

Los accesos empiezan deshabilitados. Cambiar la contraseña, el alcance o el estado invalida las sesiones anteriores. Solamente quien tiene `manage_settings` puede configurar estos accesos. La bitácora registra los cambios sin guardar contraseñas.

## Consultar

- Buscar por nombre y apellido completos o parciales, expediente, DUI con o sin guion, teléfono con o sin formato, correo, dirección y número de orden (por ejemplo `#123`).
- Abrir los filtros para seleccionar fecha de nacimiento, género, estado del paciente y fechas de órdenes. Las horas corresponden al registro de la misma orden, en la zona horaria del sistema: El Salvador.
- **Ver expediente** muestra datos del paciente y su historial paginado. El alcance configurado también se aplica al historial y a las rutas de los PDFs.
- **Ver resultados** abre un visor del PDF final guardado en el sistema. **Descargar PDF** devuelve el mismo archivo y nombre utilizados en Órdenes. No se regeneran resultados, firmas ni sellos.
- Si aún no se finalizó la orden o el laboratorio no guardó el PDF, se muestra un aviso; el archivo no se publica desde el portal. En móviles sin visor integrado está disponible **Abrir PDF**.

El portal no permite editar pacientes, ingresar resultados ni guardar consultas o indicaciones médicas. Sus cuentas no tienen acceso al panel administrativo. La búsqueda y los PDFs requieren sesión activa; las respuestas no permiten caché ni indexación. Apache impide el acceso directo a `/storage/reportes/`, conservando las rutas autenticadas del panel y del portal.

## Actualizar Oracle sin borrar volúmenes

Ejecutar desde la sesión SSH del servidor:

```bash
set -e
cd ~/lab_oncosavi
git switch main
git pull --ff-only origin main
docker compose -f docker-compose.yml build app
docker compose -f docker-compose.yml up -d --wait db
docker compose -f docker-compose.yml run --rm --no-deps app php artisan migrate --force
docker compose -f docker-compose.yml up -d --remove-orphans
docker compose -f docker-compose.yml exec -T app php artisan optimize:clear
docker compose -f docker-compose.yml exec -T app php artisan filament:clear-cached-components
docker compose -f docker-compose.yml ps
```

No ejecuta seeders ni elimina datos, PDFs o volúmenes. Después de actualizar, configurar el acceso general o los accesos individuales desde el panel.

## Integración de dev-manuel

Se hizo un merge con la rama `dev-manuel`, conservando solo el alcance solicitado de reportes: cierre diario, retiro del ticket promedio y simplificación de la vista del catálogo. Se conservaron los tokens de color claro/oscuro de `main` y se corrigió el límite de fechas del cierre diario. La actualización posterior de atención agrega teléfonos múltiples y nuevos diseños de documentos, manteniendo los permisos y el flujo manual de WhatsApp.

## Verificación

`PortalMedicosTest` verifica autenticación, aislamiento del administrador, revocación, ambos alcances, búsquedas, filtros de día/hora, rechazo de escrituras, PDF original, descargas, límites de login y gestión de accesos con contraseña compartida. `CierreCajaServiceTest` cubre el cierre diario y sus cancelaciones, además de los períodos mensual, trimestral y anual.

```bash
DB_CONNECTION=sqlite DB_DATABASE=:memory: php -d memory_limit=512M vendor/bin/phpunit
npm run build
```

Usar siempre bases de prueba aisladas para PHPUnit; nunca la base desplegada.
