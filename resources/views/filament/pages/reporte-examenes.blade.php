<x-filament-panels::page>
    @php($resumen = $this->resumen)

    <style>
        .catalog-dashboard { display: grid; gap: 1.25rem; }
        .catalog-panel { overflow: hidden; border: 1px solid var(--ui-border); border-radius: 1rem; background: var(--ui-surface); box-shadow: 0 1px 3px rgba(9, 11, 59, .06); }
        .catalog-hero { position: relative; overflow: hidden; padding: 1.5rem; color: #fff; background: linear-gradient(120deg, rgb(var(--primary-950)), rgb(var(--primary-800)), rgb(var(--primary-600))); }
        .catalog-hero:before, .catalog-hero:after { position: absolute; border: 1px solid rgb(var(--primary-400) / .3); border-radius: 999px; content: ''; }
        .catalog-hero:before { right: -4rem; top: -7rem; width: 19rem; height: 19rem; }
        .catalog-hero:after { right: 7rem; bottom: -7rem; width: 12rem; height: 12rem; }
        .catalog-eyebrow { position: relative; z-index: 1; color: rgb(var(--primary-200)); font-size: .7rem; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; }
        .catalog-title { position: relative; z-index: 1; max-width: 650px; margin-top: .35rem; font-size: 1.5rem; font-weight: 800; letter-spacing: -.025em; }
        .catalog-description { position: relative; z-index: 1; max-width: 670px; margin-top: .45rem; color: rgb(var(--primary-200)); font-size: .82rem; line-height: 1.55; }
        .catalog-badges { position: relative; z-index: 1; display: flex; flex-wrap: wrap; gap: .5rem; margin-top: 1rem; }
        .catalog-badge { border: 1px solid rgba(255,255,255,.2); border-radius: 999px; padding: .32rem .65rem; color: #fff; background: rgba(255,255,255,.08); font-size: .68rem; font-weight: 700; }
        .catalog-kpis { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 1rem; }
        .catalog-kpi { position: relative; overflow: hidden; min-height: 112px; border: 1px solid var(--ui-border); border-radius: .9rem; padding: 1rem; background: var(--ui-surface); box-shadow: 0 1px 3px rgba(9, 11, 59, .05); }
        .catalog-kpi:before { position: absolute; inset: 0 auto 0 0; width: 4px; content: ''; background: var(--kpi-color); }
        .catalog-kpi-top { display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
        .catalog-kpi-label { color: var(--ui-muted); font-size: .68rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; }
        .catalog-kpi-icon { display: grid; width: 31px; height: 31px; place-items: center; border-radius: .6rem; color: var(--kpi-color); background: var(--kpi-bg); font-size: .78rem; font-weight: 900; }
        .catalog-kpi-value { margin-top: .7rem; color: var(--ui-heading); font-size: 1.55rem; font-weight: 800; letter-spacing: -.03em; }
        .catalog-kpi-foot { margin-top: .12rem; color: var(--ui-muted); font-size: .66rem; }
        .catalog-content { display: grid; grid-template-columns: 1fr; gap: 1.25rem; align-items: start; }
        .catalog-panel-title { display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--ui-border); padding: .95rem 1rem; color: var(--ui-heading); font-size: .9rem; font-weight: 800; }
        .catalog-panel-title span { color: var(--ui-muted); font-size: .68rem; font-weight: 500; }
        .catalog-area-list { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .7rem; padding: 1rem; }
        .catalog-area { display: flex; align-items: center; justify-content: space-between; gap: .8rem; border: 1px solid var(--ui-border); border-radius: .75rem; padding: .72rem .8rem; background: var(--ui-subtle); }
        .catalog-area-name { overflow: hidden; color: var(--ui-text); font-size: .72rem; font-weight: 700; text-overflow: ellipsis; white-space: nowrap; }
        .catalog-area-count { display: grid; min-width: 30px; height: 25px; place-items: center; border-radius: 999px; color: var(--ui-heading); background: var(--ui-soft); font-size: .68rem; font-weight: 900; }
        .catalog-features { display: grid; }
        .catalog-feature { display: grid; grid-template-columns: 32px 1fr; gap: .75rem; border-bottom: 1px solid var(--ui-border); padding: .85rem 1rem; }
        .catalog-feature:last-child { border-bottom: 0; }
        .catalog-feature-icon { display: grid; width: 32px; height: 32px; place-items: center; border-radius: .65rem; color: var(--ui-heading); background: var(--ui-soft); font-size: .8rem; font-weight: 900; }
        .catalog-feature strong { display: block; color: var(--ui-text); font-size: .75rem; }
        .catalog-feature span { display: block; margin-top: .15rem; color: var(--ui-muted); font-size: .67rem; line-height: 1.4; }
        .catalog-footer-note { margin: 0 1rem 1rem; border-left: 3px solid rgb(var(--primary-400)); border-radius: .3rem; padding: .65rem .75rem; color: var(--ui-muted); background: var(--ui-subtle); font-size: .7rem; line-height: 1.5; }
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
            </div>
        </section>

        <section class="catalog-kpis">
            @foreach ([
                ['Áreas activas', $resumen['total_areas'], 'heroicon-o-squares-2x2', 'Con exámenes disponibles'],
                ['Exámenes', $resumen['total_examenes'], 'heroicon-o-beaker', 'Incluidos en el catálogo'],
                ['Perfiles', $resumen['perfiles'], 'heroicon-o-rectangle-stack', 'Paquetes activos'],
                ['Exámenes externos', $resumen['externos'], 'heroicon-o-arrow-top-right-on-square', 'Procesamiento externo'],
            ] as [$titulo, $valor, $icono, $pie])
                <article class="catalog-kpi" style="--kpi-color: var(--ui-heading); --kpi-bg: var(--ui-soft);">
                    <div class="catalog-kpi-top"><span class="catalog-kpi-label">{{ $titulo }}</span><span class="catalog-kpi-icon"><x-filament::icon :icon="$icono" class="h-5 w-5" /></span></div>
                    <div class="catalog-kpi-value">{{ $valor }}</div>
                    <div class="catalog-kpi-foot">{{ $pie }}</div>
                </article>
            @endforeach

            <article class="catalog-kpi" style="--kpi-color: var(--ui-heading); --kpi-bg: var(--ui-soft);">
                <div class="catalog-kpi-top"><span class="catalog-kpi-label">Precio promedio</span><span class="catalog-kpi-icon"><x-heroicon-o-currency-dollar class="h-5 w-5" /></span></div>
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

        </section>
    </div>
</x-filament-panels::page>
