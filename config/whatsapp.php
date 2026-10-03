<?php

return [
    'enabled' => env('WHATSAPP_ENABLED', false),
    'webhook_url' => env('WHATSAPP_WEBHOOK_URL', 'http://n8n:5678/webhook/oncosavi-whatsapp'),
    'webhook_secret' => env('WHATSAPP_WEBHOOK_SECRET'),
    'bridge_url' => env('WHATSAPP_BRIDGE_URL', 'http://whatsapp:3000'),
    'bridge_token' => env('WHATSAPP_BRIDGE_TOKEN'),
    'timeout' => 40,
    'max_pdf_bytes' => 8 * 1024 * 1024,
];
