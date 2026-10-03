import { access } from 'node:fs/promises';
import { createApp } from './app.mjs';
import { createGateway } from './baileys.mjs';

process.umask(0o077);
// libsignal imprime objetos de sesión con claves mediante console.info/warn.
// Este proceso solo emite los avisos controlados de abajo, nunca sus objetos internos.
const notice = text => process.stdout.write(text + '\n');
for (const method of ['log', 'info', 'warn', 'error', 'debug', 'dir', 'trace']) console[method] = () => {};
for (const event of ['unhandledRejection', 'uncaughtException']) {
  process.on(event, () => { notice('WhatsApp: fallo de conexión; reinicie el servicio si persiste.'); process.exit(1); });
}
const gateway = createGateway('/data/session');
const app = createApp({ token: process.env.WHATSAPP_BRIDGE_TOKEN, directory: '/data/messages', gateway });
app.listen(3000, '0.0.0.0', () => notice('ONCOSAVI WhatsApp: servicio privado iniciado.'));
// Solo conectar al inicio si ya hubo una vinculación; de otro modo esperar al administrador.
try { await access('/data/session/creds.json'); await gateway.connect(); } catch {}
