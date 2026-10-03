// Solo eventos controlados: nunca objetos de errores, cuerpos HTTP, QR, teléfonos o credenciales.
const events = new Set(['started', 'fatal', 'connection_requested', 'connection_failed', 'qr_ready',
  'version_updated', 'version_fallback', 'connected', 'disconnected', 'reconnect_scheduled', 'connect_received', 'request_failed', 'request_rejected', 'send_received']);
export function safeLog(event, fields = {}, write = line => process.stdout.write(line + '\n')) {
  if (!events.has(event)) return;
  const entry = { service: 'whatsapp', event };
  if (/^[a-z_]{1,40}$/.test(fields.code ?? '')) entry.code = fields.code;
  if (Number.isInteger(fields.status)) entry.status = fields.status;
  write(JSON.stringify(entry));
}
