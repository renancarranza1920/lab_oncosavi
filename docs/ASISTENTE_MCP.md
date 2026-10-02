# Asistente del laboratorio, versión 1

El administrador accede a `/chatbot` usando su sesión habitual. También aparece **Asistente del laboratorio** en Administración. Funciona en modo claro y en móvil. No necesita una API de pago: el modelo corre en Oracle.

## Alternativas investigadas

| Alternativa | Encaje en este proyecto |
|---|---|
| [Laravel MCP](https://github.com/laravel/mcp) | Elegida: servidor oficial integrado con Laravel, middleware, sesión y validación existentes. |
| [FastMCP](https://github.com/PrefectHQ/fastmcp) | Viable; requiere otro servicio Python y mantener su autenticación y acceso a MySQL. |
| [SDK PHP de MCP](https://github.com/modelcontextprotocol/php-sdk) | Viable; su documentación lo considera experimental antes de la primera versión mayor. Requiere más integración manual. |

MCP expone herramientas; el modelo interpreta lenguaje natural. Esta versión usa Laravel MCP 1.0.1 y [Ollama](https://github.com/ollama/ollama) 0.35.0 con `qwen3:1.7b`. La imagen se fija por digest y se verificó que publica `linux/arm64`, arquitectura de Oracle A1. Laravel se actualizó dentro de la versión 12 para cumplir requisitos del paquete. Las dependencias se resolvieron para PHP 8.3, el PHP de Docker.

## Qué puedes preguntar

- «Dame un resumen de hoy».
- «¿Cuántas órdenes están pendientes este mes?».
- «Muéstrame los importes de los últimos 7 días».
- «¿Qué exámenes se solicitan más?».
- «Lista las últimas órdenes».
- «¿Cuántos clientes nuevos registramos?».
- «¿Y ayer?» después de una consulta.

También hay seis botones de consulta rápida y fechas seleccionables. Cada respuesta presenta tabla real, periodo, filtro, fuente y hora de consulta. Se puede descargar CSV. Si el modelo no está listo o excede el tiempo permitido, las consultas rápidas siguen funcionando; las preguntas reconocibles usan un respaldo guiado, identificado en la respuesta.

Los importes salen de `ordens.total`. Las sumas excluyen canceladas, pero **no representan pagos comprobados, utilidad ni saldo pendiente de cobro**. Clientes nuevos cuenta altas, no visitas. Exámenes populares cuenta líneas de exámenes en órdenes vigentes. Órdenes recientes puede incluir canceladas si no se filtra por estado. El límite de exámenes/órdenes recientes es 20; el rango máximo es 366 días.

## Arquitectura y acceso

```mermaid
flowchart LR
    A[Administrador autenticado] --> B[Chat en Laravel]
    B --> C[Ollama privado: interpreta la pregunta]
    C --> D[Parámetros validados de informe]
    D --> E[Consultas definidas de Laravel]
    E --> F[(MySQL interno)]
    E --> G[Tabla real y descarga CSV]
    H[Cliente MCP con sesión y CSRF] --> I[/mcp/laboratorio]
    I --> E
```

Chat y MCP comparten `InformesLaboratorio`; el chat no necesita una conexión HTTP a sí mismo. El modelo solo recibe la pregunta, fechas seleccionadas y contexto del informe anterior. No recibe filas de la BD, credenciales ni resultados clínicos. Las cifras se muestran directamente desde las consultas, sin una segunda redacción del modelo que pudiera inventarlas. Un modelo pequeño puede seleccionar un informe o periodo incorrectos; la tabla siempre presenta los filtros aplicados.

- Solo rol `admin` con permiso `access_admin_panel`. Recepción y Laboratorista reciben 403, incluso mediante peticiones directas. Los informes financieros también exigen `ingresos_diarios`.
- Formularios y MCP usan sesión Laravel y CSRF; no se habilita acceso anónimo ni un token público.
- No hay SQL libre, cambios de datos de negocio, datos clínicos ni nombres/contactos de pacientes en estas herramientas.
- Los parámetros se validan después de la respuesta del modelo. Campos extra, informes desconocidos y rangos fuera de límite se rechazan.
- Hasta 15 peticiones por minuto por usuario; una inferencia a la vez. Ollama limita a un modelo cargado, dos peticiones en cola, 4 GB y 2 CPU. Contexto de 2048 tokens.
- Ollama y MySQL no publican puertos. El navegador solo se comunica con Laravel. Ollama no recibe las variables privadas de `.env`.
- Bitácora con autor, tipo de informe, periodo y cantidad de filas; no guarda el texto de la pregunta. El historial del chat no se conserva al recargar.
- CSP, protección contra marcos, respuestas sin caché y texto escapado. CSV neutraliza fórmulas.

Se utiliza la cuenta SQL de Laravel. La restricción de lectura de negocio está en las herramientas; la cuenta SQL no es de solo lectura, pues Laravel necesita escribir órdenes, sesiones y bitácora. Un usuario SQL dedicado con permisos SELECT sería una mejora posterior de aislamiento.

El endpoint MCP `/mcp/laboratorio` expone `consultar_laboratorio`, compatible con initialize, tools/list y tools/call. Esta versión requiere sesión + token CSRF: **todavía no configura OAuth para conectar clientes externos como Claude Desktop**. Ejemplo JSON-RPC autenticado desde la misma aplicación:

```json
{"jsonrpc":"2.0","id":1,"method":"tools/call","params":{"name":"consultar_laboratorio","arguments":{"informe":"resumen","desde":"2026-10-01","hasta":"2026-10-02"}}}
```

## Desplegar en Oracle sin borrar volúmenes

Desde una copia limpia de `main`:

```bash
cd ~/lab_oncosavi
git pull --ff-only origin main
docker compose up -d --build
docker compose exec app php artisan optimize:clear
```

No necesita migraciones ni un seeder nuevo. Conserva `mysql_data` y `laravel_storage`; agrega `ollama_models`. No usar `down -v`. Caddy puede conservar el proxy actual hacia `127.0.0.1:8000`.

`ollama-init` descarga el modelo automáticamente. Puede tardar varios minutos y necesita salida de Oracle a los servidores de modelos de Ollama. La etiqueta del modelo puede actualizar su contenido al ejecutar un pull; esta versión conserva los archivos en el volumen persistente. Hasta terminar, usar consultas rápidas. Comprobar progreso:

```bash
docker compose logs -f ollama-init
docker compose exec ollama ollama list
docker compose ps
docker stats
```

`ollama-init` debe terminar con código 0; no permanece ejecutándose. Si la descarga falla, reintentar sin borrar el volumen:

```bash
docker compose run --rm ollama-init
```

Abrir `https://applab.oncosavi.com/chatbot` y entrar con Admin. No se crean nuevos usuarios privilegiados para este cambio.

Variables opcionales, innecesarias para el primer despliegue:

```dotenv
CHATBOT_ENABLED=true
CHATBOT_LOCAL_MODEL=qwen3:1.7b
```

Tras cambiar el modelo, ejecutar `docker compose up -d --force-recreate app ollama-init`. Para desactivar el chat usar `CHATBOT_ENABLED=false` y recrear `app`. No hay API de IA de pago; siguen aplicando las condiciones de Oracle al uso de instancia y almacenamiento. No se promete gratuidad permanente.

El `.env` compartido no se incluyó en Git ni en imágenes. Para un sitio accesible por Internet, usar `APP_ENV=production` y `APP_DEBUG=false`. Cambiar contraseñas en `.env` no modifica automáticamente usuarios de un MySQL con volumen existente: hay que aplicar la rotación también en MySQL. Cambiar `APP_KEY` invalida sesiones y puede afectar datos cifrados; no se regeneró durante este cambio.

## Verificación y límites

`ChatbotTest` verifica los seis informes contra datos de prueba, permisos, parámetros maliciosos, límites, bitácora, protocolo MCP real, respaldo guiado, seguimiento y concurrencia. La llamada a Ollama se simula en esas pruebas. Chromium verifica sesión, tablas reales de una BD local, CSV, CSRF, escape de HTML y móvil.

```bash
DB_CONNECTION=sqlite DB_DATABASE=:memory: php -d memory_limit=512M vendor/bin/phpunit
npm run build
docker compose config --quiet
```

La descarga del modelo se bloqueó por la política de red del entorno de desarrollo. El build completo de Docker se detuvo por un 429 de Docker Hub; la auditoría de avisos de Composer también se bloqueó por acceso a Packagist. No se eludieron esas restricciones. Se verificaron configuración Compose, manifiestos ARM64, dependencias, aplicación y protocolo; **no se midieron velocidad ni precisión reales de Qwen en Oracle, ni se completó el build de contenedores aquí**. El primer despliegue y las preguntas reales deben comprobarlo en el servidor.
