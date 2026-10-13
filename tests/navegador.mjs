// npm install playwright en un entorno de pruebas; el hosting no necesita Node.
import { createRequire } from 'node:module';
import assert from 'node:assert/strict';
const require = createRequire(import.meta.url);
const { chromium } = require(process.env.MERINO_PLAYWRIGHT_PATH || 'playwright');
const browser = await chromium.launch({ executablePath: process.env.MERINO_BROWSER_BIN || undefined, headless: true, args: ['--no-sandbox'] });
const origin = process.env.MERINO_TEST_URL || 'http://127.0.0.1:8094';
const catalog = { success: true, moneda: 'USD', asistente: { ia: true }, examens: [
  { id: 1, nombre: 'psa total', precio: '23.45', categoria: 'Marcadores tumorales' },
  { id: 2, nombre: 'Psa libre', precio: '34.56', categoria: 'Marcadores tumorales' },
  { id: 3, nombre: 'Glucosa', precio: '5.25', categoria: 'Química sanguínea' },
  { id: 4, nombre: 'Relación psa total/libre', precio: '51.23', categoria: 'Marcadores tumorales' },
  { id: 5, nombre: '<img src=x onerror=window.EXECUTED=1> PSA', precio: '90.00', categoria: 'Prueba de texto' }
], perfiles: [{ id: 1, nombre: 'Perfil lipídico', precio: '12.00', incluidos: ['Colesterol HDL', 'Colesterol LDL'] }] };
let checks = 0;
const pages = [];
try {
  for (const width of [1366, 390, 320]) {
    const page = await browser.newPage({ viewport: { width, height: width === 1366 ? 950 : 850 } });
    pages.push(page);
    const errors = [];
    page.on('pageerror', (error) => errors.push(error.message));
    let aiMode = 'success';
    let offline = false;
    const bodies = [];
    await page.route('**/api/catalogo.php', async (route) => {
      await route.fulfill({ status: offline ? 503 : 200, contentType: 'application/json', body: JSON.stringify(offline ? { success: false } : catalog) });
    });
    await page.route('**/api/interpretar.php', async (route) => {
      const body = route.request().postDataJSON();
      bodies.push(body);
      if (aiMode === 'down') return route.fulfill({ status: 503, contentType: 'application/json', body: '{"success":false}' });
      await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({
        success: true, kind: 'matches', needs_clarification: true,
        keys: aiMode === 'invalid' ? ['exam:999999'] : ['exam:1', 'exam:2'], precio: '0.00', texto: '<script>window.EXECUTED=2</script>'
      }) });
    });
    await page.goto(origin, { waitUntil: 'networkidle' });
    await page.getByRole('button', { name: 'Abrir asistente de consultas Merino' }).click();
    const ask = async (text) => {
      await page.locator('#merino-chat-input').fill(text);
      await page.locator('.mc-submit').click();
      await page.waitForFunction(() => !document.querySelector('.mc-submit').disabled);
      return page.locator('.mc-message:not(.mc-message-user)').last();
    };
    let answer = await ask('¿Cuánto sale el antígeno prostático?');
    assert.equal(await answer.locator('.mc-record').count(), 4);
    assert.ok((await answer.innerText()).includes('PSA significa'));
    assert.ok((await answer.innerText()).includes('$23.45'));
    assert.equal(bodies.length, 0);
    checks += 4;
    answer = await ask('PSA total');
    assert.equal(await answer.locator('.mc-record').count(), 1);
    checks++;
    catalog.examens[1].precio = '39.87';
    answer = await ask('¿y el libre?');
    assert.equal(await answer.locator('.mc-record-name').first().innerText(), 'PSA LIBRE');
    assert.ok((await answer.innerText()).includes('$39.87'));
    checks += 2;
    aiMode = 'down';
    answer = await ask('glucoas');
    assert.equal(await answer.locator('.mc-record').count(), 0);
    assert.ok((await answer.innerText()).includes('nombres parecidos'));
    await answer.getByRole('button', { name: 'GLUCOSA', exact: true }).click();
    await page.waitForFunction(() => !document.querySelector('.mc-submit').disabled);
    answer = page.locator('.mc-message:not(.mc-message-user)').last();
    assert.ok((await answer.innerText()).includes('$5.25'));
    checks += 3;
    aiMode = 'success';
    answer = await ask('Quiero cotizar aquel marcador llamado antígeno que solicitan para la próstata');
    assert.equal(await answer.locator('.mc-record').count(), 2);
    assert.ok((await answer.innerText()).includes('$23.45'));
    assert.ok(!(await answer.innerText()).includes('$0.00'));
    assert.equal(await page.evaluate(() => window.EXECUTED), undefined);
    assert.deepEqual(Object.keys(bodies.at(-1)).sort(), ['consulta', 'contexto', 'scope']);
    assert.ok(bodies.at(-1).contexto.every((key) => /^(exam|profile):\d+$/.test(key)));
    checks += 6;
    aiMode = 'invalid';
    answer = await ask('nombre muy extraño de un análisis desconocido');
    assert.equal(await answer.locator('.mc-record').count(), 0);
    assert.ok((await answer.innerText()).includes('No encontré'));
    checks += 2;
    const beforeMedical = bodies.length;
    answer = await ask('mis resultados de PSA, paciente de prueba');
    assert.ok((await answer.innerText()).includes('comunícate directamente'));
    assert.equal(bodies.length, beforeMedical);
    checks += 2;
    offline = true;
    answer = await ask('precio de PSA total');
    assert.equal(await answer.locator('.mc-record').count(), 0);
    assert.ok((await answer.innerText()).includes('no están disponibles'));
    answer = await ask('Horarios');
    assert.ok((await answer.innerText()).includes('Lunes a viernes'));
    checks += 3;
    offline = false;
    answer = await ask('PSA');
    assert.equal(await page.evaluate(() => window.EXECUTED), undefined);
    const dimensions = await page.evaluate(() => ({ doc: document.documentElement.scrollWidth, viewport: innerWidth, widget: document.querySelector('.mc-panel').getBoundingClientRect().width }));
    assert.ok(dimensions.doc <= dimensions.viewport + 1, JSON.stringify(dimensions));
    assert.ok(dimensions.widget <= dimensions.viewport, JSON.stringify(dimensions));
    assert.deepEqual(errors, []);
    checks += 4;
    await page.waitForFunction(() => {
      const list = document.querySelector('.mc-messages');
      return list.scrollHeight - list.clientHeight - list.scrollTop <= 2;
    });
    if (process.env.MERINO_SCREENSHOT_DIR) await page.screenshot({ path: `${process.env.MERINO_SCREENSHOT_DIR}/merino-chat-${width}.png` });
    await page.close();
  }
  console.log(`${checks} comprobaciones de navegador en escritorio y móviles: nombres, contexto, precio actualizado, IA simulada, fallos, texto seguro y anchura.`);
} finally { await browser.close(); }
