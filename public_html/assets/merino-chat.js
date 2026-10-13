import { MERINO_CHAT_CONFIG as config } from './merino-chat-config.js';

// Reglas locales: no se envían preguntas a servicios externos y no se guardan conversaciones.
const STOP_WORDS = new Set([
  'a', 'al', 'ante', 'como', 'con', 'cual', 'cuales', 'cuando', 'cuanto', 'cuantos',
  'cuanta', 'cuantas', 'cuesta', 'cuestan', 'costo', 'costos', 'de', 'del', 'dame',
  'dar', 'darme', 'el', 'ella', 'en', 'es', 'ese', 'esa', 'esta', 'este', 'estos',
  'estas', 'hay', 'hola', 'informacion', 'la', 'las', 'lo', 'los', 'me', 'mi',
  'necesito', 'para', 'por', 'precio', 'precios', 'puede', 'puedes', 'que', 'quiero',
  'saber', 'se', 'si', 'su', 'sus', 'tiene', 'tienen', 'un', 'una', 'unos', 'unas',
  'vale', 'valen', 'valor', 'valores', 'ver', 'y', 'e', 'examen', 'examenes', 'analisis', 'prueba',
  'pruebas', 'perfil', 'perfiles', 'paquete', 'paquetes', 'lista', 'listado',
  'listar', 'muestra', 'mostrar', 'muestrame', 'muestren', 'todo', 'todos',
  'toda', 'todas', 'favor', 'buenos', 'buenas', 'dias', 'dia', 'tardes', 'noches',
  'incluye', 'incluyen', 'incluido', 'incluidos', 'contiene', 'contienen',
  'lleva', 'llevan', 'disponible', 'disponibles', 'catalogo', 'laboratorio',
  'donde', 'puedo', 'consultar'
]);
const ALIASES = [
  [/\bbiometria hematica\b/g, 'hemograma'],
  [/\bcbc\b/g, 'hemograma'],
  [/\bglucosa en sangre\b/g, 'glucosa'],
  [/\btiroides\b/g, 'tiroideo'],
  [/\btiroidea\b/g, 'tiroideo']
];

export function normalizeQuery(value) {
  return String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '')
    .toLowerCase().replace(/[^a-z0-9]+/g, ' ').trim().replace(/\s+/g, ' ');
}

function canonicalQuery(value) {
  let normalized = normalizeQuery(value);
  for (const [pattern, replacement] of ALIASES) normalized = normalized.replace(pattern, replacement);
  // "Hemograma completo" se busca como hemograma; se muestran todas las coincidencias.
  return normalized.replace(/\bhemograma completo\b/g, 'hemograma');
}

function queryTerms(value) {
  return canonicalQuery(value).split(' ').filter((token) => token && !STOP_WORDS.has(token));
}

