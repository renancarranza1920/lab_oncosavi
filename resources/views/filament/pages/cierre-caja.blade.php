<x-filament-panels::page>
    @php($datos = $this->datos)

    <style>
        .cash-dashboard { display: grid; gap: 1.25rem; }
        .cash-panel { overflow: hidden; border: 1px solid var(--ui-border); border-radius: 1rem; background: var(--ui-surface); box-shadow: 0 1px 3px rgba(9, 11, 59, .06); }
        .cash-hero { position: relative; overflow: hidden; padding: 1.35rem 1.5rem; color: #fff; background: linear-gradient(120deg, rgb(var(--primary-950)), rgb(var(--primary-800)), rgb(var(--primary-600))); }
        .cash-hero:after { position: absolute; right: -3rem; top: -5rem; width: 15rem; height: 15rem; border: 1px solid rgb(var(--primary-400) / .35); border-radius: 999px; content: ''; }
        .cash-eyebrow { position: relative; z-index: 1; color: rgb(var(--primary-200)); font-size: .7rem; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; }
        .cash-hero-title { position: relative; z-index: 1; margin-top: .35rem; font-size: 1.45rem; font-weight: 800; letter-spacing: -.02em; }
        .cash-hero-subtitle { position: relative; z-index: 1; margin-top: .3rem; color: rgb(var(--primary-200)); font-size: .82rem; }
        .cash-filters { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 1rem; padding: 1.15rem 1.25rem; }
        .cash-field-label { display: block; margin-bottom: .35rem; color: var(--ui-text); font-size: .76rem; font-weight: 700; }
        .cash-range { display: flex; min-height: 42px; align-items: center; gap: .7rem; border: 1px solid var(--ui-border); border-radius: .65rem; padding: .55rem .75rem; background: var(--ui-subtle); }
        .cash-range-icon { display: grid; width: 28px; height: 28px; flex: 0 0 28px; place-items: center; border-radius: .55rem; color: var(--ui-heading); background: var(--ui-soft); font-size: .8rem; font-weight: 800; }
        .cash-range strong { display: block; color: var(--ui-heading); font-size: .78rem; }
        .cash-range span { display: block; margin-top: .1rem; color: var(--ui-muted); font-size: .68rem; }
        .cash-note { margin: 0 1.25rem 1.15rem; border-left: 3px solid rgb(var(--primary-600)); border-radius: .25rem; padding: .55rem .75rem; color: var(--ui-muted); background: var(--ui-subtle); font-size: .7rem; }
        .cash-kpis { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 1rem; }
        .cash-kpi { position: relative; overflow: hidden; min-height: 116px; border: 1px solid var(--ui-border); border-radius: .9rem; padding: 1rem; background: var(--ui-surface); box-shadow: 0 1px 3px rgba(9, 11, 59, .05); }
        .cash-kpi:before { position: absolute; inset: 0 auto 0 0; width: 4px; content: ''; background: var(--kpi-color); }
        .cash-kpi-top { display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
        .cash-kpi-label { color: var(--ui-muted); font-size: .7rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
        .cash-kpi-icon { display: grid; width: 30px; height: 30px; place-items: center; border-radius: .6rem; color: var(--kpi-color); background: var(--kpi-bg); font-size: .85rem; font-weight: 900; }
        .cash-kpi-value { margin-top: .75rem; color: var(--ui-heading); font-size: 1.55rem; font-weight: 800; letter-spacing: -.03em; }
        .cash-kpi-foot { margin-top: .15rem; color: var(--ui-muted); font-size: .67rem; }
        .cash-content-grid { display: grid; grid-template-columns: minmax(250px, .8fr) minmax(0, 2fr); gap: 1.25rem; align-items: start; }
        .cash-panel-title { display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--ui-border); padding: .9rem 1rem; color: var(--ui-heading); font-size: .88rem; font-weight: 800; }
        .cash-panel-title span { color: var(--ui-muted); font-size: .68rem; font-weight: 500; }
        .cash-status-list { display: grid; }
        .cash-status { display: grid; grid-template-columns: 1fr auto auto; align-items: center; gap: .8rem; border-bottom: 1px solid var(--ui-border); padding: .72rem 1rem; }
        .cash-status:last-child { border-bottom: 0; }
        .cash-status-name { color: var(--ui-text); font-size: .78rem; }
        .cash-status-count { display: grid; min-width: 28px; height: 24px; place-items: center; border-radius: 999px; color: var(--ui-heading); background: var(--ui-soft); font-size: .72rem; font-weight: 800; }
        .cash-status-total { min-width: 70px; color: var(--ui-text); font-size: .72rem; font-weight: 700; text-align: right; }
        .cash-table-wrap { overflow-x: auto; }
        .cash-table { width: 100%; border-collapse: collapse; font-size: .75rem; }
        .cash-table th { padding: .68rem .8rem; color: var(--ui-muted); background: var(--ui-subtle); font-size: .65rem; font-weight: 800; letter-spacing: .04em; text-align: left; text-transform: uppercase; white-space: nowrap; }
        .cash-table td { border-top: 1px solid var(--ui-border); padding: .7rem .8rem; color: var(--ui-text); white-space: nowrap; }
        .cash-table tbody tr:hover { background: var(--ui-subtle); }
        .cash-table .right { text-align: right; }
        .cash-table .net { color: rgb(var(--success-700)); font-weight: 800; }
        .cash-empty { padding: 2.5rem 1rem !important; color: var(--ui-muted) !important; text-align: center; }
        .cash-state { display: inline-block; border-radius: 999px; padding: .18rem .5rem; font-size: .62rem; font-weight: 800; text-transform: uppercase; }
        @media (max-width: 1180px) { .cash-kpis { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        @media (max-width: 900px) { .cash-filters { grid-template-columns: repeat(2, minmax(0, 1fr)); } .cash-content-grid { grid-template-columns: 1fr; } }
        @media (max-width: 680px) { .cash-filters, .cash-kpis { grid-template-columns: 1fr; } .cash-hero-title { font-size: 1.2rem; } }

        .dark .cash-table .net { color: rgb(var(--success-300)); }
    </style>

    <div class="cash-dashboard">
        <section class="cash-panel">
            <div class="cash-hero">
                <div class="cash-eyebrow">Cierre de caja</div>
                <div class="cash-hero-title">Reporte Financiero - {{ $datos['etiqueta'] }}</div>
                <div class="cash-hero-subtitle">Consulta y descarga los ingresos registrados del período seleccionado.</div>
            </div>

            <div class="cash-filters">
                <label>
                    <span class="cash-field-label">Tipo de período</span>
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="periodo">
                            <option value="mensual">Mensual</option>
                            <option value="trimestral">Trimestral</option>
                            <option value="anual">Anual</option>
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </label>

                @if ($periodo === 'mensual')
                    <label>
                        <span class="cash-field-label">Mes</span>
                        <x-filament::input.wrapper>
                            <x-filament::input.select wire:model.live="mes">
                                @foreach ([1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'] as $numero => $nombre)
                                    <option value="{{ $numero }}">{{ $nombre }}</option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </label>
                @elseif ($periodo === 'trimestral')
                    <label>
                        <span class="cash-field-label">Trimestre</span>
                        <x-filament::input.wrapper>
                            <x-filament::input.select wire:model.live="trimestre">
                                <option value="1">Enero - Marzo</option>
                                <option value="2">Abril - Junio</option>
                                <option value="3">Julio - Septiembre</option>
                                <option value="4">Octubre - Diciembre</option>
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </label>
                @else
                    <div></div>
                @endif

                <label>
                    <span class="cash-field-label">Año</span>
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="anio">
                            @foreach (array_reverse($this->anios) as $opcion)
                                <option value="{{ $opcion }}">{{ $opcion }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </label>
                
            </div>

            <div class="cash-note">Los ingresos incluyen órdenes no canceladas. Las cancelaciones se muestran por separado porque el sistema todavía no registra el estado ni el método de pago.</div>
        </section>

        <section class="cash-kpis">
            @foreach ([
                ['Ingreso bruto', $datos['resumen']['ingreso_bruto'], 'heroicon-o-banknotes', 'Antes de descuentos'],
                ['Descuentos', $datos['resumen']['descuentos'], 'heroicon-o-receipt-percent', 'Aplicados en el período'],
                ['Ingreso neto', $datos['resumen']['ingreso_neto'], 'heroicon-o-currency-dollar', 'Órdenes no canceladas'],
                ['Ticket promedio', $datos['resumen']['ticket_promedio'], 'heroicon-o-chart-bar', 'Promedio por orden'],
            ] as [$titulo, $valor, $icono, $pie])
                <article class="cash-kpi" style="--kpi-color: var(--ui-heading); --kpi-bg: var(--ui-soft);">
                    <div class="cash-kpi-top"><span class="cash-kpi-label">{{ $titulo }}</span><span class="cash-kpi-icon"><x-filament::icon :icon="$icono" class="h-5 w-5" /></span></div>
                    <div class="cash-kpi-value">${{ number_format($valor, 2) }}</div>
                    <div class="cash-kpi-foot">{{ $pie }}</div>
                </article>
            @endforeach

            <article class="cash-kpi" style="--kpi-color: var(--ui-heading); --kpi-bg: var(--ui-soft);">
                <div class="cash-kpi-top"><span class="cash-kpi-label">Órdenes</span><span class="cash-kpi-icon"><x-heroicon-o-clipboard-document-list class="h-5 w-5" /></span></div>
                <div class="cash-kpi-value">{{ $datos['resumen']['ordenes'] }}</div>
                <div class="cash-kpi-foot">{{ $datos['resumen']['ordenes_canceladas'] }} canceladas</div>
            </article>
        </section>

        <section class="cash-content-grid">
            <div class="cash-panel">
                <div class="cash-panel-title">Órdenes por estado <span>{{ $datos['resumen']['ordenes'] }} en total</span></div>
                <div class="cash-status-list">
                    @foreach ($datos['estados'] as $estado)
                        <div class="cash-status">
                            <span class="cash-status-name">{{ $estado['etiqueta'] }}</span>
                            <span class="cash-status-count">{{ $estado['cantidad'] }}</span>
                            <span class="cash-status-total">${{ number_format($estado['total'], 2) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="cash-panel">
                <div class="cash-panel-title">Movimiento diario <span>{{ $datos['movimientos']->count() }} días con actividad</span></div>
                <div class="cash-table-wrap">
                    <table class="cash-table">
                        <thead><tr><th>Fecha</th><th class="right">Órdenes</th><th class="right">Bruto</th><th class="right">Descuentos</th><th class="right">Neto</th></tr></thead>
                        <tbody>
                            @forelse ($datos['movimientos'] as $movimiento)
                                <tr><td>{{ $movimiento['fecha'] }}</td><td class="right">{{ $movimiento['ordenes'] }}</td><td class="right">${{ number_format($movimiento['bruto'], 2) }}</td><td class="right">${{ number_format($movimiento['descuentos'], 2) }}</td><td class="right net">${{ number_format($movimiento['neto'], 2) }}</td></tr>
                            @empty
                                <tr><td colspan="5" class="cash-empty">No hay órdenes en este período.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="cash-panel">
            <div class="cash-panel-title">Detalle de órdenes <span>{{ $datos['ordenes']->count() }} registros</span></div>
            <div class="cash-table-wrap">
                <table class="cash-table">
                    <thead><tr><th>Orden</th><th>Fecha</th><th>Cliente</th><th>Estado</th><th class="right">Bruto</th><th class="right">Descuento</th><th class="right">Neto</th></tr></thead>
                    <tbody>
                        @forelse ($datos['ordenes'] as $orden)
                            <tr>
                                <td>#{{ $orden['id'] }}</td><td>{{ $orden['fecha'] }}</td><td>{{ $orden['cliente'] }}</td>
                                <td><span class="cash-state {{ \App\Support\EstadoVisual::clase($orden['estado']) }}">{{ $orden['estado'] }}</span></td>
                                <td class="right">${{ number_format($orden['bruto'], 2) }}</td><td class="right">${{ number_format($orden['descuento'], 2) }}</td><td class="right net">${{ number_format($orden['neto'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="cash-empty">No hay órdenes registradas en este período.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-filament-panels::page>
