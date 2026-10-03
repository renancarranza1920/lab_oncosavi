import test from 'node:test';
import assert from 'node:assert/strict';
import { EventEmitter } from 'node:events';
import { mkdtemp, rm } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { createGateway } from '../baileys.mjs';
import { safeLog } from '../log.mjs';
const tick = () => new Promise(resolve => setImmediate(resolve));
const pause = ms => new Promise(resolve => setTimeout(resolve, ms));
async function fixture(t, options = {}) {
  const directory = await mkdtemp(tmpdir() + '/wa-connection-');
  const sockets = [], events = [];
  const gateway = createGateway(directory, {
    loadAuth: async () => ({ state: {}, saveCreds: async () => {} }),
    resolveVersion: async () => ({ version: [2, 3000, 1043857760], isLatest: true }),
    toQR: async () => 'data:image/png;base64,AA==',
    log: (event, fields) => events.push({ event, ...fields }),
    makeSocket: config => { const socket = { ev: new EventEmitter(), end: () => {}, config }; sockets.push(socket); return socket; },
    ...options,
  });
  t.after(async () => { gateway.close(); await rm(directory, { recursive: true, force: true }); });
  return { gateway, sockets, events };
}
function disconnect(socket, statusCode) { socket.ev.emit('connection.update', { connection: 'close', lastDisconnect: { error: { output: { statusCode }, message: 'SECRET PHONE' } } }); }

test('un clic inicia una sesión y el QR posterior se puede consultar, sin registrar su contenido', async t => {
  const { gateway, sockets, events } = await fixture(t);
  await Promise.all([gateway.connect(), gateway.connect()]);
  assert.equal(sockets.length, 1);
  assert.equal(gateway.status().status, 'connecting');
  assert.deepEqual(sockets[0].config.version, [2, 3000, 1043857760]);
  sockets[0].ev.emit('connection.update', { qr: 'SECRET QR' }); await tick();
  assert.equal(gateway.status().status, 'qr'); assert.ok(gateway.status().qr.startsWith('data:image/png;'));
  sockets[0].ev.emit('connection.update', { connection: 'open' });
  assert.equal(gateway.status().status, 'connected'); assert.equal(gateway.status().qr, null);
  assert.ok(events.some(e => e.event === 'qr_ready'));
  assert.ok(!JSON.stringify(events).includes('SECRET'));
});

test('rechazo de protocolo sale de conectando y conserva motivo y código seguros en logs', async t => {
  const { gateway, sockets, events } = await fixture(t);
  await gateway.connect(); disconnect(sockets[0], 405);
  assert.deepEqual(gateway.status(), { status: 'disconnected', qr: null, code: 'protocol_error' });
  assert.ok(events.some(e => e.event === 'disconnected' && e.status === 405));
  assert.ok(!JSON.stringify(events).includes('SECRET'));
});

test('la espera del QR tiene límite y permite otro intento', async t => {
  const { gateway, sockets } = await fixture(t, { connectDeadline: 15 });
  await gateway.connect(); await pause(35);
  assert.equal(gateway.status().code, 'connection_timeout');
  await gateway.connect(); assert.equal(sockets.length, 2);
  assert.equal(gateway.status().status, 'connecting');
});

test('QR de un socket cerrado no reaparece aunque termine de generarse tarde', async t => {
  let finish;
  const { gateway, sockets } = await fixture(t, { toQR: () => new Promise(resolve => { finish = resolve; }) });
  await gateway.connect(); sockets[0].ev.emit('connection.update', { qr: 'old' });
  disconnect(sockets[0], 401); finish('data:image/png;base64,AA=='); await tick();
  assert.equal(gateway.status().code, 'auth_expired'); assert.equal(gateway.status().qr, null);
});

test('fallo de red limita reconexiones, sin un bucle infinito', async t => {
  const { gateway, sockets } = await fixture(t, { reconnectDelay: 5, maxRetries: 1 });
  await gateway.connect(); disconnect(sockets[0], 428); await pause(30);
  assert.equal(sockets.length, 2); disconnect(sockets[1], 428); await pause(30);
  assert.equal(sockets.length, 2); assert.equal(gateway.status().status, 'disconnected');
});

test('fallo al leer la sesión se ve como error recuperable', async t => {
  const { gateway } = await fixture(t, { loadAuth: async () => { throw Error('SECRET PATH'); } });
  await gateway.connect(); assert.equal(gateway.status().code, 'session_error');
});

test('consulta de versión caída conserva el inicio de conexión con la versión incorporada', async t => {
  const { gateway, events } = await fixture(t, { resolveVersion: async () => { throw Error('network'); } });
  assert.equal((await gateway.connect()).status, 'connecting');
  assert.ok(events.some(e => e.event === 'version_fallback'));
});

test('registro seguro descarta secretos, errores internos y eventos ajenos', () => {
  const lines = []; const write = line => lines.push(line);
  safeLog('disconnected', { code: 'network_error', status: 428, qr: 'SECRET', phone: 'SECRET', error: new Error('SECRET'), token: 'SECRET' }, write);
  safeLog('SECRET', {}, write);
  assert.equal(lines.length, 1); assert.ok(!lines[0].includes('SECRET'));
  assert.equal(JSON.parse(lines[0]).status, 428);
});

test('solo un nuevo clic restablece la sesión revocada, nunca una sesión válida', async t => {
  let resets = 0;
  const { gateway, sockets } = await fixture(t, { clearAuth: async () => { resets++; } });
  await gateway.connect(); assert.equal(resets, 0);
  disconnect(sockets[0], 401); await tick(); assert.equal(resets, 0);
  await gateway.connect(); assert.equal(resets, 1); assert.equal(sockets.length, 2);
  sockets[1].ev.emit('connection.update', { connection: 'open' });
  await gateway.connect(); assert.equal(resets, 1); assert.equal(sockets.length, 2);
});
