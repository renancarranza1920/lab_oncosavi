const root = document.getElementById('chatbot');
if (root) {
    const messages = document.getElementById('messages');
    const welcome = document.getElementById('welcome');
    const form = document.getElementById('question-form');
    const input = document.getElementById('question');
    const send = document.getElementById('send-question');
    const conversation = document.getElementById('conversation');
    let busy = false;
    let poll;
    let context = null;

    function el(tag, text, className) {
        const node = document.createElement(tag);
        if (text !== undefined) node.textContent = String(text);
        if (className) node.className = className;
        return node;
    }
    function scroll() { conversation.scrollTop = conversation.scrollHeight; }
    function status(ready) {
        document.getElementById('model-status').textContent = ready ? 'IA local lista · datos consultados al momento' : 'Preparando IA local · las consultas rápidas ya están disponibles';
        document.getElementById('status-dot').classList.toggle('ready', ready);
    }
    async function checkStatus() {
        try {
            const r = await fetch(root.dataset.status, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            if (r.status === 401 || r.status === 403 || r.status === 429) { clearInterval(poll); status(false); return; }
            if (!r.ok) throw new Error();
            const data = await r.json();
            status(data.listo);
            if (data.listo) clearInterval(poll);
        } catch { status(false); }
    }
    function download(result) {
        const keys = Object.keys(result.columnas);
        const cell = value => {
            let text = String(value ?? '');
            if (/^[=+\-@\t\r]/.test(text)) text = "'" + text;
            return '"' + text.replaceAll('"', '""') + '"';
        };
        const csv = '\uFEFF' + [Object.values(result.columnas), ...result.filas.map(row => keys.map(key => row[key]))].map(row => row.map(cell).join(',')).join('\r\n');
        const url = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }));
        const a = el('a'); a.href = url; a.download = `informe-${result.periodo.desde}-${result.periodo.hasta}.csv`; a.click();
        setTimeout(() => URL.revokeObjectURL(url), 1000);
    }
    function render(result, mode) {
        const card = el('article', undefined, 'answer-card');
        const header = el('div', undefined, 'answer-header');
        const heading = el('div');
        heading.append(el('span', mode === 'ia_local' ? 'CONSULTA CON IA LOCAL' : 'CONSULTA GUIADA', 'section-kicker'), el('h3', result.titulo));
        const exportButton = el('button', 'Descargar CSV', 'export-button');
        exportButton.type = 'button'; exportButton.addEventListener('click', () => download(result));
        header.append(heading, exportButton); card.append(header);
        card.append(el('p', `${result.periodo.desde} → ${result.periodo.hasta}${result.periodo.estado !== 'todos' ? ' · ' + result.periodo.estado : ''}`, 'answer-period'));
        card.append(el('p', result.respuesta, 'answer-summary'));
        if (!result.filas.length) {
            card.append(el('p', 'No hay registros para este periodo y filtro.', 'empty-result'));
        } else {
            const wrapper = el('div', undefined, 'result-table-wrap');
            const table = el('table', undefined, 'result-table');
            const caption = el('caption', result.titulo, 'sr-only');
            const head = el('thead'); const hr = el('tr');
            Object.values(result.columnas).forEach(label => { const th = el('th', label); th.scope = 'col'; hr.append(th); }); head.append(hr);
            const body = el('tbody');
            for (const row of result.filas) {
                const tr = el('tr'); Object.keys(result.columnas).forEach(key => tr.append(el('td', row[key] ?? ''))); body.append(tr);
            }
            table.append(caption, head, body); wrapper.append(table); card.append(wrapper);
        }
        card.append(el('p', result.nota, 'answer-note'));
        const time = new Date(result.consultado_en).toLocaleString('es-SV', { timeZone: 'America/El_Salvador', dateStyle: 'short', timeStyle: 'short' });
        card.append(el('div', `${result.fuente} · Consultado ${time}`, 'answer-source'));
        const followups = el('div', undefined, 'answer-followups');
        for (const label of (result.informe === 'clientes_nuevos' ? ['¿Y hoy?', '¿Y ayer?'] : ['¿Y hoy?', '¿Y ayer?', 'Solo las pendientes'])) {
            const button = el('button', label, 'export-button'); button.type = 'button';
            button.addEventListener('click', () => ask(label)); followups.append(button);
        }
        card.append(followups); messages.append(card);
    }
    async function ask(question, report = null) {
        if (busy || !question.trim()) return;
        const from = document.getElementById('period-from'); const to = document.getElementById('period-to');
        if (!from.reportValidity() || !to.reportValidity()) return;
        if (from.value > to.value) { to.setCustomValidity('La fecha final debe ser posterior o igual a la inicial.'); to.reportValidity(); to.setCustomValidity(''); return; }
        busy = true; send.disabled = true;
        document.querySelectorAll('.quick-card').forEach(b => b.disabled = true);
        welcome.hidden = true;
        const bubble = el('div', undefined, 'question-bubble'); bubble.append(el('span', 'TÚ', 'section-kicker'), el('p', question)); messages.append(bubble);
        const loading = el('div', 'Consultando tu laboratorio…', 'loading-message'); loading.setAttribute('role', 'status'); messages.append(loading);
        input.value = ''; scroll();
        try {
            const response = await fetch(root.dataset.endpoint, {
                method: 'POST', credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                body: JSON.stringify({ pregunta: question, desde: from.value, hasta: to.value, consulta_rapida: report, contexto: context }),
            });
            const data = await response.json();
            if (!response.ok) {
                const firstError = data.errors ? Object.values(data.errors).flat()[0] : null;
                throw new Error(response.status === 419 || response.status === 401 ? 'Tu sesión venció. Vuelve a iniciar sesión en el sistema.' : response.status === 429 ? 'El asistente está ocupado o recibiste el límite de consultas. Espera un momento.' : firstError || data.message || 'No fue posible consultar el informe.');
            }
            loading.remove(); render(data.resultado, data.modo);
            context = { informe: data.resultado.informe, ...data.resultado.periodo, limite: data.resultado.limite };
            from.value = data.resultado.periodo.desde; to.value = data.resultado.periodo.hasta;
            if (data.modo === 'ia_local') status(true);
        } catch (error) {
            loading.remove(); const node = el('div', error.message || 'No se pudo conectar. Intenta de nuevo.', 'error-message'); node.setAttribute('role', 'alert'); messages.append(node);
        } finally {
            busy = false; send.disabled = false; document.querySelectorAll('.quick-card').forEach(b => b.disabled = false); scroll(); input.focus();
        }
    }
    for (const id of ['period-from', 'period-to']) {
        document.getElementById(id).addEventListener('change', () => {
            if (context) { context.desde = document.getElementById('period-from').value; context.hasta = document.getElementById('period-to').value; }
        });
    }
    form.addEventListener('submit', e => { e.preventDefault(); ask(input.value); });
    input.addEventListener('keydown', e => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); form.requestSubmit(); } });
    document.querySelectorAll('.quick-card').forEach(button => button.addEventListener('click', () => ask(button.dataset.question, button.dataset.report)));
    document.querySelectorAll('[data-clear-chat]').forEach(button => button.addEventListener('click', () => { if (busy) return; messages.replaceChildren(); welcome.hidden = false; input.value = ''; context = null; input.focus(); }));
    poll = setInterval(checkStatus, 30000); checkStatus();
    document.addEventListener('visibilitychange', () => { if (!document.hidden) checkStatus(); });
}
