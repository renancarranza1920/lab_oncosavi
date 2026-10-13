import fs from 'node:fs';
import vm from 'node:vm';
import assert from 'node:assert/strict';
const base = new URL('../public_html/assets/', import.meta.url);
const context = vm.createContext({ console });
const modules = new Map();
async function source(name) {
  if (modules.has(name)) return modules.get(name);
  const m = new vm.SourceTextModule(fs.readFileSync(new URL(name, base), 'utf8'), { context, identifier: name });
  modules.set(name, m);
  await m.link((specifier) => source(specifier.replace('./', '')));
  return m;
}
const main = await source('merino-chat.js');
await main.evaluate();
const search = modules.get('merino-search.js').namespace;
const { validateCatalog, parseIntent, searchCatalog, validateInterpretation, formatPrice } = main.namespace;
let passed = 0;
const check = (fn) => { fn(); passed++; };
const names = [
  'PSA TOTAL', 'PSA libre', 'RELACIÓN PSA TOTAL/LIBRE', 'B-hcG-Cuant',
  'Prueba de embarazo sangre', 'Colesterol Alta Densidad - HDL', 'Colesterol Baja Densidad - LDL',
  'Hemoglobina Glicosilada AIC', 'Hemograma', 'T.G.O.', 'AST', 'T4 libre', 'T4 total', 'T3 libre',
  'Antígeno prostático específico total (PSA)', 'Proteína C reactiva', 'PCR MOLECULAR',
  'Hepatitis B Ag. de superficie', 'Anticuerpos de hepatitis B', 'CA 125', 'CA 15-3',
  'General de orina', 'Urocultivo', 'VIH prueba rápida', 'GLUCOSA', 'GLUCOSA', 'TSH 3ra Generación', 'Hb y Ht'
];
const data = { success: true, moneda: 'USD', asistente: { ia: true },
  examens: names.map((nombre, index) => ({ id: index + 1, nombre, precio: (index + 1 + 0.25).toFixed(2), categoria: index === 25 ? 'Química urinaria' : 'Laboratorio' })),
  perfiles: [{ id: 10, nombre: 'PERFIL LIPÍDICO', precio: '4.50', incluidos: ['HDL', 'LDL'] },
    { id: 11, nombre: 'PERFIL TIROIDEO', precio: '6.00', incluidos: ['TSH', 'T4'] }] };
