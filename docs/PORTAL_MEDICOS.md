# Portal de expedientes para médicos

URL: [https://app.laboratorioclinicomerino.com/expediente](https://app.laboratorioclinicomerino.com/expediente). El portal de **LABORATORIO CLÍNICO MERINO** usa la misma aplicación, base y dominio que el panel `/admin`. Su instalación se describe en [README_HOSTINGER.md](../README_HOSTINGER.md).

## Acceso general para todos los médicos

El acceso compartido usa **medicos**. Después de instalar y migrar, entra a **Atención al Paciente → Médicos → Acceso médico general**, habilítalo y define una contraseña para compartir. Consulta todos los expedientes y PDFs, sin acceso al administrador. Desde la raíz del proyecto, `php artisan lameri:portal-medicos-general` también permite habilitarlo con una contraseña aleatoria guardada en `storage/app/private/portal-medicos-general.json`.

Cambiar la contraseña cierra las sesiones existentes. Los botones de contraseña permiten mostrar u ocultar lo escrito; no revelan contraseñas guardadas. Consulta [MEJORAS_OPERACION_Y_REPORTES.md](MEJORAS_OPERACION_Y_REPORTES.md) para la gestión de la cuenta compartida y su archivo privado de acceso.

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

El portal no permite editar pacientes, ingresar resultados ni guardar consultas o indicaciones médicas. Sus cuentas no tienen acceso al panel administrativo. La búsqueda y los PDFs requieren sesión activa; las respuestas no permiten caché ni indexación. El `.htaccess` de `public_html` impide el acceso directo a `/storage/reportes/`, conservando las rutas autenticadas del panel y del portal.

## Actualización y comprobación

Usa las instrucciones de [README_HOSTINGER.md](../README_HOSTINGER.md) y el código de `lameri_new`. Conserva `.env` y `storage` de tu instalación para mantener credenciales, firmas, sellos y documentos. No reemplaces el almacenamiento operativo por las carpetas vacías de un paquete inicial.

`PortalMedicosTest` cubre autenticación, aislamiento del administrador, revocación, ambos alcances, búsquedas, filtros de día/hora, rechazo de escrituras, PDF original, descargas, límites de login y gestión de accesos con contraseña compartida. `CierreCajaServiceTest` cubre el cierre diario y sus cancelaciones, además de los períodos mensual, trimestral y anual.

Para repetir la suite en un entorno de desarrollo con dependencias instaladas:

```bash
DB_CONNECTION=sqlite DB_DATABASE=:memory: php -d memory_limit=512M vendor/bin/phpunit
npm run build
```

Usa siempre bases de prueba aisladas para PHPUnit; nunca la base desplegada. Después de instalar, comprueba el acceso compartido e individual desde el navegador con expedientes ficticios y verifica que un médico con alcance limitado no pueda abrir PDFs ajenos.
