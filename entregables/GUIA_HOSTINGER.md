# Web pública de Laboratorio Clínico Merino

Esta entrega corresponde a **laboratorioclinicomerino.com**. Puedes instalarla desde el ZIP y después actualizarla por comandos Git. Conserva la web original y añade un asistente que reconoce nombres, siglas y errores de escritura, con IA opcional para consultas más difíciles. Consulta el catálogo de la **base del Lameri anterior**, como pediste.

## Dónde extraer el ZIP

En el Administrador de archivos de Hostinger abre la carpeta del dominio principal, **el padre de `public_html`**:

```text
domains/laboratorioclinicomerino.com/
├── public_html/                         ← web visible del dominio
│   ├── index.html
│   ├── .htaccess
│   ├── logo.png
│   ├── iconlab.png
│   ├── assets/
│   └── api/
│       ├── catalogo.php
│       └── interpretar.php
└── configuracion/                       ← carpeta privada, fuera de public_html
    ├── .htaccess
    └── lameri-publica.php.example
```

1. Descarga un respaldo del `public_html` actual y guárdalo fuera de la carpeta pública.
2. Sube `lameri_web_hostinger.zip` al padre de `public_html` y extráelo allí. Reemplaza los archivos de la web con los del ZIP. Confirma que `index.html` esté directamente dentro de `public_html`, sin otra carpeta `public_html` anidada.
3. Mantén `configuracion` junto a `public_html`. Esa carpeta guarda la conexión privada, y el navegador nunca recibe su contraseña.
4. Si el Administrador de archivos abre directamente `public_html`, sube allí **solo su contenido** y crea/sube la carpeta `configuracion` en el directorio superior por el administrador de archivos o SFTP.

La web requiere PHP 8.2 o superior con la extensión **pdo_mysql** habilitada. La IA opcional necesita además **curl**. Puedes habilitar estas extensiones desde hPanel → Configuración de PHP. Los archivos ya están preparados para usarse en Hostinger.

## Conectar la base anterior

Dentro de `configuracion`, cambia el nombre de:

```text
lameri-publica.php.example
```

a:

```text
lameri-publica.php
```

Ábrelo con el editor y sustituye los datos del bloque `mysql`. Usa **los mismos datos de conexión del Lameri anterior**, que puedes consultar en su `.env` privado o en hPanel → Bases de datos MySQL:

| Configuración de esta web | Dato de la instalación anterior |
| --- | --- |
| `host` | `DB_HOST` |
| `port` | `DB_PORT` (normalmente 3306) |
| `database` | `DB_DATABASE` |
| `username` | `DB_USERNAME` |
| `password` | `DB_PASSWORD` |

Respeta el nombre completo de base y usuario, incluido el prefijo de Hostinger. Copia el valor de cada variable, sin las comillas externas de `.env`. El archivo PHP usa cadenas entre comillas simples: si un valor contiene una comilla simple, escríbela como `\'`; si contiene una barra invertida, escríbela como `\\`.

Puedes configurar un usuario de solo lectura si tu hosting lo permite. La web únicamente consulta `examens`, `tipo_examens`, `perfil` y `detalle_perfil`, y necesita permiso `SELECT` sobre esas tablas. Su código no modifica la base ni consulta pacientes, órdenes, resultados, médicos, usuarios o credenciales del sistema clínico.

No crees una base nueva, no importes un SQL y no ejecutes migraciones ni seeders para esta web. La conexión lee el catálogo existente. Mantén la contraseña fuera de `public_html` y no la envíes por el chat.

## Comprobar que funciona

1. Abre **https://laboratorioclinicomerino.com** y recarga la página sin caché si todavía ves la versión anterior.
2. Abre el botón del asistente y escribe `precio de la glucosa`, `perfiles disponibles` o `todos los exámenes`.
3. Compara un precio de examen y el precio de un perfil con el panel del **Lameri anterior**. El perfil muestra su precio propio y los exámenes que incluye.
4. Comprueba ubicación, horarios, teléfono, WhatsApp y servicio a domicilio. Los enlaces de WhatsApp usan el móvil **+503 7595-4210**.
5. La consulta pública **https://laboratorioclinicomerino.com/api/catalogo.php** debe responder JSON con `success: true`. Solo contiene campos públicos del catálogo.

