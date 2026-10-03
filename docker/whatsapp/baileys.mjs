import makeWASocket, { useMultiFileAuthState, DisconnectReason, Browsers, fetchLatestBaileysVersion } from '@whiskeysockets/baileys';
import pino from 'pino';
import QRCode from 'qrcode';
import { mkdir, rm } from 'node:fs/promises';

// Dependencias sustituibles para probar QR, desconexiones y timeouts sin un teléfono real.
export function createGateway(directory, { makeSocket = makeWASocket, loadAuth = useMultiFileAuthState,
  clearAuth = () => rm(directory, { recursive: true, force: true }),
  resolveVersion = () => fetchLatestBaileysVersion({ timeout: 3500 }),
  toQR = value => QRCode.toDataURL(value, { width: 320, margin: 2 }), log = () => {},
  reconnectDelay = 5000, connectDeadline = 30000, maxRetries = 3 } = {}) {
  let socket, starting, state = 'disconnected', qr = null, code = null, reconnect, deadline;
  let attempts = 0, stopped = false, qrGeneration = 0, version, versionAt = 0;
  const logger = pino({ level: 'silent' }); // Nunca imprimir objetos de Baileys/libsignal.
  function status() { return { status: state, qr, code }; }
  function fail(reason, current) {
    if (current && socket !== current) return;
    clearTimeout(deadline); socket = null; qr = null; qrGeneration++;
    state = 'disconnected'; code = reason;
    log('connection_failed', { code: reason });
    current?.end(new Error('connection_closed'));
  }
  async function connect(automatic = false) {
    if (starting) return starting;
    if (socket && ['connected', 'connecting', 'qr'].includes(state)) return status();
    // Solo un nuevo clic del administrador restablece una sesión que WhatsApp ya revocó.
    const renewAuth = code === 'auth_expired' && !automatic;
    if (!automatic) attempts = 0;
    stopped = false; clearTimeout(reconnect);
    state = 'connecting'; qr = null; code = null;
    log('connection_requested');
    starting = (async () => {
      try {
        if (renewAuth) await clearAuth();
        await mkdir(directory, { recursive: true, mode: 0o700 });
        const { state: auth, saveCreds } = await loadAuth(directory);
        if (!version || Date.now() - versionAt > 600000) {
          try {
            const latest = await resolveVersion();
            if (Array.isArray(latest.version) && latest.version.length === 3 && latest.version.every(n => Number.isSafeInteger(n) && n >= 0)) {
              version = latest.version; versionAt = Date.now();
            }
            log(latest.isLatest ? 'version_updated' : 'version_fallback');
          } catch { log('version_fallback'); }
        }
        const current = makeSocket({ auth, logger, ...(version ? { version } : {}), browser: Browsers.ubuntu('Chrome'),
          markOnlineOnConnect: false, syncFullHistory: false, shouldSyncHistoryMessage: () => false,
          connectTimeoutMs: 20000, defaultQueryTimeoutMs: 15000, retryRequestDelayMs: 1000 });
        socket = current;
        deadline = setTimeout(() => fail('connection_timeout', current), connectDeadline);
        current.ev.on('creds.update', () => {
          if (socket === current) Promise.resolve().then(saveCreds).catch(() => fail('session_error', current));
        });
        current.ev.on('connection.update', async update => {
          if (socket !== current) return;
          if (update.qr) {
            const generation = ++qrGeneration;
            try {
              const value = await toQR(update.qr);
              if (socket === current && generation === qrGeneration && state !== 'connected') {
                clearTimeout(deadline); qr = value; state = 'qr'; code = null;
                log('qr_ready');
              }
            } catch { fail('qr_error', current); }
          }
          if (update.connection === 'open' && socket === current) {
            clearTimeout(deadline); state = 'connected'; qr = null; code = null; attempts = 0; qrGeneration++;
            log('connected');
          }
          if (update.connection === 'close' && socket === current) {
            clearTimeout(deadline);
            const hadQR = state === 'qr';
            socket = null; state = 'disconnected'; qr = null; qrGeneration++;
            const disconnect = update.lastDisconnect?.error?.output?.statusCode;
            code = disconnect === DisconnectReason.loggedOut ? 'auth_expired'
              : disconnect === DisconnectReason.connectionReplaced ? 'connection_replaced'
              : disconnect === 405 ? 'protocol_error'
              : hadQR && disconnect === DisconnectReason.timedOut ? 'qr_expired' : 'network_error';
            log('disconnected', { code, ...(Number.isInteger(disconnect) ? { status: disconnect } : {}) });
            if (!stopped && code === 'network_error' && attempts++ < maxRetries) {
              log('reconnect_scheduled');
              reconnect = setTimeout(() => { void connect(true); }, reconnectDelay);
            }
          }
        });
      } catch { fail('session_error', socket); }
      return status();
    })();
    try { return await starting; } finally { starting = null; }
  }
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
  function close() {
    stopped = true; clearTimeout(reconnect); clearTimeout(deadline);
    const previous = socket; socket = null; qrGeneration++; qr = null; state = 'disconnected';
    previous?.end(new Error('service_stopped'));
  }
  return { connect, status, recipient, send, close };
}
