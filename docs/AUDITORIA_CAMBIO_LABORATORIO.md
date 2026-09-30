Identidad ONCOSAVI — 28 de septiembre de 2026

Datos institucionales centralizados en `config/laboratorio.php`.

- Nombre: ONCOSAVI, San Vicente.
- Correo: oncosavi@gmail.com.
- Llamadas y WhatsApp: +503 2393 0239.
- Dirección: Calle 1 de Julio #23, Bo. San Francisco, San Vicente, El Salvador. Esquina opuesta a la Procuraduría.
- Paleta extraída del logo: azul marino #090B3B, celeste #64ABC6, rojo #E32737 y blanco #FFFFFF.
- Logo original en `public/images/oncosavi.png` y copia para despliegue en `public_hostinger/images/oncosavi.png`.

Panel, acceso, favicon, contactos, gráficos, etiquetas, mensajes y cinco plantillas PDF usan la nueva identidad. Los colores clínicos de recipientes conservan su significado. La marca de agua de resultados es texto diagonal tenue, sin imágenes. El sello institucional no se incluye hasta disponer de uno autorizado.

Por indicación expresa del usuario, se conservan documentos clínicos emitidos y firmas y sellos personales. Se comprobaron sus hashes antes y después de la limpieza: 65 archivos intactos. Se retiraron 57 recursos institucionales y archivos derivados de caché. No se modifican pacientes, resultados, usuarios existentes ni credenciales de conexión. El historial de Git y el nombre físico de la carpeta de trabajo no se reescriben.

La URL local es `http://127.0.0.1:8000` para `php artisan serve`. Antes de desplegar se debe establecer APP_URL con el dominio real de ONCOSAVI. El correo de contacto y el remitente se actualizaron; la entrega SMTP requiere la configuración operativa de la cuenta. Los mensajes a pacientes siguen requiriendo adjuntar manualmente su PDF.

El seeder crea la cuenta institucional con una contraseña aleatoria y requiere restablecerla mediante el flujo de recuperación. No se ejecutó sobre la base existente.

Validación automatizada: login y enlaces de contacto; generación de los cinco tipos de PDF con datos ficticios; resultados multipágina y marca de agua sin logo. Las muestras se guardan en `storage/framework/testing/oncosavi/`. Las tablas de resultados se dividen entre páginas sin una tabla externa que provoque desbordamientos; las firmas personales siguen dentro del pie de cada tabla de resultados.

Resultado final: 6 pruebas aprobadas con 63 aserciones; sintaxis PHP comprobada en 186 archivos y compilación Blade correcta. Los cinco PDF se generaron; la revisión del reporte largo confirmó 65 filas distribuidas en tres páginas, con cabeceras y pies dentro del papel. El logo instalado coincide byte a byte con el original proporcionado. La búsqueda final no encontró referencias a la identidad institucional reemplazada en código, configuración, recursos de aplicación ni documentación activa.

Pendientes técnicos fuera del cambio de identidad: revisión de firma de impresión QZ, autorización de rutas de documentos, almacenamiento de resultados y prueba física de impresoras. No se realizó publicación ni envío de mensajes.

Corrección del catálogo impreso — 28 de septiembre de 2026

El PDF recibido tenía cuatro páginas por una tabla exterior de tres columnas que no podía paginar correctamente; dejaba el encabezado y el pie en páginas separadas y recortaba contenido. La comprobación previa con un catálogo vacío no detectaba este problema de volumen.

La distribución ahora mide los nombres y reparte filas completas en un máximo de seis columnas sobre dos páginas carta. Repite los datos de paciente y médico, mantiene los precios y conserva cada perfil junto a sus exámenes. Incluye círculos vacíos de aproximadamente 3 mm para marcar a mano. No cambia nombres, precios ni registros de la base.

Se verificó el catálogo completo: 170 exámenes, 7 perfiles y 40 exámenes incluidos en perfiles; dos páginas y 217 círculos, sin nombres recortados ni contenido fuera de márgenes. La suite tiene 8 pruebas aprobadas y 758 aserciones. Un catálogo futuro que no quepa de forma legible muestra un aviso en lugar de descargar más páginas o perder contenido.

Formato vigente solicitado: dos páginas A4 verticales (210 × 297 mm), con tres columnas por página y tipografía de 8.5 puntos para el catálogo actual. Sin fecha de impresión, campo de fecha ni numeración. Se conservan todos los exámenes, precios, perfiles y 217 círculos. Verificación del PDF renderizado: dos páginas A4, nombres completos y contenido dentro de márgenes. Pruebas del catálogo: 2 aprobadas, 700 aserciones. Este formato sustituye el ajuste provisional de una sola hoja.

Actualización de interfaz: retiradas las franjas de contacto del pie del panel y del acceso. Estados centralizados en config/estados.php y App\Support\EstadoVisual: finalizado/activo en verde pastel, pendiente en amarillo pastel, pausado/inactivo/cancelado en rojo pastel y en proceso en azul pastel. Aplicado a tablas, expedientes, resultados, pestañas, gráficos y componentes semánticos de Filament (avisos, botones e indicadores). Contraste texto/fondo superior a 6:1 en todas las paletas; prueba de login sin contactos aprobada y sintaxis verificada en los ocho archivos PHP afectados.
