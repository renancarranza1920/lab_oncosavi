#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/../.."
if [[ ! -f .env.whatsapp ]]; then
  echo 'Falta activar WhatsApp: ejecute bash docker/whatsapp/iniciar.sh'
  exit 1
fi
compose=(docker compose --env-file .env --env-file .env.whatsapp -f docker-compose.yml -f docker-compose.whatsapp.yml)
"${compose[@]}" ps app whatsapp
resultado=0
"${compose[@]}" exec -T app php artisan whatsapp:diagnostico || resultado=$?
"${compose[@]}" logs --tail=40 whatsapp
exit "$resultado"
