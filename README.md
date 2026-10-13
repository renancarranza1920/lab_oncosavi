# Web pública de Laboratorio Clínico Merino

Esta rama contiene la web del dominio principal y su asistente de consultas con respuestas predefinidas. Conserva el diseño original y consulta exámenes y perfiles activos de la **base del Lameri anterior**. La aplicación clínica se mantiene en sus otras ramas.

## Descargar e instalar manualmente

- [Descargar ZIP preparado para Hostinger](https://github.com/renancarranza1920/lab_oncosavi/raw/refs/heads/lameri_web_publica/entregables/lameri_web_hostinger.zip?download=true)
- [Guía de instalación](entregables/GUIA_HOSTINGER.md)

Extrae el ZIP en el padre de `public_html` del dominio `laboratorioclinicomerino.com`. Renombra `configuracion/lameri-publica.php.example` a `lameri-publica.php` y completa la conexión a MySQL del sistema anterior. La configuración y su caché quedan fuera del directorio público; la configuración real se excluye de Git.

Requiere PHP 8.2 o superior y `pdo_mysql`. La entrega contiene los archivos preparados para subirlos manualmente a Hostinger.

## Qué responde el asistente

Precios de exámenes, perfiles e incluidos; horarios, dirección y mapas; teléfono, WhatsApp, correo y redes; áreas de servicio, domicilio, propósito, misión y visión. Las indicaciones específicas de preparación, pagos, seguros y entrega de resultados se confirman con recepción. Las respuestas de información general funcionan aunque la base esté temporalmente inaccesible.

El asistente no utiliza IA, no envía el texto de las preguntas a un servidor y no guarda conversaciones. El endpoint consulta únicamente `examens`, `tipo_examens`, `perfil` y `detalle_perfil`; conserva el precio propio de cada perfil y respeta los estados activos. No consulta datos clínicos ni modifica la base.

## Archivos

`public_html/` contiene la página original, el asistente y la API del catálogo. `configuracion/` incluye únicamente el ejemplo privado de conexión. `entregables/` contiene el ZIP y la guía para instalar esta entrega.

## Validación

Consultas comprobadas contra un esquema de prueba del sistema anterior; reglas del bot y privacidad verificadas; navegación probada en escritorio y móviles de 390 y 320 px, incluyendo perfiles, coincidencias ambiguas, paginación, contenido HTML tratado como texto y fallos de conexión. La conexión a la base real se activa al completar la configuración en Hostinger.
