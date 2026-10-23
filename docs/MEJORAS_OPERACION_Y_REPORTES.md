# Atención y documentos en Lameri

El sistema de **LABORATORIO CLÍNICO MERINO** utiliza la estructura clínica, los permisos y los flujos actualizados. La instalación en una carpeta de proyecto y las actualizaciones de la rama `lameri_new` se describen en [README_HOSTINGER.md](../README_HOSTINGER.md).

## Órdenes

Crear una orden regresa a la pestaña Pendientes filtrada por la nueva orden. Las etiquetas están habilitadas por defecto; el icono de la tarjeta aparece únicamente en la pestaña Pendientes para órdenes pendientes. El tablero conserva la distribución e impresión ZPL originales de Merino; los permisos controlan su acceso y el envío a la impresora. Una instalación que conserve `IMPRESION_ETIQUETAS_HABILITADA=false` en `.env` debe cambiarla a `true` y actualizar las cachés según [README_HOSTINGER.md](../README_HOSTINGER.md#ver-e-imprimir-etiquetas).

**Gestionar muestras** se muestra únicamente en la pestaña Pendientes. El formulario permite seleccionar todas las muestras o deseleccionarlas. **Guardar muestras** conserva una recepción parcial y pasa a En proceso al completar los exámenes. **Guardar y pasar a proceso** exige al menos una muestra compatible por cada examen con muestras configuradas; si falta alguna, muestra el error sin guardar esa selección. No hace falta marcar todas las alternativas de un examen.

Las acciones de muestras, pausa, reanudación, finalización, cancelación o restauración conservan la pestaña elegida. Completar desde Ingresar resultados regresa al listado sin seleccionar una pestaña. El detalle de una orden usa los colores del tema también al pasar el cursor en modo oscuro.

## Teléfonos

En Clientes y en la creación rápida de pacientes desde una orden, Contacto permite **Agregar otro teléfono**, seleccionar país y distinguir Móvil/Fijo. Hay códigos de países frecuentes y **Otro país**, donde se introduce el código internacional. El número se ingresa sin ese código; el sistema valida y almacena ambos juntos.

Si se actualiza una instalación con teléfonos existentes, la migración los copia como primer contacto. Al compartir resultados finales o parciales se pregunta a qué número enviar: se debe elegir un contacto o escribir otro, sin selección automática, incluso si hay un único teléfono. También se puede elegir solo correo si el paciente tiene uno registrado. Se puede cambiar el orden de los contactos. Clientes, órdenes y expedientes muestran la lista; las búsquedas encuentran también los teléfonos secundarios. Las cotizaciones admiten los mismos países para su teléfono de WhatsApp.

## Acceso médico compartido y contraseñas

La cuenta general usa **medicos** y consulta todos los pacientes en `/expediente`, con acceso de lectura al historial y los PDFs guardados. No es un usuario del administrador ni un médico que se pueda asignar a las órdenes.

En **Atención al Paciente → Médicos → Acceso médico general**, un administrador puede habilitarla, definir una contraseña compartida o cambiarla. Dejar la contraseña vacía conserva la actual. Cambiarla cierra las sesiones anteriores. También están disponibles los accesos médicos individuales.

Para habilitar la cuenta desde PHP CLI, entra por SSH a la raíz donde está `artisan`:

```bash
php artisan lameri:portal-medicos-general
cat storage/app/private/portal-medicos-general.json
```

El comando genera una contraseña aleatoria, guarda su hash en la base y deja los datos de acceso en ese archivo privado. Repetirlo conserva una cuenta ya configurada. `--renovar` genera otra contraseña y revoca sesiones. Si cambias la contraseña desde el panel, utiliza la nueva: el archivo contiene únicamente la generada por el comando. No publiques ese archivo ni lo incluyas en el paquete de distribución.

Los formularios de contraseña del administrador, usuarios, perfil, restablecimiento y portal médico permiten mostrar u ocultar lo escrito. Las contraseñas guardadas nunca se precargan. Consulta [PORTAL_MEDICOS.md](PORTAL_MEDICOS.md) para los alcances individuales y las rutas de consulta.

## Resultados y solicitud de exámenes

El PDF de resultados conserva la estructura original de Lameri: membrete, tarjeta de paciente de cinco columnas, médico, marca de agua, esquinas, tablas amarillas, tipografía y pie. Mantiene las muestras registradas, pruebas, resultados, referencias, matrices, observaciones, firmas y sellos del sistema actualizado. El encabezado y el pie se repiten en cada página. Cotización, comprobante y boleta también conservan sus plantillas originales.

El catálogo imprimible se presenta como una solicitud con espacios para paciente, edad, fecha, sexo y firma del médico, tres columnas, círculos y áreas/perfiles. Incluye indicaciones generales al pie. **Incluir precios en la solicitud de exámenes** permite imprimir además las tarifas. Se conservan todos los nombres: un catálogo que exceda dos páginas legibles muestra un aviso y no se corta silenciosamente.

Los PDFs ya guardados conservan su contenido. Para aplicar el diseño o las imágenes vigentes a una orden existente, vuelve a generar su PDF desde Órdenes; el portal y las descargas mostrarán ese mismo archivo actualizado.

## Sellos, firmas y autoría

En **Mi perfil → Sello del laboratorio**, únicamente el rol `admin` puede subir, reemplazar o retirar el sello institucional (PNG, máximo 2 MB). Es compartido por el laboratorio y separado del sello y firma personales de cada usuario. Se incluye al generar resultados completos y parciales; los PDFs ya guardados deben regenerarse. La imagen y su configuración se guardan dentro de `storage`: conserva esta carpeta cuando actualices el código en Hostinger.

El encabezado muestra fecha y hora de **registro** (creación de la orden) e **impresión** (generación del PDF), en hora de El Salvador. Los sellos institucional y personal y la firma se colocan después de los resultados, dentro de la tabla, siguiendo el espacio ocupado por su contenido. El sello institucional tiene 130 px de ancho; el sello personal, 170 × 90 px; la firma, 170 × 105 px. Al paginar, el bloque se mantiene junto a los resultados para evitar una página que contenga únicamente sellos.

Guardar o completar una orden conserva el autor de los resultados que no cambian. Solo los resultados realmente modificados se atribuyen al usuario que los editó. La comprobación de cambios concurrentes evita sobrescribir silenciosamente resultados modificados por otro usuario mientras la orden estaba abierta.

La regeneración lee las imágenes actuales del autor guardado en cada resultado. Descargar un PDF parcial no reasigna la autoría. Para recuperar una autoría histórica con evidencia de bitácora, consulta [REPARACION_AUTORIA_RESULTADOS.md](REPARACION_AUTORIA_RESULTADOS.md).

Los comandos `lameri:*` son los nombres vigentes; los alias `oncosavi:*` se mantienen por compatibilidad.
