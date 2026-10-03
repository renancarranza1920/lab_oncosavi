import { writeFileSync } from 'node:fs';
const webhook = process.env.WHATSAPP_WEBHOOK_SECRET;
const bridge = process.env.WHATSAPP_BRIDGE_TOKEN;
if (!webhook || !bridge || webhook.length < 32 || bridge.length < 32) throw new Error('Falta la configuración privada de WhatsApp.');
writeFileSync('/home/node/.n8n/oncosavi-credentials.json', JSON.stringify([
  { id: 'oncosaviWebhookV1', name: 'ONCOSAVI webhook privado', type: 'httpHeaderAuth', data: { name: 'X-Oncosavi-Token', value: webhook } },
  { id: 'oncosaviBridgeV1', name: 'ONCOSAVI WhatsApp privado', type: 'httpHeaderAuth', data: { name: 'Authorization', value: `Bearer ${bridge}` } },
]), { mode: 0o600 });
