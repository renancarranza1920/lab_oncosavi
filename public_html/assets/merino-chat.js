import { MERINO_CHAT_CONFIG as config } from './merino-chat-config.js';

import { normalizeQuery, queryTerms, searchCatalog, resolveCatalog, resolveFollowUp, describeQuery } from './merino-search.js';
export { normalizeQuery, searchCatalog };

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
  if (has(/\b(resultados|resultado|informe|informes|expediente|paciente|pacientes|dui|diagnostico|diagnosticar|interpretar|interpretacion|sintomas|sintoma|dolor|duele)\b/) ||
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
    return name.trim().toLocaleUpperCase('es');
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
    aiEnabled: data.asistente?.ia === true,
    exams: data.examens.map((entry) => readRecord(entry, 'exam')),
    profiles: data.perfiles.map((entry) => readRecord(entry, 'profile'))
  };
}

export function validateInterpretation(data, catalog, scope = 'all') {
  if (!data || data.success !== true || !['matches', 'none', 'contact'].includes(data.kind)
      || !Array.isArray(data.keys) || data.keys.length > 20 || typeof data.needs_clarification !== 'boolean') {
    throw new Error('Consulta no interpretada.');
  }
  const records = scope === 'profiles' ? catalog.profiles : scope === 'exams' ? catalog.exams : catalog.exams.concat(catalog.profiles);
  const allowed = new Map(records.map((record) => [record.type + ':' + record.id, record]));
  if (data.keys.some((key) => typeof key !== 'string' || !allowed.has(key))
      || (data.kind === 'matches') !== (data.keys.length > 0)) throw new Error('Coincidencia no válida.');
  return { kind: data.kind, records: [...new Set(data.keys)].map((key) => allowed.get(key)),
    clarify: data.needs_clarification || data.keys.length > 1 };
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
  let previousRecords = [];
  let busy = false;

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

  async function interpretQuery(query, catalog, scope, previous) {
    const controller = new AbortController();
    const timeout = window.setTimeout(() => controller.abort(), 12000);
    try {
      const response = await fetch(new URL(config.interpretationUrl, document.baseURI), {
        method: 'POST', credentials: 'same-origin', cache: 'no-store',
        headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ consulta: query, scope, contexto: previous.map((record) => record.type + ':' + record.id) }),
        signal: controller.signal
      });
      if (!response.ok) throw new Error('No se pudo interpretar la consulta.');
      return validateInterpretation(await response.json(), catalog, scope);
    } finally { window.clearTimeout(timeout); }
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

  async function showCatalog(intent, originalQuery) {
    const response = message('Consultando el catálogo…');
    response.setAttribute('aria-busy', 'true');
    try {
      const loaded = await loadCatalog();
      let resolved = resolveCatalog(loaded, intent.type === 'pcr' ? 'PCR' : intent.query, intent.scope);
      let understoodByAI = false;
      if (loaded.aiEnabled && intent.query && intent.type !== 'pcr'
          && (!resolved.records.length || resolved.approximate || resolved.unmatched.length)) {
        try {
          const interpretation = await interpretQuery(originalQuery || intent.query, loaded, intent.scope, previousRecords);
          if (interpretation.kind === 'contact') {
            previousRecords = [];
            response.replaceChildren();
            appendParagraph(response, 'Para saber qué examen necesitas o consultar resultados, comunícate con el laboratorio o con tu médico.');
            contactLink(response);
            return;
          }
          if (interpretation.records.length) {
            resolved = { records: interpretation.records, approximate: false, unmatched: [] };
            understoodByAI = true;
          }
        } catch { /* El reconocimiento local sigue disponible si la IA falla. */ }
      }
      response.replaceChildren();
      const records = resolved.records;
      previousRecords = intent.query && !resolved.approximate && records.length <= 20 ? records : [];
      for (const explanation of describeQuery(intent.query)) appendParagraph(response, explanation);
      if (intent.type === 'pcr') {
        appendParagraph(response, 'PCR puede referirse a pruebas distintas. Indica el nombre completo que deseas consultar.');
        if (records.length) appendParagraph(response, 'Estas son las coincidencias publicadas con ese nombre; revisa cuál corresponde a tu consulta:');
      } else if (resolved.approximate) {
        appendParagraph(response, 'Encontré nombres parecidos. Elige el examen que quisiste escribir para consultar su precio:');
        const suggestions = createNode('div', 'mc-chips');
        for (const record of records.slice(0, 8)) suggestions.append(questionButton(record.name, record.name));
        response.append(suggestions);
        contactLink(response);
        return;
      } else if (!records.length) {
        appendParagraph(response, 'No encontré una coincidencia en el catálogo público para esa consulta.');
        appendParagraph(response, 'Prueba con el nombre del examen o consulta con recepción. La ausencia en esta lista no confirma que el laboratorio no lo realice.');
      } else if (!intent.query) {
        appendParagraph(response, intent.scope === 'profiles' ? 'Perfiles disponibles y su precio propio:' :
          intent.scope === 'all' ? 'Exámenes y perfiles disponibles con sus precios:' :
            'Exámenes disponibles y sus precios:');
      } else if (understoodByAI) {
        appendParagraph(response, 'Por el nombre que escribiste, estas son las coincidencias del catálogo. Comprueba cuál corresponde a tu consulta:');
      } else if (records.length === 1) {
        appendParagraph(response, 'Encontré esta coincidencia en el catálogo público:');
      } else {
        appendParagraph(response, 'Encontré ' + records.length + ' coincidencias. Revisa sus nombres o escribe una consulta más específica:');
      }
      if (records.length) {
        renderRecords(response, records);
        if (resolved.unmatched.length) appendParagraph(response, 'Una parte de tu consulta no tuvo coincidencia: ' + resolved.unmatched.join(', ') + '. Puedes escribir ese examen por separado.');
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
      previousRecords = [];
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
    if (!query || busy) return;
    busy = true;
    submit.disabled = true;
    message(query, true);
    try {
      const originalIntent = parseIntent(query);
      const followUp = originalIntent.type === 'catalog' ? resolveFollowUp(query, previousRecords) : { query, clarify: false };
      const intent = parseIntent(followUp.query);
      if (followUp.clarify) {
        const response = message('Indica el nombre completo del examen al que te refieres. Si hay varias opciones, elige una:');
        const choices = createNode('div', 'mc-chips');
        for (const record of previousRecords.slice(0, 8)) choices.append(questionButton(record.name, record.name));
        response.append(choices);
      } else if (intent.type === 'menu') {
        const response = message('Puedo ayudarte con los precios publicados y la información de atención. Elige una opción o escribe el nombre del examen:');
        menu(response);
      } else if (intent.type === 'info') {
        previousRecords = [];
        showInfo(intent.key);
      } else await showCatalog(intent, query);
    } finally {
      busy = false;
      submit.disabled = false;
      scrollConversation();
    }
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
    if (busy) return;
    const value = input.value;
    input.value = '';
    ask(value);
  });
  const greeting = message('Hola. Consulta precios de exámenes y perfiles, horarios, ubicación y servicios. Escribe una pregunta o elige una opción.');
  appendParagraph(greeting, 'Las consultas difíciles pueden usar IA para reconocer el nombre del examen. No compartas datos personales ni resultados médicos.', 'mc-status');
  menu(greeting);
}

if (typeof document !== 'undefined') {
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', mountChat, { once: true });
  else mountChat();
}
