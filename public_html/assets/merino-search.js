// Equivalencias de nombres; nunca contienen precios ni recomendaciones de exámenes.
const STOP_WORDS = new Set([
  'a', 'al', 'ante', 'como', 'con', 'cual', 'cuales', 'cuando', 'cuanto', 'cuantos',
  'cuanta', 'cuantas', 'cuesta', 'cuestan', 'costo', 'costos', 'de', 'del', 'dame',
  'dar', 'darme', 'el', 'ella', 'en', 'es', 'ese', 'esa', 'esta', 'este', 'estos',
  'estas', 'hay', 'hola', 'informacion', 'la', 'las', 'lo', 'los', 'me', 'mi',
  'necesito', 'para', 'por', 'precio', 'precios', 'puede', 'puedes', 'que', 'quiero',
  'saber', 'se', 'si', 'su', 'sus', 'tiene', 'tienen', 'un', 'una', 'unos', 'unas',
  'vale', 'valen', 'valor', 'valores', 'ver', 'y', 'e', 'examen', 'examenes', 'analisis',
  'prueba', 'pruebas', 'perfil', 'perfiles', 'paquete', 'paquetes', 'lista', 'listado',
  'listar', 'muestra', 'mostrar', 'muestrame', 'muestren', 'todo', 'todos', 'toda',
  'todas', 'favor', 'buenos', 'buenas', 'dias', 'dia', 'tardes', 'noches', 'incluye',
  'incluyen', 'incluido', 'incluidos', 'contiene', 'contienen', 'lleva', 'llevan',
  'disponible', 'disponibles', 'catalogo', 'laboratorio', 'donde', 'puedo', 'consultar',
  'quisiera', 'podria', 'podrias', 'podrian', 'gustaria', 'busco', 'buscando', 'ocupo',
  'hacen', 'hacer', 'hace', 'realizan', 'realizar', 'ofrecen', 'ofrece', 'manejan',
  'sale', 'salen', 'cobran', 'cobrar', 'decir', 'dices', 'digas', 'dice', 'sabes',
  'tendran', 'tendra', 'tendre', 'tengo', 'cotizar', 'cotizacion', 'averiguar',
  'buen', 'disculpa', 'disculpe', 'gracias', 'tambien', 'favorito', 'llamado', 'llamada'
]);

