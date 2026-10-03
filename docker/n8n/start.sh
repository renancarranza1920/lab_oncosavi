#!/bin/sh
set -eu
umask 077
marker=/home/node/.n8n/oncosavi-whatsapp-v1.initialized
if [ ! -f "$marker" ]; then
    node /bootstrap/credentials.mjs
    n8n import:credentials --input=/home/node/.n8n/oncosavi-credentials.json
    n8n import:workflow --input=/bootstrap/whatsapp-workflow.json
    n8n publish:workflow --id=oncosaviWhatsAppV1
    rm -f /home/node/.n8n/oncosavi-credentials.json
    touch "$marker"
fi
exec n8n start