export function parseIntent(value) {
  const normalized = normalizeQuery(value);
  const has = (pattern) => pattern.test(normalized);
  if (!normalized || has(/^(hola|buenos dias|buenas tardes|buenas noches|menu|ayuda|inicio)$/)) {
    return { type: 'menu' };
  }
  if (has(/\b(horario|horarios|abren|abre|cierran|cierra|abierto|abiertos|atienden|domingo|sabado)\b/)) {
    return { type: 'info', key: 'hours' };
  }
  if (has(/\b(domicilio|domicilios|casa|visita)\b/)) return { type: 'info', key: 'home' };
  const asksPrice = has(/\b(precio|precios|costo|costos|cuanto|cuantos|cuesta|cuestan|vale|valen|valor|valores)\b/);
  if (has(/\b(ubicacion|ubicados|direccion|mapa|llegar|queda|quedan)\b/) ||
      (has(/\bdonde\b/) && !asksPrice)) {
    return { type: 'info', key: 'location' };
  }
  if (has(/\b(resultados|resultado|informe|informes|expediente|paciente|pacientes|dui|diagnostico|diagnosticar|interpretar|interpretacion)\b/) ||
      has(/\bvalores? (?:de )?referencia\b/)) {
    return { type: 'info', key: 'results' };
  }
  if (has(/\b(preparacion|prepararme|ayuno|ayunas|comer|comida|medicamento|medicamentos|indicaciones|requisitos)\b/)) {
    return { type: 'info', key: 'preparation' };
  }
  if (has(/\b(pago|pagos|pagar|tarjeta|tarjetas|efectivo|transferencia|seguro|seguros|aseguradora|factura|facturas)\b/)) {
    return { type: 'info', key: 'payments' };
  }
  if (has(/\b(contacto|contactar|telefono|telefonos|llamar|llamada|whatsapp|correo|email|recepcion|persona|humano|humana|asesor)\b/)) {
    return { type: 'info', key: 'contact' };
  }
  if (has(/\b(redes|facebook|instagram|siguenos|sociales)\b/)) {
    return { type: 'info', key: 'social' };
  }
  if (has(/\b(nosotros|mision|vision)\b/) || has(/^quienes (somos|son)$/)) {
    return { type: 'info', key: 'about' };
  }
  if (has(/^(servicios|que servicios ofrecen|areas|especialidades)$/)) {
    return { type: 'info', key: 'services' };
  }
  const terms = queryTerms(normalized);
  if (terms.length === 1 && terms[0] === 'pcr') {
    return { type: 'pcr', query: value, scope: 'exams' };
  }
  const asksProfiles = has(/\b(perfil|perfiles|paquete|paquetes)\b/);
  const asksExams = has(/\b(examen|examenes|prueba|pruebas)\b/);
  const scope = asksProfiles && asksExams ? 'all' : asksProfiles ? 'profiles' : 'exams';
  const multiple = String(value).split(/\s+(?:y|e)\s+|[,;]+/i).filter((part) => queryTerms(part).length > 0).length > 1;
  if (!terms.length) return { type: 'catalog', scope, query: '' };
  return { type: 'catalog', scope: multiple ? 'all' : scope, query: value };
}

export function formatPrice(value) {
  const text = typeof value === 'number' ? String(value) : value;
  if (typeof text !== 'string' || !/^\d+(?:\.\d{1,2})?$/.test(text)) return null;
  const amount = Number(text);
  if (!Number.isFinite(amount) || amount < 0 || amount > Number.MAX_SAFE_INTEGER / 100) return null;
  return '$' + amount.toFixed(2);
}

export function validateCatalog(data) {
  if (!data || data.success !== true || data.moneda !== 'USD' ||
      !Array.isArray(data.examens) || !Array.isArray(data.perfiles)) {
    throw new Error('Catálogo no disponible.');
  }
  const readName = (name) => {
    if (typeof name !== 'string' || !name.trim() || name.length > 500) {
      throw new Error('Nombre de catálogo inválido.');
    }
    return name.trim();
  };
  const readRecord = (entry, type) => {
    if (!entry || !Number.isSafeInteger(Number(entry.id)) || Number(entry.id) < 1 ||
        formatPrice(entry.precio) === null) throw new Error('Registro de catálogo inválido.');
    const result = {
      id: Number(entry.id), name: readName(entry.nombre), price: entry.precio,
      type, category: typeof entry.categoria === 'string' ? entry.categoria.slice(0, 500) : ''
    };
    if (type === 'profile') {
      if (!Array.isArray(entry.incluidos)) throw new Error('Perfil inválido.');
      result.included = entry.incluidos.map(readName);
    }
    return result;
  };
  return {
    currency: 'USD',
    exams: data.examens.map((entry) => readRecord(entry, 'exam')),
    profiles: data.perfiles.map((entry) => readRecord(entry, 'profile'))
  };
}

