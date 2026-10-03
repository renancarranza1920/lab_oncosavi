import makeWASocket, { useMultiFileAuthState, DisconnectReason, Browsers } from '@whiskeysockets/baileys';
import pino from 'pino';
import QRCode from 'qrcode';
import { mkdir } from 'node:fs/promises';

export function createGateway(directory) {
  let socket, starting, state = 'disconnected', qr = null, reconnect;
  const logger = pino({ level: 'silent' }); // No teléfonos, mensajes, QR o claves en logs.
  async function connect() {
    if (socket && ['connected', 'connecting', 'qr'].includes(state)) return status();
    if (starting) return starting;
    starting = (async () => {
      clearTimeout(reconnect);
      await mkdir(directory, { recursive: true, mode: 0o700 });
      const { state: auth, saveCreds } = await useMultiFileAuthState(directory);
      state = 'connecting'; qr = null;
      const current = makeWASocket({ auth, logger, browser: Browsers.ubuntu('ONCOSAVI'),
        markOnlineOnConnect: false, syncFullHistory: false, shouldSyncHistoryMessage: () => false,
        connectTimeoutMs: 20000, defaultQueryTimeoutMs: 15000, retryRequestDelayMs: 1000 });
      socket = current;
      current.ev.on('creds.update', () => saveCreds().catch(() => { state = 'disconnected'; }));
      current.ev.on('connection.update', async update => {
        if (socket !== current) return;
        if (update.qr) {
          const value = await QRCode.toDataURL(update.qr, { width: 320, margin: 2 });
          if (socket === current && state !== 'connected') { qr = value; state = 'qr'; }
        }
        if (update.connection === 'open') { state = 'connected'; qr = null; }
        if (update.connection === 'close') {
          socket = null; state = 'disconnected'; qr = null;
          const code = update.lastDisconnect?.error?.output?.statusCode;
          if (code !== DisconnectReason.loggedOut && code !== DisconnectReason.connectionReplaced) {
            reconnect = setTimeout(() => connect().catch(() => {}), 5000);
          }
        }
      });
      return status();
    })();
    try { return await starting; } finally { starting = null; }
  }
  function status() { return { status: state, qr }; }
  async function recipient(phone) {
    if (state !== 'connected' || !socket) throw new Error('not_connected');
    const result = await socket.onWhatsApp(phone);
    return result?.find(item => item.exists)?.jid ?? null;
  }
  async function send(jid, input) {
    const sent = await socket.sendMessage(jid, { document: Buffer.from(input.pdf, 'base64'),
      mimetype: 'application/pdf', fileName: input.filename, caption: input.message });
    if (!sent?.key?.id) throw new Error('missing_ack');
    return sent.key.id;
  }
  return { connect, status, recipient, send };
}