const catalog = validateCatalog(data);
const ids = (query, scope = 'exams') => Array.from(searchCatalog(catalog, query, scope), (r) => r.id).sort((a, b) => a - b);
check(() => assert.deepEqual(ids('¿Me puede decir cuánto sale el antígeno prostático?'), [1, 2, 3, 15]));
check(() => assert.deepEqual(ids('antígeno específico de la próstata'), [1, 2, 3, 15]));
check(() => assert.deepEqual(ids('antígeno específico de próstata'), [1, 2, 3, 15]));
check(() => assert.deepEqual(ids('examen para la próstata'), [1, 2, 3, 15]));
check(() => assert.deepEqual(ids('PSA TOTAL'), [1, 15]));
check(() => assert.deepEqual(ids('antígeno prostático libre'), [2]));
check(() => assert.deepEqual(ids('P.S.A. libre'), [2]));
check(() => assert.deepEqual(ids('PSA total y libre'), [1, 2, 15]));
check(() => assert.deepEqual(ids('PSA totales y libres'), [1, 2, 15]));
check(() => assert.deepEqual(ids('relación psa total libre'), [3]));
for (const query of ['HbA1c', 'A1C', 'hemoglobina glucosilada', 'hemoglobina glicada', 'HBA 1 C']) {
  check(() => assert.deepEqual(ids(query), [8]));
}
for (const query of ['CBC', 'biometría hemática', 'conteo sanguíneo completo', 'hemograma completo']) {
  check(() => assert.deepEqual(ids(query), [9]));
}
check(() => assert.deepEqual(ids('colesterol bueno'), [6]));
check(() => assert.deepEqual(ids('colesterol malo'), [7]));
check(() => assert.deepEqual(ids('HDL'), [6]));
check(() => assert.deepEqual(ids('LDL'), [7]));
check(() => assert.deepEqual(ids('T.G.O.'), [10, 11]));
check(() => assert.deepEqual(ids('aspartato aminotransferasa'), [10, 11]));
check(() => assert.deepEqual(ids('tiroxina libre'), [12]));
check(() => assert.deepEqual(ids('tiroxina total'), [13]));
check(() => assert.deepEqual(ids('triyodotironina libre'), [14]));
check(() => assert.deepEqual(ids('hormona estimulante de la tiroides'), [27]));
check(() => assert.deepEqual(ids('beta hcg cuantitativa'), [4]));
check(() => assert.deepEqual(ids('embarazo'), [4, 5]));
check(() => assert.deepEqual(ids('prueba de embarazo en sangre'), [5]));
check(() => assert.deepEqual(ids('hcg cualitativo'), [5]));
check(() => assert.deepEqual(ids('proteína C reactiva'), [16]));
check(() => assert.deepEqual(ids('reacción en cadena de la polimerasa'), [17]));
check(() => assert.equal(parseIntent('cuánto cuesta PCR').type, 'pcr'));
check(() => assert.deepEqual(ids('PCR'), [16, 17]));
check(() => assert.deepEqual(ids('HBsAg'), [18]));
check(() => assert.deepEqual(ids('antígeno de superficie de hepatitis B'), [18]));
check(() => assert.deepEqual(ids('CA125'), [20]));
check(() => assert.deepEqual(ids('CA153'), [21]));
check(() => assert.deepEqual(ids('EGO'), [22]));
check(() => assert.deepEqual(ids('cultivo de orina'), [23]));
check(() => assert.deepEqual(ids('HIV'), [24]));
check(() => assert.deepEqual(ids('azúcar en sangre'), [25, 26]));
check(() => assert.deepEqual(ids('perfil de colesterol', 'profiles'), [10]));
check(() => assert.equal(searchCatalog(catalog, 'lipidograma', 'profiles')[0].price, '4.50'));
check(() => assert.deepEqual(ids('perfil tiroides', 'profiles'), [11]));
check(() => assert.deepEqual(ids('hemograma, hba1c y psa libre', 'all'), [2, 8, 9]));
check(() => assert.deepEqual(ids('Hb y Ht, glucosa', 'all'), [25, 26, 28]));
check(() => assert.deepEqual(ids('análisis desconocido 991122'), []));
check(() => assert.equal(search.resolveCatalog(catalog, 'glucoas', 'exams').approximate, true));
check(() => assert.deepEqual(ids('glucoas'), [25, 26]));
check(() => assert.equal(search.resolveCatalog(catalog, 'antigeno prostatco', 'exams').approximate, true));
check(() => assert.deepEqual(ids('antigeno prostatco'), [1, 2, 3, 15]));
check(() => assert.equal(search.resolveCatalog(catalog, 'hemograma y desconocido', 'exams').unmatched.length, 1));
check(() => assert.deepEqual(ids('T3'), [14]));
check(() => assert.deepEqual(ids('T4'), [12, 13]));
const psaRecords = searchCatalog(catalog, 'PSA', 'exams');
check(() => assert.equal(search.resolveFollowUp('¿y el libre?', psaRecords).query, 'psa libre'));
check(() => assert.equal(search.resolveFollowUp('¿y el total?', [catalog.exams[1]]).query, 'psa total'));
check(() => assert.equal(search.resolveFollowUp('¿cuánto cuesta ese?', [catalog.exams[1]]).query, 'PSA LIBRE'));
check(() => assert.equal(search.resolveFollowUp('¿cuánto cuesta ese?', psaRecords).clarify, true));
check(() => assert.equal(search.resolveFollowUp('el libre', [catalog.exams[0], catalog.exams[11]]).clarify, true));
check(() => assert.equal(search.resolveFollowUp('el libre', []).clarify, true));
check(() => assert.equal(parseIntent('mis resultados de PSA').key, 'results'));
check(() => assert.equal(parseIntent('tengo dolor qué análisis necesito').key, 'results'));
check(() => assert.equal(parseIntent('ayuno para PSA').key, 'preparation'));
check(() => assert.equal(parseIntent('a qué hora abren').key, 'hours'));
const interpretation = { success: true, kind: 'matches', keys: ['exam:1'], needs_clarification: false, precio: '0.00' };
check(() => assert.equal(validateInterpretation(interpretation, catalog).records[0].price, '1.25'));
check(() => assert.throws(() => validateInterpretation({ ...interpretation, keys: ['exam:999999'] }, catalog)));
check(() => assert.throws(() => validateInterpretation({ ...interpretation, keys: ['profile:10'] }, catalog, 'exams')));
check(() => assert.equal(validateInterpretation({ ...interpretation, keys: ['exam:10', 'profile:10'] }, catalog).records.length, 2));
check(() => assert.throws(() => validateInterpretation({ ...interpretation, kind: 'none' }, catalog)));
check(() => assert.equal(catalog.aiEnabled, true));
check(() => assert.equal(validateCatalog({ ...data, asistente: undefined }).aiEnabled, false));
check(() => assert.equal(formatPrice('-1.00'), null));
check(() => assert.equal(formatPrice('0.00'), '$0.00'));
check(() => assert.equal(/\binnerHTML\b|insertAdjacentHTML|document\.write\(/.test(fs.readFileSync(new URL('merino-chat.js', base), 'utf8')), false));
console.log(`${passed} comprobaciones: sinónimos, variantes, errores de escritura, contexto, precios del catálogo y respuestas de IA limitadas a registros públicos.`);