export function searchCatalog(catalog, query, scope = 'all') {
  const records = scope === 'profiles' ? catalog.profiles :
    scope === 'exams' ? catalog.exams : catalog.exams.concat(catalog.profiles);
  const rawSegments = String(query || '').split(/\s+(?:y|e)\s+|[,;]+/i);
  const segments = rawSegments.map(queryTerms).filter((terms) => terms.length > 0);
  if (!segments.length) return records.slice().sort((a, b) => a.name.localeCompare(b.name, 'es'));
  const ranked = [];
  for (const record of records) {
    const name = canonicalQuery(record.name);
    const words = name.split(' ');
    const categoryWords = canonicalQuery(record.category).split(' ');
    let best = 0;
    for (const terms of segments) {
      // Se requieren todos los términos de cada búsqueda: no se elige una coincidencia arbitraria.
      const matches = terms.every((term) => words.some((word) =>
        word === term || (term.length >= 4 && word.startsWith(term))) ||
        categoryWords.some((word) => word === term));
      if (!matches) continue;
      const joined = terms.join(' ');
      const score = name === joined ? 100 :
        name.includes(joined) ? 75 : 50;
      best = Math.max(best, score);
    }
    if (best) ranked.push({ record, score: best });
  }
  return ranked.sort((a, b) => b.score - a.score || a.record.name.localeCompare(b.record.name, 'es'))
    .map((entry) => entry.record);
}

function createNode(tag, className, text) {
  const element = document.createElement(tag);
  if (className) element.className = className;
  if (text !== undefined) element.textContent = text;
  return element;
}

function createLink(label, href) {
  const link = createNode('a', '', label);
  link.href = href;
  if (href.startsWith('https://')) {
    link.target = '_blank';
    link.rel = 'noopener noreferrer';
  }
  return link;
}

function appendParagraph(parent, text, className) {
  const paragraph = createNode('p', className || '', text);
  parent.append(paragraph);
  return paragraph;
}

function createSpeechIcon() {
  const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
  svg.setAttribute('viewBox', '0 0 24 24');
  svg.setAttribute('width', '26');
  svg.setAttribute('height', '26');
  svg.setAttribute('fill', 'none');
  svg.setAttribute('stroke', 'currentColor');
  svg.setAttribute('stroke-width', '1.8');
  svg.setAttribute('aria-hidden', 'true');
  const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
  path.setAttribute('d', 'M21 11.5a8.5 8.5 0 0 1-8.5 8.5H4l-2 2v-9.5A8.5 8.5 0 0 1 10.5 4H12a9 9 0 0 1 9 7.5ZM7 11h10M7 15h6');
  svg.append(path);
  return svg;
}