const ALIASES = [
  [/\b(?:antigeno (?:especifico (?:de (?:la )?)?prostata|(?:de (?:la )?)?prostata|prostatico(?: especifico)?)|prostate specific antigen|examen (?:de (?:la )?|para (?:la )?)prostata|prostata)\b/g, 'psa'],
  [/\bpsa\s*(total|libre)\b/g, 'psa $1'],
  [/\b(?:hemoglobina (?:glicosilada|glucosilada|glicada|a1c)|hba\s*1\s*c|a1c|hb a1c)\b/g, 'hba1c'],
  [/\b(?:biometria hematica|conteo sanguineo completo|cuadro hematico|complete blood count|cbc|hemograma completo)\b/g, 'hemograma'],
  [/\b(?:hemoglobina (?:y )?hematocrito|hb y ht)\b/g, 'hbht'],
  [/\b(?:hormona (?:tiroestimulante|estimulante (?:de (?:la )?|del )?tiroides)|tirotropina)\b/g, 'tsh'],
  [/\b(?:triyodotironina|tri yodotironina)\b/g, 't3'],
  [/\b(?:tiroxina|tetraiodotironina|tetrayodotironina)\b/g, 't4'],
  [/\b(?:perfil (?:de )?colesterol|perfil (?:de )?grasas|lipidograma)\b/g, 'perfil lipidico'],
  [/\b(?:tiroides|tiroidea)\b/g, 'tiroideo'],
  [/\b(?:azucar (?:en (?:la )?|de (?:la )?)sangre|azucar|glicemia|glucemia|glucosa en sangre)\b/g, 'glucosa'],
  [/\b(?:colesterol (?:bueno|de alta densidad)|lipoproteina de alta densidad)\b/g, 'hdl'],
  [/\b(?:colesterol (?:malo|de baja densidad)|lipoproteina de baja densidad)\b/g, 'ldl'],
  [/\b(?:transaminasa (?:glutamico oxalacetica|glutamica oxalacetica)|aspartato aminotransferasa|tgo|sgot)\b/g, 'ast'],
  [/\b(?:transaminasa (?:glutamico piruvica|glutamica piruvica)|alanina aminotransferasa|tgp|sgpt)\b/g, 'alt'],
  [/\b(?:nitrogeno ureico(?: en sangre)?|blood urea nitrogen|bun)\b/g, 'bun'],
  [/\b(?:velocidad de (?:eritro )?sedimentacion(?: globular)?|eritrosedimentacion|ves|vsg|esr)\b/g, 'vsg'],
  [/\b(?:tiempo de protrombina|prothrombin time|tp|pt)\b/g, 'tp'],
  [/\b(?:tiempo de (?:tromboplastina|tromb) parcial (?:activada|act)|tiempo de tromboplastina parcial|tpt|ttpa|aptt|ptt)\b/g, 'ttpa'],
  [/\b(?:examen general de orina|general de orina|examen de orina|urinalisis|uroanalisis|ego)\b/g, 'ego'],
  [/\b(?:examen general de heces|general de heces|examen de heces|coprologico|coprologia|coproanalisis)\b/g, 'heces'],
  [/\b(?:cultivo de orina|urocultivo)\b/g, 'urocultivo'],
  [/\b(?:cultivo de heces|coprocultivo)\b/g, 'coprocultivo'],
  [/\b(?:gonadotropina corionica humana|gonadotrofina corionica humana|beta hcg|b hcg|bhcg)\b/g, 'hcg'],
  [/\b(?:cuant|cuantitativas?|cuantitativos?)\b/g, 'cuantitativo'],
  [/\b(?:cual|cualitativas?|cualitativos?)\b/g, 'cualitativo'],
  [/\blibres\b/g, 'libre'],
  [/\btotales\b/g, 'total'],
  [/\bprueba de embarazo(?: en)? sangre\b/g, 'hcg cualitativo'],
  [/\b(?:prueba de embarazo|embarazo)\b/g, 'hcg'],
  [/\b(?:antigeno de superficie (?:de (?:la )?)?hepatitis b|hepatitis b ag (?:de )?superficie|hbsag)\b/g, 'hbsag'],
  [/\b(?:anticuerpos (?:contra (?:la )?|de (?:la )?)?hepatitis c|hepatitis c ac|anti hcv)\b/g, 'antihcv'],
  [/\b(?:virus de inmunodeficiencia humana|hiv|vih)\b/g, 'vih'],
  [/\b(?:antigeno carcinoembrionario|cea ag carcioembrionario|cea)\b/g, 'cea'],
  [/\b(?:alfa feto proteina|alfa fetoproteina|alfafetoproteina|afp)\b/g, 'afp'],
  [/\bca\s*15\s*3\b/g, 'ca153'],
  [/\bca\s*19\s*9\b/g, 'ca199'],
  [/\bca\s*125\b/g, 'ca125'],
  [/\b(?:grupo sanguineo(?: y factor rh)?|tipo de sangre|tipeo sanguineo(?: y factor rh)?)\b/g, 'tipeo'],
  [/\b(?:factor reumatoide|factor reumatoideo(?: latex ra)?|latex ra)\b/g, 'reumatoideo'],
  [/\b(?:antistreptolisina o|antiestreptolisina o|aso)\b/g, 'aso'],
  [/\b(?:proteina c reactiva|proteina c reactiva pcr)\b/g, 'pcr reactiva'],
  [/\b(?:reaccion en cadena de la polimerasa|polymerase chain reaction)\b/g, 'pcr molecular'],
  [/\b(?:anti cuerpos|anticuerpo)\b/g, 'anticuerpos']
];
const VARIANTS = new Set(['libre', 'total', 'cuantitativo', 'cualitativo', 'igg', 'igm', 'rapida']);
const PROTECTED = new Set(['libre', 'total', 'cuantitativo', 'cualitativo', 'reactiva', 'molecular',
  'antigeno', 'anticuerpos', 'orina', 'sangre', 'suero', 'igg', 'igm', 'iga', 'ige']);
