#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/../.."
python3 docker/whatsapp/configurar.py
compose=(docker compose --env-file .env --env-file .env.whatsapp -f docker-compose.yml -f docker-compose.whatsapp.yml)
"${compose[@]}" config --quiet
# Construir primero; la aplicación actual puede continuar funcionando durante la construcción.
"${compose[@]}" build app whatsapp
"${compose[@]}" up -d db
"${compose[@]}" run --rm --no-deps app php artisan migrate --force
"${compose[@]}" up -d --remove-orphans app n8n whatsapp
"${compose[@]}" exec -T app php artisan optimize:clear
"${compose[@]}" up -d --wait --wait-timeout 180 n8n whatsapp
"${compose[@]}" ps
echo 'Abra ONCOSAVI → Envíos WhatsApp → Vincular WhatsApp como administrador.'
