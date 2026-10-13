# Web pública de Laboratorio Clínico Merino

Esta rama contiene la web del dominio principal y su asistente de consultas. Reconoce siglas, sinónimos, errores de escritura y preguntas de seguimiento mediante reglas locales, sin claves de servicios externos ni cobros por consulta. Conserva el diseño original y consulta exámenes y perfiles activos de la **base del Lameri anterior**. La aplicación clínica se mantiene en sus otras ramas.

## Descargar e instalar manualmente

- [Descargar ZIP preparado para Hostinger](https://github.com/renancarranza1920/lab_oncosavi/raw/refs/heads/lameri_web_publica/entregables/lameri_web_hostinger.zip?download=true)
- [Guía de instalación](entregables/GUIA_HOSTINGER.md)

Extrae el ZIP en el padre de `public_html` del dominio `laboratorioclinicomerino.com`. Renombra `configuracion/lameri-publica.php.example` a `lameri-publica.php` y completa la conexión a MySQL del sistema anterior. La configuración y su caché quedan fuera del directorio público; la configuración real se excluye de Git.

Requiere PHP 8.2 o superior y `pdo_mysql`. La entrega contiene los archivos preparados para subirlos manualmente a Hostinger.

## Qué responde el asistente

Precios de exámenes, perfiles e incluidos; horarios, dirección y mapas; teléfono, WhatsApp, correo y redes; áreas de servicio, domicilio, propósito, misión y visión. Las indicaciones específicas de preparación, pagos, seguros y entrega de resultados se confirman con recepción. Las respuestas de información general funcionan aunque la base esté temporalmente inaccesible.

El reconocimiento local funciona sin claves ni servicios externos. Por ejemplo, `antígeno prostático`, `examen de próstata` y `PSA` encuentran las variantes publicadas; `HbA1c` reconoce hemoglobina glicosilada y `CBC` reconoce hemograma. Distingue variantes y pide elegir ante errores de escritura. Después de consultar PSA, `¿y el libre?` conserva el contexto de la búsqueda.

Los nombres de exámenes y perfiles se muestran en MAYÚSCULAS, incluidos los miembros de cada perfil y las opciones sugeridas. El formato se aplica al mostrarlos y conserva las tildes.

Las preguntas y el contexto se procesan en el navegador. El bot usa un vocabulario de equivalencias y comparación de nombres; ofrece coincidencias del catálogo y remite a recepción cuando no reconoce una consulta. No guarda conversaciones ni envía preguntas a proveedores externos.

La API consulta únicamente `examens`, `tipo_examens`, `perfil` y `detalle_perfil`; conserva el precio propio de cada perfil y respeta los estados activos. Las consultas clínicas se remiten a recepción. La web no modifica la base.

## Funcionamiento sin clave ni API de pago

Las mejoras de reconocimiento funcionan con la configuración MySQL que ya tiene la web. No requieren activar otra cuenta, añadir claves ni habilitar `curl`. El bot reconoce, por ejemplo, `antígeno prostático`, `examen de próstata`, `HbA1c`, `CBC`, `colesterol bueno` y variantes como total/libre. Conserva el contexto de la búsqueda para `¿y el libre?` y sugiere nombres ante errores de escritura.

Esta versión elimina la integración con proveedores de IA. La ruta antigua `api/interpretar.php` devuelve HTTP 410 sin leer claves ni realizar llamadas externas, para que también las instalaciones actualizadas desde un ZIP dejen de utilizarla. Los campos antiguos de IA en el archivo privado se ignoran; la conexión MySQL se conserva.

## Actualizar por comandos en Hostinger

Si instalaste el ZIP, conecta una sola vez la carpeta actual del dominio a Git desde SSH:

```bash
cd ~/domains/laboratorioclinicomerino.com
LAMERI_WEB_GIT_TMP="$(mktemp -d)"
git clone --depth 1 --single-branch --branch lameri_web_publica \
  https://github.com/renancarranza1920/lab_oncosavi.git "$LAMERI_WEB_GIT_TMP" && \
bash "$LAMERI_WEB_GIT_TMP/scripts/connect-hostinger-git.sh" "$PWD"
```

El conector respalda la web en una carpeta privada y mantiene la ubicación actual del dominio. Conserva `configuracion/lameri-publica.php`, su caché y los archivos ajenos a Git. La configuración privada de MySQL se completa según la guía si todavía falta.

Después, las siguientes actualizaciones utilizan:

```bash
cd ~/domains/laboratorioclinicomerino.com
bash scripts/update-hostinger.sh
```

El actualizador comprueba la rama y los cambios locales, obtiene la versión publicada, valida el código PHP y guarda un respaldo privado antes de actualizar. Conserva las credenciales y no ejecuta operaciones sobre MySQL. Las modificaciones locales de archivos versionados deben revisarse antes de volver a actualizar.

Los precios y el contenido del catálogo se actualizan desde la base anterior, normalmente en un minuto; no requieren actualizar el código con Git. Los scripts de esta rama corresponden a la web del dominio principal.

## Archivos

`public_html/` contiene la página original, el asistente y la API del catálogo. `configuracion/` incluye únicamente el ejemplo privado de conexión. `scripts/` permite conectar la instalación y actualizarla desde SSH. `entregables/` contiene el ZIP y la guía para instalar esta entrega.

## Validación

Consultas comprobadas contra un esquema de prueba del sistema anterior; sinónimos, siglas, variantes y contexto verificados. Navegación probada en escritorio y móviles de 390 y 320 px, con actualización de precios, fallos de conexión, ausencia de consultas a intérpretes externos y contenido HTML tratado como texto. La conexión a la base real utiliza tu configuración MySQL de Hostinger.

Para probar en un entorno de desarrollo:

```bash
php tests/catalogo.php
node --experimental-vm-modules tests/chatbot.mjs
```

`tests/navegador.mjs` añade comprobaciones con Playwright contra un servidor PHP local. Hostinger no necesita Node, Composer ni Docker para esta web.
