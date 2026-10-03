# Asistente pospuesto

El chatbot se retiró del sistema por decisión del propietario. Ya no aparece en Administración y las rutas `/chatbot`, `/chatbot/estado`, `/chatbot/preguntar` y `/mcp/laboratorio` no están disponibles. Se retiraron sus archivos, entradas de Vite, variables de ejemplo, dependencia Laravel MCP y servicios Ollama de Docker.

La implementación anterior queda en el historial de Git para retomarla en el futuro: primera versión `64f5ec6` y mejoras `75f53fe`. Se mantienen la versión actual de Laravel, el resto de módulos y los registros históricos de bitácora.

## Actualizar Oracle

```bash
cd ~/lab_oncosavi
git switch main
git pull --ff-only origin main
docker compose up -d --build --remove-orphans
docker compose exec app php artisan optimize:clear
docker compose ps
```

`--remove-orphans` retira los contenedores de Ollama que pertenecían a este proyecto. Se conservan los volúmenes de MySQL, Laravel y cualquier volumen existente de modelos; no se borran datos ni se ejecutan seeders. Las variables `CHATBOT_*` que aún estén en tu `.env` se pueden retirar, pero ya no se utilizan.
