<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Asistente del laboratorio · {{ config('laboratorio.nombre') }}</title>
    @vite(['resources/css/chatbot.css', 'resources/js/chatbot.js'])
</head>
<body>
    <div class="assistant-shell" id="chatbot" data-endpoint="{{ route('chatbot.preguntar') }}" data-status="{{ route('chatbot.estado') }}">
        <aside class="assistant-sidebar">
            <a href="{{ url('/admin') }}" class="brand"><img src="{{ asset(config('laboratorio.logo')) }}" alt="ONCOSAVI"><span>ONCOSAVI<small>Laboratorio clínico</small></span></a>
            <div class="workspace-label">ESPACIO DEL ADMINISTRADOR</div>
            <div class="selected-nav"><span aria-hidden="true">◈</span> Asistente del laboratorio</div>
            <button id="clear-chat" data-clear-chat class="new-chat" type="button"><span aria-hidden="true">＋</span> Nueva consulta</button>
            <div class="sidebar-about"><div class="small-icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 18 18 6M6 6h12v12"/></svg></div><strong>Tu laboratorio, en perspectiva</strong><p>Consulta la actividad, revisa tendencias y encuentra respuestas en tus registros.</p></div>
            <a href="{{ url('/admin') }}" class="back-link">← Volver al sistema</a>
            <div class="sidebar-user"><span class="avatar">{{ mb_substr(auth()->user()->name, 0, 1) }}</span><div><strong>{{ auth()->user()->name }}</strong><small>Administrador</small></div></div>
        </aside>
        <main class="assistant-main">
            <header class="assistant-topbar"><div><span class="section-kicker">ASISTENTE</span><h1>Una mirada clara a tu laboratorio.</h1></div><span class="local-badge"><span aria-hidden="true"></span> IA en tu servidor</span></header>
            <nav class="mobile-navigation" aria-label="Navegación del asistente"><a href="{{ url('/admin') }}">← Volver al sistema</a><button type="button" data-clear-chat>Nueva consulta ＋</button></nav>
            <section class="period-toolbar" aria-label="Periodo de consulta"><div><strong>Periodo de consulta</strong><small>Puedes pedir otro periodo en tu pregunta.</small></div><div class="date-fields"><label>Desde<input id="period-from" type="date" value="{{ $desde }}" required></label><span aria-hidden="true">→</span><label>Hasta<input id="period-to" type="date" value="{{ $hasta }}" required></label></div></section>
            <div class="conversation" id="conversation">
                <section class="welcome" id="welcome"><div class="welcome-mark" aria-hidden="true">✦</div><span class="section-kicker">DATOS REALES, RESPUESTAS CLARAS</span><h2>¿Qué quieres saber hoy?</h2><p>Pregúntame por las órdenes, los importes registrados o los exámenes más solicitados. Cada respuesta incluye su periodo y fuente.</p>
                    <div class="quick-grid">
                        @foreach ([['resumen', 'Resumen del laboratorio', 'Lo esencial de este periodo', '◈'], ['ordenes_por_estado', '¿Cómo van las órdenes?', 'Pendientes, en proceso y finalizadas', '≡'], ['ingresos_por_dia', 'Revisar importes por día', 'Órdenes vigentes, sin cancelaciones', '$'], ['examenes_populares', 'Exámenes más solicitados', 'Un vistazo a las solicitudes', '↗'], ['ordenes_recientes', 'Ver las últimas órdenes', 'Folios, fechas, estados e importes', '⌁'], ['clientes_nuevos', 'Clientes nuevos', 'Altas de clientes por día', '+']] as [$informe, $pregunta, $descripcion, $icono])
                            <button class="quick-card" type="button" data-report="{{ $informe }}" data-question="{{ $pregunta }}"><span class="quick-icon" aria-hidden="true">@if($informe === 'examenes_populares')<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="m3 16 6-6 4 4 8-10M14 4h7v7"/></svg>@else{{ $icono }}@endif</span><span><strong>{{ $pregunta }}</strong><small>{{ $descripcion }}</small></span><span class="quick-arrow" aria-hidden="true"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 18 18 6M6 6h12v12"/></svg></span></button>
                        @endforeach
                    </div>
                </section>
                <div id="messages" aria-live="polite" aria-relevant="additions"></div>
            </div>
            <footer class="composer-area"><div class="assistant-status"><span class="status-dot" id="status-dot"></span><span id="model-status">Comprobando IA local…</span></div>
                <form id="question-form" class="composer"><label class="sr-only" for="question">Tu pregunta al asistente</label><textarea id="question" placeholder="Por ejemplo: ¿cuántas órdenes pendientes tenemos hoy?" rows="2" maxlength="1000" required></textarea><button type="submit" id="send-question" aria-label="Enviar pregunta"><span aria-hidden="true">↑</span></button></form>
                <p class="composer-hint">Informes administrativos de solo consulta. Los importes de órdenes no equivalen a pagos comprobados.</p>
            </footer>
        </main>
    </div>
    <noscript>Activa JavaScript para usar el chat. Puedes volver al panel para consultar tus registros.</noscript>
</body>
</html>
