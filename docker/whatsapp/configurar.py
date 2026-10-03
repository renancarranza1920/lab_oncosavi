"""Configuración local: no modifica .env, no imprime claves ni reemplaza valores existentes."""
import os
from pathlib import Path
import secrets

root = Path(__file__).resolve().parents[2]
target = root / '.env.whatsapp'
if target.exists():
    print('Ya existe .env.whatsapp. Se conservaron sus claves y configuración.')
else:
    values = {
        'WHATSAPP_ENABLED': 'true',
        'WHATSAPP_WEBHOOK_URL': 'http://n8n:5678/webhook/oncosavi-whatsapp',
        'WHATSAPP_WEBHOOK_SECRET': secrets.token_hex(32),
        'WHATSAPP_BRIDGE_URL': 'http://whatsapp:3000',
        'WHATSAPP_BRIDGE_TOKEN': secrets.token_hex(32),
        'N8N_ENCRYPTION_KEY': secrets.token_hex(32),
    }
    with open(target, 'x', opener=lambda path, flags: os.open(path, flags, 0o600)) as file:
        file.write('# Privado: no subir a GitHub. Conservar al reconstruir contenedores.\n')
        file.write('\n'.join(f'{key}={value}' for key, value in values.items()) + '\n')
    print('Configuración creada en .env.whatsapp con permisos privados. No se modificó .env.')
print('Inicie Docker con ambos archivos de Compose y ambos archivos de entorno; consulte docs/WHATSAPP_N8N.md.')