Los cambios de precios del sistema anterior aparecen tras renovar el catálogo, normalmente en un minuto. La conexión se comprueba antes de utilizar la caché del servidor. Si la base no está accesible, el bot avisa que los precios no están disponibles; conserva las respuestas de información general.

## Reconocimiento de nombres e IA opcional

Tras actualizar, el bot ya entiende nombres como `antígeno prostático`, `PSA`, `HbA1c`, `hemoglobina glucosilada`, `CBC`, `biometría hemática` y `colesterol bueno`. También reconoce siglas con puntos, como `P.S.A.`. Ante un error como `glucoas`, sugiere el nombre y te permite elegir antes de mostrar su precio. Si consultas PSA y luego escribes `¿y el libre?`, mantiene el contexto. Conserva separadas las variantes total/libre, cualitativa/cuantitativa y PCR molecular/proteína C reactiva.

Los nombres de exámenes, perfiles, exámenes incluidos y sugerencias del bot aparecen en MAYÚSCULAS, conservando las tildes.

Estas mejoras funcionan sin pagar una API. Para añadir IA real a las consultas que no se resuelven localmente:

1. Actualiza la web con el comando de **Actualizaciones siguientes**.
2. Crea una [clave API de OpenAI](https://platform.openai.com/api-keys) y configura facturación y el presupuesto del proyecto. La API tiene costo por uso aparte de ChatGPT.
3. Abre tu archivo privado **existente** `configuracion/lameri-publica.php` en Hostinger. Conserva los bloques `mysql` y `contacto` y añade, dentro del mismo `return [...]`:

```php
'ia' => [
    'enabled' => true,
    'api_key' => 'TU_CLAVE_API_DE_OPENAI',
    'model' => 'gpt-4.1-mini',
    'daily_request_limit' => 100,
],
```

4. Habilita `curl` en PHP y guarda el archivo. No copies la clave al HTML, a JavaScript ni a Git.
5. Recarga la web y consulta un nombre poco habitual de un examen real. `/api/catalogo.php` mostrará `asistente.ia: true` cuando estén configuradas la clave, la activación y `curl`; esto indica que la IA está habilitada, no que una llamada al proveedor haya sido comprobada.

La configuración antigua sigue funcionando aunque no añadas este bloque. La IA permanece desactivada hasta configurarla y puedes apagarla con `enabled => false`. No reemplaces tu archivo privado por el ejemplo: perderías los datos MySQL que ya configuraste.

Cuando se usa IA, PHP envía a OpenAI el texto de la consulta, los nombres públicos de exámenes/perfiles y referencias a la última búsqueda; no envía precios, credenciales ni datos de pacientes de la base. La web no guarda conversaciones. Las consultas sobre resultados, identificadores o síntomas se remiten a contacto. La IA devuelve solamente identificadores de catálogo validados; todos los precios mostrados siguen procediendo de MySQL.

El máximo inicial es de **100 intentos diarios (UTC)** y **6 por minuto por IP**. Puedes ajustar el máximo diario en el archivo privado. El contador se guarda en `configuracion/cache/ia-limites.json`, sin preguntas ni IPs en texto, y necesita escritura de PHP. Es un límite de cantidad de solicitudes, no de dinero; establece también tu presupuesto en OpenAI. Si la IA no está disponible o se alcanza el límite, el reconocimiento local continúa funcionando.

## Qué puede responder

- Catálogo, búsqueda de exámenes, categorías y precios activos en USD.
- Perfiles activos, precio propio y lista de exámenes incluidos.
- Dirección, ubicación en mapas, horarios, teléfono, correo, WhatsApp y redes públicas.
- Áreas de análisis y cómo solicitar el servicio a domicilio.
- Propósito, misión y visión publicados del laboratorio.
- Entrega de resultados y consultas que deben confirmarse con recepción.

Las indicaciones de preparación, tiempo de entrega específico, pagos, seguros y disponibilidad se remiten a recepción cuando no hay información publicada y validada. El asistente no inventa precios, instrucciones médicas ni acceso a expedientes.

La base anterior exige al menos dos exámenes activos para mantener activo un perfil. La consulta pública respeta esa regla y también oculta categorías y exámenes inactivos.

## Si no aparecen precios

- Comprueba que `configuracion/lameri-publica.php` esté **fuera de `public_html`**, en el padre, y con ese nombre exacto.
- Verifica host, puerto, base, usuario y contraseña del **Lameri anterior**.
- Habilita `pdo_mysql` en la configuración PHP del dominio. PHP debe tener acceso a la base MySQL.
- Confirma que el usuario tenga permisos de lectura sobre las cuatro tablas indicadas.
- Permite que PHP lea la configuración y escriba dentro de `configuracion/cache`, que se crea automáticamente. Si el hosting impide escribir la caché, el catálogo puede consultarse directamente sin ella.
- El endpoint devuelve un aviso general si hay un problema de conexión; las respuestas generales del asistente siguen disponibles.

## Editar respuestas y actualizar esta web

Los datos públicos y respuestas se mantienen en `public_html/assets/merino-chat-config.js`. Puedes editar los textos con el editor de Hostinger. Los precios siempre proceden de MySQL. Las modificaciones del contenido de la página original y del asistente deben conservar sus datos de contacto y horarios coherentes.

Para actualizarla manualmente, reemplaza los archivos de `public_html` de la entrega nueva y conserva `configuracion/lameri-publica.php` y sus credenciales. Para utilizar comandos desde SSH, sigue el procedimiento de abajo.

## Conectar la web a Git una sola vez

Este procedimiento se ejecuta en la carpeta del **dominio principal**, donde están `public_html` y `configuracion`. La rama de esta web es **`lameri_web_publica`**. El conector incorpora Git a la instalación actual y conserva su ubicación.

```bash
cd ~/domains/laboratorioclinicomerino.com
LAMERI_WEB_GIT_TMP="$(mktemp -d)"
git clone --depth 1 --single-branch --branch lameri_web_publica \
  https://github.com/renancarranza1920/lab_oncosavi.git "$LAMERI_WEB_GIT_TMP" && \
bash "$LAMERI_WEB_GIT_TMP/scripts/connect-hostinger-git.sh" "$PWD"
```

El script comprueba la carpeta y la rama, crea un respaldo completo privado en `~/lameri-web-backups` y sustituye únicamente archivos versionados de esta web. Mantiene tu configuración privada de MySQL, su caché y los archivos que no están en Git. No cambia la base de datos. La carpeta del respaldo se muestra en la terminal. Conserva el clon temporal como referencia si lo deseas; las siguientes actualizaciones usan la carpeta del dominio.

El conector se detiene si la carpeta ya tiene Git. En ese caso comprueba `git branch --show-current`: debe decir `lameri_web_publica`. Si está correctamente conectada, utiliza el actualizador de la sección siguiente.

Si todavía no configuraste MySQL, completa `configuracion/lameri-publica.php` según **Conectar la base anterior**. Conectar Git no crea ni cambia esas credenciales.

## Actualizaciones siguientes

Cuando publique nuevos cambios de la web en la rama `lameri_web_publica`, ejecuta:

```bash
cd ~/domains/laboratorioclinicomerino.com
bash scripts/update-hostinger.sh
```

Solo necesitas Git y PHP 8.2 o superior en SSH. El script descarga la rama, comprueba que la actualización permite avanzar sin mezclar historias, valida el código PHP entregado, respalda la instalación y aplica esa versión. Conserva `configuracion/lameri-publica.php`, la caché privada y los archivos ajenos a Git. La actualización no ejecuta consultas SQL, migraciones, seeders ni instalaciones de dependencias.

Si tu terminal necesita una ruta específica de PHP, puedes indicarla:

```bash
LAMERI_WEB_PHP_BIN=/opt/alt/php83/usr/bin/php bash scripts/update-hostinger.sh
```

Si editaste archivos versionados directamente en Hostinger, el script se detiene para conservarlos. Revisa `git status --short` y lleva esos cambios a la rama antes de actualizar. No hace reset ni elimina archivos locales.

Los precios, perfiles y exámenes se modifican en **el Lameri anterior** y se reflejan en la web normalmente en un minuto. No necesitas ejecutar Git por un cambio de precios. Los comandos anteriores actualizan el diseño, las respuestas y la funcionalidad de la web pública.
