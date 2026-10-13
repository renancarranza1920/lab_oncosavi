# Web pública de Laboratorio Clínico Merino

Esta entrega corresponde a **laboratorioclinicomerino.com** y se sube manualmente. Conserva la web original y añade un asistente de respuestas predefinidas, sin servicios de inteligencia artificial. Consulta el catálogo de la **base del Lameri anterior**, como pediste.

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
│       └── catalogo.php
└── configuracion/                       ← carpeta privada, fuera de public_html
    ├── .htaccess
    └── lameri-publica.php.example
```

1. Descarga un respaldo del `public_html` actual y guárdalo fuera de la carpeta pública.
2. Sube `lameri_web_hostinger.zip` al padre de `public_html` y extráelo allí. Reemplaza los archivos de la web con los del ZIP. Confirma que `index.html` esté directamente dentro de `public_html`, sin otra carpeta `public_html` anidada.
3. Mantén `configuracion` junto a `public_html`. Esa carpeta guarda la conexión privada, y el navegador nunca recibe su contraseña.
4. Si el Administrador de archivos abre directamente `public_html`, sube allí **solo su contenido** y crea/sube la carpeta `configuracion` en el directorio superior por el administrador de archivos o SFTP.

La web requiere PHP 8.2 o superior con la extensión **pdo_mysql** habilitada. Puedes usar PHP 8.3 en hPanel → Configuración de PHP. Los archivos ya están preparados para usarse en Hostinger.

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

Para actualizar esta web, reemplaza manualmente los archivos de `public_html` de la entrega nueva. Conserva `configuracion/lameri-publica.php` y sus credenciales. Los archivos de este proyecto se entregan separados del repositorio del sistema clínico.