const EXPANSIONS = {
  psa: ['antigeno', 'prostatico', 'prostata'],
  hba1c: ['hemoglobina', 'glicosilada', 'glucosilada', 'glicada'],
  hemograma: ['biometria', 'hematica', 'hematico'],
  glucosa: ['glicemia', 'glucemia', 'azucar'],
  hbht: ['hemoglobina', 'hematocrito'],
  tsh: ['tirotropina', 'tiroestimulante'],
  ego: ['orina', 'uroanalisis', 'urinalisis'],
  afp: ['alfafetoproteina', 'fetoproteina'],
  hcg: ['embarazo', 'gonadotropina', 'corionica'],
  bun: ['nitrogeno', 'ureico'],
  vsg: ['eritrosedimentacion', 'sedimentacion']
};

export function normalizeQuery(value) {
  return String(value || '').replace(/\b(?:[a-z]\.){2,}[a-z]?\.?/gi, (text) => text.replace(/\./g, ''))
    .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
    .toLowerCase().replace(/[^a-z0-9]+/g, ' ').trim().replace(/\s+/g, ' ');
}

export function canonicalQuery(value) {
  let normalized = normalizeQuery(value);
  for (const [pattern, replacement] of ALIASES) normalized = normalized.replace(pattern, replacement);
  return normalized;
}

export function queryTerms(value) {
  return [...new Set(canonicalQuery(value).split(' ').filter((token) => token && !STOP_WORDS.has(token)))];
}

function segmentsOf(query) {
  // Hb y Ht es un nombre compuesto. Las demás comas/conjunciones separan consultas.
  const input = String(query || '').replace(/\b(?:hemoglobina\s+(?:y\s+)?hematocrito|hb\s+y\s+ht)\b/gi, 'hbht');
  const segments = input.split(/\s+(?:y|e)\s+|[,;]+/i).map(queryTerms).filter((terms) => terms.length);
  for (let index = 1; index < segments.length; index += 1) {
    if (segments[index].length === 1 && VARIANTS.has(segments[index][0])) {
      const base = segments[index - 1].filter((term) => !VARIANTS.has(term));
      if (base.length) segments[index] = base.concat(segments[index]);
    }
  }
  return segments;
}

function wordMatches(term, word) {
  return term === word || (!PROTECTED.has(term) && !/\d/.test(term) && term.length >= 4 && word.startsWith(term));
}

// Distancia con transposiciones: "glucoas" puede sugerir "glucosa".
function editDistance(a, b) {
  if (Math.abs(a.length - b.length) > 2) return 3;
  const matrix = Array.from({ length: a.length + 1 }, (_, i) => [i]);
  matrix[0] = Array.from({ length: b.length + 1 }, (_, i) => i);
  for (let i = 1; i <= a.length; i += 1) {
    for (let j = 1; j <= b.length; j += 1) {
      matrix[i][j] = Math.min(matrix[i - 1][j] + 1, matrix[i][j - 1] + 1,
        matrix[i - 1][j - 1] + Number(a[i - 1] !== b[j - 1]));
      if (i > 1 && j > 1 && a[i - 1] === b[j - 2] && a[i - 2] === b[j - 1]) {
        matrix[i][j] = Math.min(matrix[i][j], matrix[i - 2][j - 2] + 1);
      }
    }
  }
  return matrix[a.length][b.length];
}

function approximateMatch(term, words) {
  if (term.length < 5 || /\d/.test(term) || PROTECTED.has(term)) return false;
  const allowance = term.length >= 9 ? 2 : 1;
  return words.some((word) => word.length >= 5 && !/\d/.test(word) && !PROTECTED.has(word)
    && editDistance(term, word) <= allowance);
}

