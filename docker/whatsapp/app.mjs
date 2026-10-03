import { createServer } from 'node:http';
import { timingSafeEqual } from 'node:crypto';
import { mkdir, readFile, writeFile, rename } from 'node:fs/promises';
import path from 'node:path';

const uuid = /^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i;
const validPhone = /^(503[267]\d{7}|1[2-9]\d{9})$/;
const maxPdf = 8 * 1024 * 1024;
function authorized(header, token) {
  const provided = Buffer.from(header ?? ''); const expected = Buffer.from(`Bearer ${token}`);
  return provided.length === expected.length && timingSafeEqual(provided, expected);
}
export function createApp({ token, directory, gateway, interval = 5000, timeout = 25000 }) {
  if (!token || token.length < 32) throw new Error('Configure WHATSAPP_BRIDGE_TOKEN (mínimo 32 caracteres).');
  let busy = false, lastSend = 0;
  const pending = new Map();
  async function read(id) {
    try { return JSON.parse(await readFile(path.join(directory, `${id}.json`), 'utf8')); }
    catch (error) { if (error.code === 'ENOENT') return null; throw error; }
  }
  async function save(id, value) {
    await mkdir(directory, { recursive: true, mode: 0o700 });
    const file = path.join(directory, `${id}.json`);
    await writeFile(`${file}.tmp`, JSON.stringify(value), { mode: 0o600 });
    await rename(`${file}.tmp`, file);
    return value;
  }
  const reply = (res, code, value) => { res.writeHead(code, { 'Content-Type': 'application/json', 'Cache-Control': 'no-store' }); res.end(JSON.stringify(value)); };
  return createServer(async (req, res) => {
    try {
      if (req.method === 'GET' && req.url === '/health') return reply(res, 200, { status: 'ok' });
      if (!authorized(req.headers.authorization, token)) return reply(res, 401, { status: 'failed' });
      if (req.method === 'GET' && req.url === '/status') return reply(res, 200, gateway.status());
      if (req.method === 'POST' && req.url === '/connect') return reply(res, 200, await gateway.connect());
      if (req.method === 'GET' && req.url.startsWith('/messages/')) {
        const id = req.url.slice('/messages/'.length);
        if (!uuid.test(id)) return reply(res, 400, { status: 'failed', code: 'invalid_request' });
        const saved = await read(id);
        // Una caída después de empezar el envío no habilita un reintento automático.
        if (saved?.status === 'sending' && !pending.has(id)) saved.status = 'unknown';
        return reply(res, saved ? 200 : 404, saved ?? { status: 'unknown' });
      }
      if (req.method !== 'POST' || req.url !== '/send') return reply(res, 404, { status: 'failed' });
      let size = 0; const chunks = [];
      for await (const chunk of req) {
        size += chunk.length;
        if (size > 12 * 1024 * 1024) return reply(res, 413, { status: 'failed', code: 'invalid_request' });
        chunks.push(chunk);
      }
      let input;
      try { input = JSON.parse(Buffer.concat(chunks)); } catch { return reply(res, 400, { status: 'failed', code: 'invalid_request' }); }
      if (!uuid.test(input.id ?? '') || !validPhone.test(input.phone ?? '') || typeof input.message !== 'string'
        || input.message.length > 2000 || !/^[a-zA-Z0-9_-]{1,80}\.pdf$/.test(input.filename ?? '')
        || typeof input.pdf !== 'string' || !/^[A-Za-z0-9+/]+={0,2}$/.test(input.pdf)) {
        return reply(res, 400, { status: 'failed', code: 'invalid_request' });
      }
      const document = Buffer.from(input.pdf, 'base64');
      if (document.length > maxPdf || document.subarray(0, 5).toString() !== '%PDF-') return reply(res, 400, { status: 'failed', code: 'invalid_request' });
      // No acepta URLs, rutas de archivo, grupos o comandos arbitrarios.
      const saved = await read(input.id);
      if (saved) {
        if (saved.status === 'sending' && !pending.has(input.id)) saved.status = 'unknown';
        return reply(res, 200, saved);
      }
      if (busy || Date.now() - lastSend < interval) return reply(res, 200, { status: 'failed', code: 'busy' });
      busy = true;
      try {
        if (gateway.status().status !== 'connected') return reply(res, 200, { status: 'failed', code: 'not_connected' });
        let jid;
        try { jid = await gateway.recipient(input.phone); } catch { return reply(res, 200, { status: 'failed', code: 'not_connected' }); }
        if (!jid) return reply(res, 200, { status: 'failed', code: 'not_registered' });
        await save(input.id, { status: 'sending' });
        lastSend = Date.now();
        const operation = gateway.send(jid, input).then(messageId => save(input.id, { status: 'sent', messageId }))
          .catch(() => save(input.id, { status: 'unknown' })).finally(() => { pending.delete(input.id); busy = false; });
        pending.set(input.id, operation);
        let timer;
        const result = await Promise.race([operation, new Promise(resolve => { timer = setTimeout(() => resolve({ status: 'unknown' }), timeout); })]);
        clearTimeout(timer);
        return reply(res, 200, result);
      } finally { if (!pending.has(input.id)) busy = false; }
    } catch {
      // Nunca devolver el error interno o registrar payloads con PDF o teléfonos.
      reply(res, 500, { status: 'unknown' });
    }
  });
}
