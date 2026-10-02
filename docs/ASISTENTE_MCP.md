# Asistente del laboratorio, versión 2

El administrador accede a `/chatbot` usando su sesión habitual. También aparece **Asistente del laboratorio** en Administración. Funciona en modo claro y en móvil. No necesita una API de pago: el modelo corre en Oracle.


## Cambios de esta versión

- «órdenes de hoy» consulta exactamente hoy en El Salvador, aunque antes estuviera seleccionado otro periodo.
- «Órdenes de Pacientes» muestra folios de órdenes sin que el modelo invente un límite de 2. «Órdenes totales dinero» devuelve la suma del periodo.
- «¿Cuántas órdenes hay?» devuelve un conteo completo; «ver órdenes» devuelve una lista. «Total de dinero» y «importes por día» son informes distintos.
- Fechas: hoy, ayer, anteayer, hace N días, últimos N días, semanas, meses, año, YYYY-MM-DD, DD/MM/YYYY y fechas en español como «del 1 al 2 de octubre de 2026». «Últimos N meses» abarca desde el primer día del mes más antiguo hasta hoy.
- Cantidades como «las últimas cinco órdenes», «top 10 exámenes más solicitados» y hasta 50 filas.
- «¿Y ayer?», «solo las pendientes» y «y todas» conservan el informe, periodo, filtro y cantidad cuando corresponde. Hay botones para esos seguimientos; Nueva consulta borra el contexto.
- Las fechas imposibles, límites excesivos y filtros de varios estados se rechazan con explicación. Las preguntas fuera de los informes disponibles no deben presentarse como si fueran una consulta válida. Sigue sin soportarse SQL libre, pagos, ganancias, resultados clínicos, búsqueda por nombre/folio ni comparación de dos periodos.
- Qwen 3 de **4B** reemplaza al de 1.7B para las formulaciones libres. Su descarga ocupa más disco y su inferencia puede tardar más; las consultas básicas evitan esa espera.

Prueba después de actualizar: «órdenes de hoy» → «cuántas órdenes hay hoy» → «total de dinero de hoy» → «¿y ayer?» → «ver las últimas cinco órdenes» → «solo las pendientes». Verifica el periodo mostrado y las cifras contra Órdenes.

## Alternativas investigadas

