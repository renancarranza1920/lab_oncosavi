# Web pública de Laboratorio Clínico Merino

Esta rama contiene la web del dominio principal y su asistente de consultas. Reconoce siglas, sinónimos, errores de escritura y preguntas de seguimiento; admite IA opcional para identificar nombres más difíciles. Conserva el diseño original y consulta exámenes y perfiles activos de la **base del Lameri anterior**. La aplicación clínica se mantiene en sus otras ramas.

## Descargar e instalar manualmente

- [Descargar ZIP preparado para Hostinger](https://github.com/renancarranza1920/lab_oncosavi/raw/refs/heads/lameri_web_publica/entregables/lameri_web_hostinger.zip?download=true)
- [Guía de instalación](entregables/GUIA_HOSTINGER.md)

Extrae el ZIP en el padre de `public_html` del dominio `laboratorioclinicomerino.com`. Renombra `configuracion/lameri-publica.php.example` a `lameri-publica.php` y completa la conexión a MySQL del sistema anterior. La configuración y su caché quedan fuera del directorio público; la configuración real se excluye de Git.

Requiere PHP 8.2 o superior y `pdo_mysql`. La entrega contiene los archivos preparados para subirlos manualmente a Hostinger.

## Qué responde el asistente

Precios de exámenes, perfiles e incluidos; horarios, dirección y mapas; teléfono, WhatsApp, correo y redes; áreas de servicio, domicilio, propósito, misión y visión. Las indicaciones específicas de preparación, pagos, seguros y entrega de resultados se confirman con recepción. Las respuestas de información general funcionan aunque la base esté temporalmente inaccesible.

El reconocimiento local funciona sin claves ni servicios externos. Por ejemplo, `antígeno prostático`, `examen de próstata` y `PSA` encuentran las variantes publicadas; `HbA1c` reconoce hemoglobina glicosilada y `CBC` reconoce hemograma. Distingue variantes y pide elegir ante errores de escritura. Después de consultar PSA, `¿y el libre?` conserva el contexto de la búsqueda.

La IA está desactivada inicialmente. Si la habilitas, las consultas que no se resuelven localmente se envían desde PHP a la API de OpenAI junto con nombres públicos del catálogo y referencias a la última búsqueda. La clave permanece en `configuracion/lameri-publica.php`, fuera de `public_html`; no se envían contraseñas ni datos de la base clínica. El modelo solo puede devolver identificadores publicados: los nombres y precios que ve el visitante proceden del catálogo, nunca del texto generado. La web no guarda conversaciones; los contadores privados de uso guardan cantidades y hashes de IP, sin preguntas.

Los endpoints consultan únicamente `examens`, `tipo_examens`, `perfil` y `detalle_perfil`; conservan el precio propio de cada perfil y respetan los estados activos. Las consultas clínicas se remiten a recepción. La web no modifica la base.

## Activar la IA opcional

Tras actualizar, agrega este bloque dentro del `return [...]` de tu archivo privado **existente** `configuracion/lameri-publica.php`, conservando `mysql` y `contacto`:

```php
'ia' => [
    'enabled' => true,
    'api_key' => 'TU_CLAVE_API_DE_OPENAI',
    'model' => 'gpt-4.1-mini',
    'daily_request_limit' => 100,
],
```

Necesita la extensión PHP `curl` y una [clave API de OpenAI](https://platform.openai.com/api-keys) con acceso al modelo y facturación configurada. El uso de la API se cobra aparte de las suscripciones de ChatGPT. La configuración anterior sigue funcionando aunque no añadas `ia`. Puedes desactivarla cambiando `enabled` a `false`.

La IA se usa como ayuda para identificar nombres, después del reconocimiento local. Hay un máximo inicial de 100 intentos al día (UTC), configurable, y 6 por minuto por IP. Los límites se guardan en `configuracion/cache/ia-limites.json`, con permisos privados. Si se agota el límite, falla el proveedor o no se puede guardar el contador, la búsqueda local sigue funcionando. El límite controla cantidades de solicitudes, no un importe monetario; configura también el presupuesto de tu proyecto en OpenAI.

La integración usa [Responses y salidas estructuradas](https://developers.openai.com/api/docs/guides/structured-outputs), con `store: false`, conexión HTTPS y tiempos de espera limitados.

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

Consultas comprobadas contra un esquema de prueba del sistema anterior; sinónimos, siglas, variantes, contexto y respuestas de IA verificadas. Navegación probada en escritorio y móviles de 390 y 320 px, con actualización de precios, errores del proveedor y contenido HTML tratado como texto. Las pruebas de IA simulan respuestas HTTP y no realizan llamadas facturables. La conexión real se activa al completar la configuración en Hostinger.

Para probar en un entorno de desarrollo:

```bash
php tests/catalogo.php
php tests/interpretacion.php
node --experimental-vm-modules tests/chatbot.mjs
```

`tests/navegador.mjs` añade comprobaciones con Playwright contra un servidor PHP local. Hostinger no necesita Node, Composer ni Docker para esta web.
