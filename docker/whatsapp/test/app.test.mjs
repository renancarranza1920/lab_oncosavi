import { test } from 'node:test';
import assert from 'node:assert/strict';
import { mkdtemp, rm, writeFile } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import path from 'node:path';
import { randomUUID } from 'node:crypto';
import { createApp } from '../app.mjs';

const token = 't'.repeat(64);
const body = () => ({ id: randomUUID(), phone: '50377778888', message: 'Documento de prueba', filename: 'prueba.pdf', pdf: Buffer.from('%PDF-1.7 prueba').toString('base64') });
async function fixture(t, options = {}) {
  const directory = await mkdtemp(path.join(tmpdir(), 'oncosavi-wa-')); let calls = 0;
  const gateway = { status: () => ({ status: 'connected', qr: null }), connect: async () => ({ status: 'qr', qr: 'prueba' }),
    recipient: async () => '50377778888@s.whatsapp.net', send: async () => { calls++; return 'message-123'; }, ...options.gateway };
  const server = createApp({ token, directory, gateway, interval: 0, timeout: 1000, ...options, gateway });
  await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
  const url = `http://127.0.0.1:${server.address().port}`;
  t.after(async () => { await new Promise(resolve => server.close(resolve)); await rm(directory, { recursive: true, force: true }); });
  return { directory, calls: () => calls, request: (route, input, auth = token) => fetch(url + route, { method: input ? 'POST' : 'GET',
    headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${auth}` }, body: input ? JSON.stringify(input) : undefined }) };
}
test('token obligatorio protege QR y envío', async t => {
  const f = await fixture(t);
  assert.equal((await f.request('/status', null, 'incorrecto')).status, 401);
  assert.equal((await f.request('/send', body(), 'incorrecto')).status, 401);
  assert.equal(f.calls(), 0);
});
test('envía el documento una vez y conserva el resultado para solicitudes repetidas', async t => {
  const f = await fixture(t); const input = body();
  assert.deepEqual(await (await f.request('/send', input)).json(), { status: 'sent', messageId: 'message-123' });
  assert.deepEqual(await (await f.request('/send', input)).json(), { status: 'sent', messageId: 'message-123' });
  assert.equal(f.calls(), 1);
});
test('validación impide grupos, rutas, documentos falsos y URLs de archivos', async t => {
  const f = await fixture(t);
  for (const change of [{ phone: 'grupo@g.us' }, { filename: '../privado.pdf' }, { pdf: Buffer.from('HTML').toString('base64') }, { pdf: 'http://db/secret' }]) {
    const result = await f.request('/send', { ...body(), ...change }); assert.equal(result.status, 400);
  }
  assert.equal(f.calls(), 0);
});
test('sesión desconectada informa fallo sin intentar enviar', async t => {
  const f = await fixture(t, { gateway: { status: () => ({ status: 'disconnected' }) } });
  assert.deepEqual(await (await f.request('/send', body())).json(), { status: 'failed', code: 'not_connected' });
  assert.equal(f.calls(), 0);
});
test('número sin WhatsApp no inicia el envío', async t => {
  const f = await fixture(t, { gateway: { recipient: async () => null } });
  assert.deepEqual(await (await f.request('/send', body())).json(), { status: 'failed', code: 'not_registered' });
});
test('error del proveedor no filtra detalles ni se reintenta', async t => {
  let calls = 0;
  const f = await fixture(t, { gateway: { send: async () => { calls++; throw Error('CLAVE PRIVADA'); } } });
  const input = body();
  assert.deepEqual(await (await f.request('/send', input)).json(), { status: 'unknown' });
  assert.deepEqual(await (await f.request('/send', input)).json(), { status: 'unknown' });
  assert.equal(calls, 1);
});
test('tras reiniciar un envío interrumpido queda incierto y no se vuelve a enviar', async t => {
  const f = await fixture(t); const input = body();
  await writeFile(path.join(f.directory, `${input.id}.json`), JSON.stringify({ status: 'sending' }));
  assert.deepEqual(await (await f.request('/send', input)).json(), { status: 'unknown' });
  assert.equal(f.calls(), 0);
});
test('respuesta tardía puede consultarse sin reenviar', async t => {
  const f = await fixture(t, { timeout: 5, gateway: { send: () => new Promise(resolve => setTimeout(() => resolve('tardio-123'), 30)) } });
  const input = body();
  assert.deepEqual(await (await f.request('/send', input)).json(), { status: 'unknown' });
  await new Promise(resolve => setTimeout(resolve, 45));
  assert.deepEqual(await (await f.request('/messages/' + input.id)).json(), { status: 'sent', messageId: 'tardio-123' });
});

test('conserva el nombre descargado con espacios y PDF en mayúsculas y el mensaje original', async t => {
  let received;
  const f = await fixture(t, { gateway: { send: async (_jid, input) => { received = input; return 'nombre-original'; } } });
  const input = { ...body(), filename: 'PACIENTE-PRUEBA - 5 P.PDF', message: 'Estimado(a) *Paciente Prueba*,\n\nPor favor revise el documento PDF adjunto.' };
  assert.equal((await (await f.request('/send', input)).json()).status, 'sent');
  assert.equal(received.filename, input.filename);
  assert.equal(received.message, input.message);
  for (const filename of ['../PACIENTE.PDF', 'carpeta/PACIENTE.PDF', 'carpeta\\PACIENTE.PDF', 'PACIENTE\n.PDF']) {
    assert.equal((await f.request('/send', { ...body(), filename })).status, 400);
  }
});