| Alternativa | Encaje en este proyecto |
|---|---|
| [Laravel MCP](https://github.com/laravel/mcp) | Elegida: servidor oficial integrado con Laravel, middleware, sesión y validación existentes. |
| [FastMCP](https://github.com/PrefectHQ/fastmcp) | Viable; requiere otro servicio Python y mantener su autenticación y acceso a MySQL. |
| [SDK PHP de MCP](https://github.com/modelcontextprotocol/php-sdk) | Viable; su documentación lo considera experimental antes de la primera versión mayor. Requiere más integración manual. |

MCP expone herramientas; el modelo interpreta lenguaje natural. Esta versión usa Laravel MCP 1.0.1 y [Ollama](https://github.com/ollama/ollama) 0.35.0 con `qwen3:4b`. La imagen se fija por digest y se verificó que publica `linux/arm64`, arquitectura de Oracle A1. Laravel se actualizó dentro de la versión 22 para cumplir requisitos del paquete. Las dependencias se resolvieron para PHP 8.3, el PHP de Docker.

## Qué puedes preguntar

- «Dame un resumen de hoy».
- «¿Cuántas órdenes están pendientes este mes?».
- «Muéstrame los importes de los últimos 7 días».
- «¿Qué exámenes se solicitan más?».
- «Lista las últimas órdenes».
- «¿Cuántos clientes nuevos registramos?».
- «¿Y ayer?» después de una consulta.

También hay nueve botones de consulta rápida y fechas seleccionables. Cada respuesta presenta una explicación breve calculada con los datos reales, tabla, periodo, filtro, fuente y hora de consulta. Las fechas de los controles se actualizan al periodo que se acaba de consultar. Se puede descargar CSV. Las preguntas básicas reconocidas se resuelven directamente sin esperar a Ollama y aparecen como CONSULTA GUIADA. Ollama interpreta las formulaciones restantes y aparecen como CONSULTA CON IA LOCAL. El estado «IA local lista» indica que está descargado el modelo, no que todas las preguntas necesiten inferencia.

Los importes salen de `ordens.total`. Las sumas excluyen canceladas, pero **no representan pagos comprobados, utilidad ni saldo pendiente de cobro**. Clientes nuevos cuenta altas, no visitas. Exámenes populares cuenta líneas de exámenes en órdenes vigentes. Órdenes recientes puede incluir canceladas si no se filtra por estado. Las listas muestran 25 filas por defecto y admiten hasta 50 si se solicitan; los conteos y sumas usan todos los registros del periodo, sin truncarlos. Pacientes atendidos cuenta clientes distintos con órdenes vigentes, sin revelar su identidad ni confirmar atención médica; el rango máximo es 366 días.

## Arquitectura y acceso

```mermaid
flowchart LR
    A[Administrador autenticado] --> B[Chat en Laravel]
    B --> R[Reglas para preguntas básicas y fechas]
    R --> D[Parámetros validados de informe]
    B --> C[Ollama privado: otras formulaciones]
    C --> D
    D --> E[Consultas definidas de Laravel]
    E --> F[(MySQL interno)]
    E --> G[Tabla real y descarga CSV]
    H[Cliente MCP con sesión y CSRF] --> I[/mcp/laboratorio]
    I --> E
```

Chat y MCP comparten `InformesLaboratorio`; el chat no necesita una conexión HTTP a sí mismo. El modelo solo recibe la pregunta y contexto del informe anterior; el servidor resuelve fechas y límites. No recibe filas de la BD, credenciales ni resultados clínicos. Las cifras se muestran directamente desde las consultas, sin una segunda redacción del modelo que pudiera inventarlas. El modelo no puede inventar un periodo ni reducir las filas: su esquema solo permite elegir un informe y estado. Las fechas explícitas y los filtros reconocidos tienen prioridad. Sigue siendo posible que interprete mal una formulación libre; la tabla presenta el informe y los filtros aplicados.

- Solo rol `admin` con permiso `access_admin_panel`. Recepción y Laboratorista reciben 403, incluso mediante peticiones directas. Los informes financieros también exigen `ingresos_diarios`.
- Formularios y MCP usan sesión Laravel y CSRF; no se habilita acceso anónimo ni un token público.
- No hay SQL libre, cambios de datos de negocio, datos clínicos ni nombres/contactos de pacientes en estas herramientas.
- Los parámetros se validan después de la respuesta del modelo. Campos extra, informes desconocidos y rangos fuera de límite se rechazan.
- Hasta 40 peticiones por minuto por usuario; una inferencia a la vez, con hasta 100 segundos de espera. Las consultas directas siguen disponibles durante otra inferencia. Ollama limita a un modelo cargado, dos peticiones en cola, 6 GB y 3 CPU. Contexto de 4096 tokens y permanencia en memoria de 10 minutos. Estos son topes de uso, no recursos reservados ni cambios de tamaño en Oracle.
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
CHATBOT_LOCAL_MODEL=qwen3:4b
```

Si ya tienes `CHATBOT_LOCAL_MODEL=qwen3:1.7b` en tu `.env`, cámbialo a `CHATBOT_LOCAL_MODEL=qwen3:4b` antes de reconstruir; un valor existente tiene prioridad sobre el nuevo valor por defecto. Tras cambiar el modelo, ejecutar `docker compose up -d --force-recreate app ollama ollama-init`. El modelo anterior permanece en el volumen; no es necesario borrarlo. Para desactivar el chat usar `CHATBOT_ENABLED=false` y recrear `app`. No hay API de IA de pago; siguen aplicando las condiciones de Oracle al uso de instancia y almacenamiento. No se promete gratuidad permanente.

El `.env` compartido no se incluyó en Git ni en imágenes. Para un sitio accesible por Internet, usar `APP_ENV=production` y `APP_DEBUG=false`. Cambiar contraseñas en `.env` no modifica automáticamente usuarios de un MySQL con volumen existente: hay que aplicar la rotación también en MySQL. Cambiar `APP_KEY` invalida sesiones y puede afectar datos cifrados; no se regeneró durante este cambio.

## Verificación y límites

`ChatbotTest` verifica los nueve informes contra datos de prueba, permisos, parámetros maliciosos, límites, bitácora, protocolo MCP real, respaldo guiado, seguimiento y concurrencia. La llamada a Ollama se simula en esas pruebas. Chromium verifica sesión, tablas reales de una BD local, CSV, CSRF, escape de HTML y móvil.

```bash
DB_CONNECTION=sqlite DB_DATABASE=:memory: php -d memory_limit=512M vendor/bin/phpunit
npm run build
docker compose config --quiet
```

La descarga del modelo se bloqueó por la política de red del entorno de desarrollo. El build de esta versión compiló PHP 8.3 y sus extensiones y construyó el frontend, pero se detuvo en Composer: la política de red del entorno devuelve 403 al descargar dependencias de api.github.com. La auditoría de avisos de Composer también se bloqueó anteriormente por acceso a Packagist. No se eludieron esas restricciones. Se verificaron configuración Compose, manifiestos ARM64, dependencias, aplicación y protocolo; **no se midieron velocidad ni precisión reales de Qwen en Oracle, ni se completó el build de contenedores aquí**. El usuario confirmó que la primera versión funciona en Oracle. La nueva versión debe comprobar allí la descarga de Qwen 4B y su velocidad; las pruebas locales no establecen su rendimiento en la instancia A1.
