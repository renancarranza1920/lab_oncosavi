<x-filament-panels::page>
    @php($resumen = $this->resumen)

    <style>
        .catalog-dashboard { display: grid; gap: 1.25rem; }
        .catalog-panel { overflow: hidden; border: 1px solid #dce5e9; border-radius: 1rem; background: #fff; box-shadow: 0 1px 3px rgba(9, 11, 59, .06); }
        .catalog-hero { position: relative; overflow: hidden; padding: 1.5rem; color: #fff; background: linear-gradient(120deg, #090b3b 0%, #183c64 68%, #327c98 100%); }
        .catalog-hero:before, .catalog-hero:after { position: absolute; border: 1px solid rgba(100, 171, 198, .3); border-radius: 999px; content: ''; }
        .catalog-hero:before { right: -4rem; top: -7rem; width: 19rem; height: 19rem; }
        .catalog-hero:after { right: 7rem; bottom: -7rem; width: 12rem; height: 12rem; }
        .catalog-eyebrow { position: relative; z-index: 1; color: #98d9ed; font-size: .7rem; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; }
        .catalog-title { position: relative; z-index: 1; max-width: 650px; margin-top: .35rem; font-size: 1.5rem; font-weight: 800; letter-spacing: -.025em; }
        .catalog-description { position: relative; z-index: 1; max-width: 670px; margin-top: .45rem; color: #d9edf4; font-size: .82rem; line-height: 1.55; }
        .catalog-badges { position: relative; z-index: 1; display: flex; flex-wrap: wrap; gap: .5rem; margin-top: 1rem; }
        .catalog-badge { border: 1px solid rgba(255,255,255,.2); border-radius: 999px; padding: .32rem .65rem; color: #fff; background: rgba(255,255,255,.08); font-size: .68rem; font-weight: 700; }
        .catalog-kpis { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 1rem; }
        .catalog-kpi { position: relative; overflow: hidden; min-height: 112px; border: 1px solid #dce5e9; border-radius: .9rem; padding: 1rem; background: #fff; box-shadow: 0 1px 3px rgba(9, 11, 59, .05); }
        .catalog-kpi:before { position: absolute; inset: 0 auto 0 0; width: 4px; content: ''; background: var(--kpi-color); }
        .catalog-kpi-top { display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
        .catalog-kpi-label { color: #607784; font-size: .68rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; }
        .catalog-kpi-icon { display: grid; width: 31px; height: 31px; place-items: center; border-radius: .6rem; color: var(--kpi-color); background: var(--kpi-bg); font-size: .78rem; font-weight: 900; }
        .catalog-kpi-value { margin-top: .7rem; color: #090b3b; font-size: 1.55rem; font-weight: 800; letter-spacing: -.03em; }
        .catalog-kpi-foot { margin-top: .12rem; color: #78909c; font-size: .66rem; }
        .catalog-content { display: grid; grid-template-columns: minmax(0, 1.35fr) minmax(300px, .65fr); gap: 1.25rem; align-items: start; }
        .catalog-panel-title { display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e5ecef; padding: .95rem 1rem; color: #090b3b; font-size: .9rem; font-weight: 800; }
        .catalog-panel-title span { color: #78909c; font-size: .68rem; font-weight: 500; }
        .catalog-area-list { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .7rem; padding: 1rem; }
        .catalog-area { display: flex; align-items: center; justify-content: space-between; gap: .8rem; border: 1px solid #e6edf0; border-radius: .75rem; padding: .72rem .8rem; background: #fbfcfd; }
        .catalog-area-name { overflow: hidden; color: #344b59; font-size: .72rem; font-weight: 700; text-overflow: ellipsis; white-space: nowrap; }
        .catalog-area-count { display: grid; min-width: 30px; height: 25px; place-items: center; border-radius: 999px; color: #090b3b; background: #dff0f6; font-size: .68rem; font-weight: 900; }
        .catalog-features { display: grid; }
        .catalog-feature { display: grid; grid-template-columns: 32px 1fr; gap: .75rem; border-bottom: 1px solid #edf1f3; padding: .85rem 1rem; }
        .catalog-feature:last-child { border-bottom: 0; }
        .catalog-feature-icon { display: grid; width: 32px; height: 32px; place-items: center; border-radius: .65rem; color: #17637f; background: #e5f4f8; font-size: .8rem; font-weight: 900; }
        .catalog-feature strong { display: block; color: #182a37; font-size: .75rem; }
        .catalog-feature span { display: block; margin-top: .15rem; color: #71848f; font-size: .67rem; line-height: 1.4; }
        .catalog-footer-note { margin: 0 1rem 1rem; border-left: 3px solid #e32737; border-radius: .3rem; padding: .65rem .75rem; color: #526976; background: #fafbfc; font-size: .7rem; line-height: 1.5; }
        .dark .catalog-panel, .dark .catalog-kpi { border-color: #374151; background: #111827; }
        .dark .catalog-kpi-label, .dark .catalog-area-name, .dark .catalog-feature span, .dark .catalog-footer-note { color: #cbd5e1; }
        .dark .catalog-kpi-value, .dark .catalog-panel-title, .dark .catalog-feature strong { color: #f8fafc; }
        .dark .catalog-area, .dark .catalog-footer-note { border-color: #374151; background: #1f2937; }
        .dark .catalog-panel-title, .dark .catalog-feature { border-color: #263244; }
        @media (max-width: 1180px) { .catalog-kpis { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        @media (max-width: 900px) { .catalog-content { grid-template-columns: 1fr; } }
        @media (max-width: 680px) { .catalog-kpis, .catalog-area-list { grid-template-columns: 1fr; } .catalog-title { font-size: 1.25rem; } }
    </style>

    <div class="catalog-dashboard">
        <section class="catalog-panel">
            <div class="catalog-hero">
                <div class="catalog-eyebrow">Catálogo institucional</div>
                <div class="catalog-title">Reporte de Exámenes y Perfiles</div>
                <div class="catalog-description">Genera la solicitud impresa del laboratorio con precios actualizados, espacios para datos del paciente y selección manual de servicios.</div>
                <div class="catalog-badges">
                    <span class="catalog-badge">Formato A4</span>
                    <span class="catalog-badge">Máximo 2 páginas</span>
                    <span class="catalog-badge">Precios en USD</span>
                    <span class="catalog-badge">Solo servicios activos</span>
                </div>
            </div>
        </section>

        <section class="catalog-kpis">
            @foreach ([
                ['Áreas activas', $resumen['total_areas'], 'A', '#2563eb', '#eaf1ff', 'Con exámenes disponibles'],
                ['Exámenes', $resumen['total_examenes'], 'E', '#15835b', '#e8f8f0', 'Incluidos en el catálogo'],
                ['Perfiles', $resumen['perfiles'], 'P', '#7c3aed', '#f2ebff', 'Paquetes activos'],
                ['Exámenes externos', $resumen['externos'], 'X', '#c47b00', '#fff5df', 'Procesamiento externo'],
            ] as [$titulo, $valor, $icono, $color, $fondo, $pie])
                <article class="catalog-kpi" style="--kpi-color: {{ $color }}; --kpi-bg: {{ $fondo }};">
                    <div class="catalog-kpi-top"><span class="catalog-kpi-label">{{ $titulo }}</span><span class="catalog-kpi-icon">{{ $icono }}</span></div>
                    <div class="catalog-kpi-value">{{ $valor }}</div>
                    <div class="catalog-kpi-foot">{{ $pie }}</div>
                </article>
            @endforeach

            <article class="catalog-kpi" style="--kpi-color: #e32737; --kpi-bg: #fdecee;">
                <div class="catalog-kpi-top"><span class="catalog-kpi-label">Precio promedio</span><span class="catalog-kpi-icon">$</span></div>
                <div class="catalog-kpi-value">${{ number_format($resumen['precio_promedio'], 2) }}</div>
                <div class="catalog-kpi-foot">Promedio por examen activo</div>
            </article>
        </section>

        <section class="catalog-content">
            <div class="catalog-panel">
                <div class="catalog-panel-title">Distribución por áreas <span>{{ $resumen['total_examenes'] }} exámenes activos</span></div>
                <div class="catalog-area-list">
                    @forelse ($resumen['areas'] as $area)
                        <div class="catalog-area"><span class="catalog-area-name">{{ $area->nombre }}</span><span class="catalog-area-count">{{ $area->examenes_count }}</span></div>
                    @empty
                        <div class="catalog-footer-note">No hay áreas con exámenes activos.</div>
                    @endforelse
                </div>
            </div>

            <div class="catalog-panel">
                <div class="catalog-panel-title">Contenido del PDF <span>Listo para imprimir</span></div>
                <div class="catalog-features">
                    <div class="catalog-feature"><div class="catalog-feature-icon">01</div><div><strong>Identificación del paciente</strong><span>Campos para paciente, médico y edad.</span></div></div>
                    <div class="catalog-feature"><div class="catalog-feature-icon">02</div><div><strong>Selección manual</strong><span>Círculos para marcar exámenes y perfiles solicitados.</span></div></div>
                    <div class="catalog-feature"><div class="catalog-feature-icon">03</div><div><strong>Información actualizada</strong><span>Áreas, nombres y precios tomados del catálogo activo.</span></div></div>
                    <div class="catalog-feature"><div class="catalog-feature-icon">04</div><div><strong>Datos de contacto</strong><span>Teléfono, WhatsApp y correo institucional.</span></div></div>
                </div>
                <div class="catalog-footer-note">Usa el botón <strong>Descargar PDF</strong> de la parte superior para generar la versión más reciente del catálogo.</div>
            </div>
        </section>
    </div>
</x-filament-panels::page>