function mountChat() {
  if (document.getElementById('merino-chat')) return;
  const widget = createNode('section');
  widget.id = 'merino-chat';
  widget.setAttribute('aria-label', 'Consultas del Laboratorio Clínico Merino');
  const launcher = createNode('button', 'mc-launcher');
  launcher.type = 'button';
  launcher.setAttribute('aria-expanded', 'false');
  launcher.setAttribute('aria-controls', 'merino-chat-panel');
  launcher.setAttribute('aria-label', 'Abrir asistente de consultas Merino');
  launcher.append(createSpeechIcon(), createNode('span', 'mc-launcher-label', '¿En qué te ayudamos?'));
  const panel = createNode('div', 'mc-panel');
  panel.id = 'merino-chat-panel';
  panel.hidden = true;
  panel.setAttribute('role', 'dialog');
  panel.setAttribute('aria-labelledby', 'merino-chat-title');
  // Es un diálogo no modal: el visitante puede seguir recorriendo la página.
  panel.setAttribute('aria-modal', 'false');
  const header = createNode('div', 'mc-header');
  const logo = createNode('img', 'mc-logo');
  logo.src = '/iconlab.png';
  logo.alt = '';
  const titles = createNode('div');
  const heading = createNode('h2', 'mc-heading', 'Asistente Merino');
  heading.id = 'merino-chat-title';
  titles.append(heading, createNode('p', 'mc-subheading', 'Precios y atención al público'));
  const close = createNode('button', 'mc-close', '×');
  close.type = 'button';
  close.setAttribute('aria-label', 'Cerrar asistente');
  header.append(logo, titles, close);
  const messages = createNode('div', 'mc-messages');
  messages.setAttribute('role', 'log');
  messages.setAttribute('aria-live', 'polite');
  messages.setAttribute('aria-relevant', 'additions text');
  messages.setAttribute('aria-label', 'Conversación de consultas');
  const form = createNode('form', 'mc-form');
  const label = createNode('label', 'mc-sr-only', 'Escribe tu pregunta');
  label.htmlFor = 'merino-chat-input';
  const input = createNode('input', 'mc-input');
  input.id = 'merino-chat-input';
  input.type = 'text';
  input.name = 'consulta';
  input.maxLength = 400;
  input.placeholder = 'Ej.: precio del hemograma';
  input.autocomplete = 'off';
  const submit = createNode('button', 'mc-submit', 'Enviar');
  submit.type = 'submit';
  form.append(label, input, submit);
  const footer = createNode('div', 'mc-footer');
  footer.append(createLink('Hablar con recepción por WhatsApp', config.whatsappUrl));
  panel.append(header, messages, form, footer);
  widget.append(launcher, panel);
  document.body.append(widget);

  let catalogPromise = null;

  function scrollConversation() {
    messages.scrollTop = messages.scrollHeight;
  }

  function message(text, user = false) {
    const item = createNode('div', 'mc-message' + (user ? ' mc-message-user' : ''));
    item.setAttribute('aria-label', user ? 'Tu consulta' : 'Respuesta de Merino');
    if (text) appendParagraph(item, text);
    messages.append(item);
    scrollConversation();
    return item;
  }

  function questionButton(labelText, query) {
    const button = createNode('button', 'mc-chip', labelText);
    button.type = 'button';
    button.addEventListener('click', () => ask(query));
    return button;
  }

  function menu(parent) {
    const choices = createNode('div', 'mc-chips');
    for (const option of config.questions) choices.append(questionButton(option.label, option.query));
    parent.append(choices);
    scrollConversation();
  }

  function contactLink(parent, labelText = 'Consultar con recepción por WhatsApp') {
    const paragraph = createNode('p');
    paragraph.append(createLink(labelText, config.whatsappUrl));
    parent.append(paragraph);
  }

  function list(parent, entries, ordered = false) {
    const content = createNode(ordered ? 'ol' : 'ul');
    for (const text of entries) content.append(createNode('li', '', text));
    parent.append(content);
  }

  function showInfo(key) {
    const response = message('');
    if (key === 'hours') {
      appendParagraph(response, 'Nuestros horarios de atención son:');
      list(response, config.hours);
      appendParagraph(response, 'Para horarios especiales o días festivos, confirma con recepción.');
    } else if (key === 'home') {
      appendParagraph(response, 'Sí, ofrecemos servicio de toma de muestras a domicilio:');
      list(response, config.homeServiceSteps, true);
      appendParagraph(response, 'La disponibilidad, cobertura y el costo del servicio se confirman al coordinarlo.');
    } else if (key === 'location') {
      appendParagraph(response, config.address);
      const paragraph = createNode('p');
      paragraph.append(createLink('Ver ubicación en Waze', config.mapUrl));
      response.append(paragraph);
    } else if (key === 'contact') {
      appendParagraph(response, config.name);
      const phone = createNode('p');
      phone.append(createLink('Teléfono: ' + config.phone, config.phoneUrl));
      const email = createNode('p');
      email.append(createLink('Correo: ' + config.email, 'mailto:' + config.email));
      response.append(phone, email);
      contactLink(response, 'WhatsApp: ' + config.whatsapp);
    } else if (key === 'results') {
      appendParagraph(response, 'Para consultar la entrega, disponibilidad o acceso a tus resultados, comunícate directamente con el laboratorio.');
      appendParagraph(response, 'Este asistente muestra información pública; no consulta expedientes, resultados personales ni interpreta resultados.');
    } else if (key === 'preparation') {
      appendParagraph(response, 'La preparación depende del examen. Confirma con recepción las indicaciones, requisitos y tiempos antes de acudir.');
      appendParagraph(response, 'Puedes consultar aquí el nombre y precio del examen y pedir sus indicaciones al laboratorio.');
    } else if (key === 'payments') {
      appendParagraph(response, 'Confirma con recepción las formas de pago, facturación, convenios, seguros y promociones vigentes.');
      appendParagraph(response, 'Los precios de exámenes y perfiles disponibles se muestran en dólares estadounidenses.');
    } else if (key === 'services') {
      appendParagraph(response, 'Contamos con estas áreas de servicio:');
      list(response, config.services);
      const choices = createNode('div', 'mc-chips');
      choices.append(questionButton('Ver exámenes y precios', 'Lista de exámenes y precios'),
        questionButton('Ver perfiles', 'Lista de perfiles y precios'));
      response.append(choices);
    } else if (key === 'social') {
      appendParagraph(response, 'Encuéntranos en nuestras redes oficiales:');
      for (const network of config.socialLinks) {
        const paragraph = createNode('p');
        paragraph.append(createLink(network.label, network.url));
        response.append(paragraph);
      }
    } else if (key === 'about') {
      appendParagraph(response, config.name);
      for (const item of config.about || []) {
        const paragraph = createNode('p');
        paragraph.append(createNode('strong', '', item.label + ': '), document.createTextNode(item.text));
        response.append(paragraph);
      }
    }
    if (key !== 'contact' && key !== 'location') contactLink(response);
    scrollConversation();
  }

  async function loadCatalog() {
    if (catalogPromise) return catalogPromise;
    catalogPromise = (async () => {
      const controller = new AbortController();
      const timeout = window.setTimeout(() => controller.abort(), 10000);
      try {
        const response = await fetch(new URL(config.catalogUrl, document.baseURI), {
          method: 'GET', credentials: 'same-origin', cache: 'no-store',
          headers: { Accept: 'application/json' }, signal: controller.signal
        });
        if (!response.ok) throw new Error('Catálogo no disponible.');
        return validateCatalog(await response.json());
      } finally {
        window.clearTimeout(timeout);
        catalogPromise = null;
      }
    })();
    return catalogPromise;
  }

  function renderRecords(response, records) {
    const container = createNode('div', 'mc-records');
    response.append(container);
    let visible = 0;
    const count = createNode('p', 'mc-count');
    const more = createNode('button', 'mc-more', 'Ver más');
    more.type = 'button';
    response.append(count, more);

    function appendNextPage() {
      const end = Math.min(visible + config.pageSize, records.length);
      for (; visible < end; visible += 1) {
        const record = records[visible];
        const card = createNode('div', 'mc-record');
        appendParagraph(card, record.name, 'mc-record-name');
        appendParagraph(card, formatPrice(record.price) + ' USD', 'mc-record-price');
        appendParagraph(card, record.type === 'profile' ? 'Perfil' :
          'Examen' + (record.category ? ' · ' + record.category : ''), 'mc-record-meta');
        if (record.type === 'profile') {
          const details = createNode('details');
          details.open = records.length === 1;
          details.append(createNode('summary', '', 'Ver ' + record.included.length + ' exámenes incluidos'));
          list(details, record.included);
          card.append(details);
        }
        container.append(card);
      }
      count.textContent = 'Mostrando ' + visible + ' de ' + records.length + '.';
      more.hidden = visible >= records.length;
      more.textContent = 'Ver más (' + Math.min(config.pageSize, records.length - visible) + ')';
      if (more.hidden) {
        count.tabIndex = -1;
        if (document.activeElement === more) count.focus({ preventScroll: true });
      }
    }
    more.addEventListener('click', appendNextPage);
    appendNextPage();
  }

  async function showCatalog(intent) {
    const response = message('Consultando el catálogo…');
    response.setAttribute('aria-busy', 'true');
    try {
      const loaded = await loadCatalog();
      response.replaceChildren();
      const records = searchCatalog(loaded, intent.type === 'pcr' ? 'PCR' : intent.query, intent.scope);
      if (intent.type === 'pcr') {
        appendParagraph(response, 'PCR puede referirse a pruebas distintas. Indica el nombre completo que deseas consultar.');
        if (records.length) appendParagraph(response, 'Estas son las coincidencias publicadas con ese nombre; revisa cuál corresponde a tu consulta:');
      } else if (!records.length) {
        appendParagraph(response, 'No encontré una coincidencia en el catálogo público para esa consulta.');
        appendParagraph(response, 'Prueba con el nombre del examen o consulta con recepción. La ausencia en esta lista no confirma que el laboratorio no lo realice.');
      } else if (!intent.query) {
        appendParagraph(response, intent.scope === 'profiles' ? 'Perfiles disponibles y su precio propio:' :
          intent.scope === 'all' ? 'Exámenes y perfiles disponibles con sus precios:' :
            'Exámenes disponibles y sus precios:');
      } else if (records.length === 1) {
        appendParagraph(response, 'Encontré esta coincidencia en el catálogo público:');
      } else {
        appendParagraph(response, 'Encontré ' + records.length + ' coincidencias. Revisa sus nombres o escribe una consulta más específica:');
      }
      if (records.length) {
        renderRecords(response, records);
        appendParagraph(response, 'Precios publicados en dólares estadounidenses. Confirma con recepción disponibilidad e indicaciones para tu visita.', 'mc-status');
      }
      if (!records.length || intent.type === 'pcr') contactLink(response);
      if (!records.length && intent.type !== 'pcr') {
        const choices = createNode('div', 'mc-chips');
        choices.append(questionButton('Todos los exámenes', 'Lista de exámenes y precios'),
          questionButton('Todos los perfiles', 'Lista de perfiles y precios'));
        response.append(choices);
      }
    } catch {
      response.replaceChildren();
      appendParagraph(response, 'Los precios y el catálogo no están disponibles en este momento. Puedes consultar horarios, ubicación y contacto.');
      contactLink(response, 'Consultar precios por WhatsApp');
      const retry = questionButton('Volver a consultar el catálogo', intent.query ||
        (intent.scope === 'profiles' ? 'Lista de perfiles y precios' : 'Lista de exámenes y precios'));
      response.append(retry);
    } finally {
      response.removeAttribute('aria-busy');
      scrollConversation();
    }
  }

  async function ask(value) {
    const query = String(value || '').trim().slice(0, 400);
    if (!query) return;
    message(query, true);
    const intent = parseIntent(query);
    if (intent.type === 'menu') {
      const response = message('Puedo ayudarte con los precios publicados y la información de atención. Elige una opción o escribe el nombre del examen:');
      menu(response);
    } else if (intent.type === 'info') showInfo(intent.key);
    else await showCatalog(intent);
  }

  function setOpen(open) {
    panel.hidden = !open;
    launcher.hidden = open;
    launcher.setAttribute('aria-expanded', String(open));
    if (open) {
      input.focus({ preventScroll: true });
      scrollConversation();
    } else launcher.focus({ preventScroll: true });
  }
  launcher.addEventListener('click', () => setOpen(true));
  close.addEventListener('click', () => setOpen(false));
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !panel.hidden) {
      event.preventDefault();
      setOpen(false);
    }
  });
  form.addEventListener('submit', (event) => {
    event.preventDefault();
    const value = input.value;
    input.value = '';
    ask(value);
  });
  const greeting = message('Hola. Consulta precios de exámenes y perfiles, horarios, ubicación y servicios. Escribe una pregunta o elige una opción.');
  appendParagraph(greeting, 'No compartas datos personales ni resultados médicos en esta consulta.', 'mc-status');
  menu(greeting);
}

if (typeof document !== 'undefined') {
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', mountChat, { once: true });
  else mountChat();
}