export function resolveCatalog(catalog, query, scope = 'all') {
  const records = scope === 'profiles' ? catalog.profiles :
    scope === 'exams' ? catalog.exams : catalog.exams.concat(catalog.profiles);
  const segments = segmentsOf(query);
  if (!segments.length) return { records: records.slice().sort((a, b) => a.name.localeCompare(b.name, 'es')), approximate: false, unmatched: [] };
  const ranked = new Map();
  const unmatched = [];
  let approximate = false;
  for (const terms of segments) {
    const rank = (fuzzy) => records.flatMap((record) => {
      const name = canonicalQuery(record.name);
      const nameWords = queryTerms(record.name);
      const words = nameWords.concat(nameWords.flatMap((word) => EXPANSIONS[word] || []));
      const categoryWords = canonicalQuery(record.category).split(' ');
      for (const [one, other] of [['total', 'libre'], ['cuantitativo', 'cualitativo']]) {
        if (terms.includes(one) && !terms.includes(other) && nameWords.includes(other)) return [];
        if (terms.includes(other) && !terms.includes(one) && nameWords.includes(one)) return [];
      }
      const matches = terms.every((term) => words.some((word) => wordMatches(term, word))
        || categoryWords.includes(term) || (fuzzy && approximateMatch(term, words)));
      if (!matches) return [];
      const joined = terms.join(' ');
      return [{ record, score: fuzzy ? 25 : name === joined ? 100 : name.includes(joined) ? 75 : 50 }];
    });
    let matches = rank(false);
    if (!matches.length) {
      matches = rank(true);
      if (matches.length) approximate = true;
    }
    if (!matches.length) unmatched.push(terms.join(' '));
    for (const entry of matches) {
      const key = entry.record.type + ':' + entry.record.id;
      if (!ranked.has(key) || ranked.get(key).score < entry.score) ranked.set(key, entry);
    }
  }
  return {
    records: [...ranked.values()].sort((a, b) => b.score - a.score || a.record.name.localeCompare(b.record.name, 'es')).map((entry) => entry.record),
    approximate, unmatched
  };
}

export function searchCatalog(catalog, query, scope = 'all') {
  return resolveCatalog(catalog, query, scope).records;
}

export function describeQuery(query) {
  const meanings = {
    psa: 'PSA significa ANTÍGENO PROSTÁTICO ESPECÍFICO. El PSA TOTAL, el PSA LIBRE y la relación entre ambos son opciones distintas.',
    hba1c: 'HBA1C también se conoce como HEMOGLOBINA GLICOSILADA, GLUCOSILADA o GLICADA.',
    hemograma: 'El HEMOGRAMA también se conoce como BIOMETRÍA HEMÁTICA o CBC.',
    tsh: 'TSH es la HORMONA ESTIMULANTE DE LA TIROIDES, también llamada TIROTROPINA.'
  };
  return queryTerms(query).filter((term) => meanings[term]).map((term) => meanings[term]);
}

export function resolveFollowUp(value, previous) {
  const normalized = normalizeQuery(value);
  const terms = queryTerms(value).filter((word) => !['ese', 'esa', 'eso', 'mismo', 'misma', 'entonces'].includes(word));
  const refersBack = /\b(ese|esa|eso|mismo|misma)\b/.test(normalized);
  const variantOnly = terms.length > 0 && terms.every((term) => VARIANTS.has(term));
  if (!refersBack && !variantOnly) return { query: value, clarify: false };
  if (!previous?.length) return { query: value, clarify: true };
  if (refersBack && !terms.length) {
    return previous.length === 1 ? { query: previous[0].name, clarify: false } : { query: value, clarify: true };
  }
  if (variantOnly) {
    const bases = [...new Set(previous.map((record) => {
      const words = queryTerms(record.name);
      const family = ['psa', 'hcg', 't3', 't4'].find((key) => words.includes(key));
      return record.type === 'exam' && family ? family : words.filter((term) => !VARIANTS.has(term)).join(' ');
    }))];
    if (bases.length === 1 && bases[0]) return { query: bases[0] + ' ' + terms.join(' '), clarify: false };
    return { query: value, clarify: true };
  }
  return { query: value, clarify: false };
}
