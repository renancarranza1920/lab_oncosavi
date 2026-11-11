<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title>Historial · Control de Calidad Interno · Oncosavi</title>
    <link rel="icon" href="{{ asset(config('laboratorio.logo')) }}">
    <link rel="stylesheet" href="{{ asset('css/cci-supervision.css') }}?v={{ filemtime(public_path('css/cci-supervision.css')) }}">
    <script type="module" src="{{ asset('js/cci-supervision.js') }}?v={{ filemtime(public_path('js/cci-supervision.js')) }}"></script>
</head>
<body>
<header class="topbar"><a class="brand" href="{{ url()->current() }}"><img src="{{ asset(config('laboratorio.logo')) }}" alt="Oncosavi"><span><strong>ONCOSAVI</strong><small>SAN VICENTE</small></span></a><nav aria-label="Consulta de supervisión"><a href="#historial" class="active">@include('control-calidad.icon', ['name' => 'clock']) Historial</a><a href="#graficos">@include('control-calidad.icon', ['name' => 'chart']) Gráficos</a></nav><span class="access-note">Consulta de supervisión</span></header>
<main>
    <section id="historial" aria-labelledby="historial-title">
        <div class="page-heading"><h1 id="historial-title">Historial</h1><button class="primary" type="submit" form="pdf-filtrado">@include('control-calidad.icon', ['name' => 'download']) Exportar PDF</button></div>
        @foreach(['pdf-filtrado', 'pdf-seleccion'] as $formId)
            <form id="{{ $formId }}" method="get" action="{{ $exportUrl }}" target="_blank" rel="noopener">
                @foreach(collect($filtros)->only(['desde', 'hasta', 'q', 'area_id', 'laboratorista_id']) as $key => $value)@if(filled($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif @endforeach
                <input type="hidden" name="incluir_graficos" value="1" data-include-graphs>
            </form>
        @endforeach
        <div class="panel history-panel">
            <div class="panel-title"><h2>Historial de registros</h2></div>
            <form id="filtros" method="get" action="{{ url()->current() }}#historial" class="table-toolbar">
                <div class="selection-tools"><button type="submit" form="pdf-seleccion" id="pdf-selected" class="text-button" disabled>@include('control-calidad.icon', ['name' => 'download']) PDF seleccionados <span id="selection-count" aria-live="polite">0</span></button><label class="check-label"><input type="checkbox" id="include-graphs" checked> Incluir gráficos</label></div>
                <label class="search-box">@include('control-calidad.icon', ['name' => 'search'])<span class="sr-only">Buscar en el historial</span><input type="search" name="q" maxlength="180" value="{{ $filtros['q'] ?? '' }}" placeholder="Buscar" aria-label="Buscar en el historial"></label>
                <button type="submit" class="text-button search-submit">Buscar</button>
                @php($activeFilters = collect($filtros)->only(['desde', 'hasta', 'area_id', 'laboratorista_id'])->filter(fn($v) => filled($v))->count())
                <details class="filter-menu" @if($errors->any()) open @endif><summary aria-label="Filtrar historial">@include('control-calidad.icon', ['name' => 'filter'])<span class="filter-count">{{ $activeFilters }}</span></summary><div class="filter-content">
                    <h3>Filtros</h3>
                    <label>Área<select name="area_id"><option value="">Todas las áreas</option>@foreach($areas as $area)<option value="{{ $area->id }}" @selected(($filtros['area_id'] ?? '') == $area->id)>{{ $area->nombre }} · {{ $area->equipo }}</option>@endforeach</select></label>
                    <label>Laboratorista<select name="laboratorista_id"><option value="">Todos</option>@foreach($laboratoristas as $person)<option value="{{ $person->id }}" @selected(($filtros['laboratorista_id'] ?? '') == $person->id)>{{ $person->name }}</option>@endforeach</select></label>
                    <div class="date-fields"><label>Desde<input type="date" name="desde" value="{{ $filtros['desde'] ?? '' }}"></label><label>Hasta<input type="date" name="hasta" value="{{ $filtros['hasta'] ?? '' }}"></label></div>
                    <div class="filter-actions"><a href="{{ url()->current() }}">Limpiar</a><button type="submit" class="primary">Aplicar filtros</button></div>
                </div></details>
            </form>
            @if($errors->any())<div class="errors" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
            @if($registros)
                <div class="table-wrap"><table class="history-table"><thead><tr><th class="select-cell"><input type="checkbox" id="select-all" aria-label="Seleccionar todos los registros de esta página"></th><th>Registro</th><th>Fecha y hora</th><th>Área</th><th>Equipo</th><th>Laboratorista</th><th>Estado</th><th><span class="sr-only">Opciones de consulta</span></th></tr></thead><tbody>
                    @forelse($registros as $registro)
                        <tr><td class="select-cell"><input type="checkbox" name="ids[]" value="{{ $registro->id }}" form="pdf-seleccion" data-record-select aria-label="Seleccionar registro {{ $registro->id }}"></td><td>#{{ $registro->id }}</td><td class="nowrap">{{ $registro->fecha_hora->format('d/m/Y H:i') }}</td><td>{{ $registro->area->nombre }}</td><td>{{ $registro->area->equipo }}</td><td>{{ $registro->laboratorista?->name ?? '—' }}</td><td><span class="registered">Registrado</span></td><td><div class="row-actions"><button type="button" class="text-button" data-dialog="registro-{{ $registro->id }}">@include('control-calidad.icon', ['name' => 'eye']) Ver registro</button><a href="{{ $exportUrl.'?'.http_build_query(['ids' => [$registro->id], 'incluir_graficos' => 1]) }}" target="_blank" rel="noopener" class="text-button" aria-label="PDF del registro {{ $registro->id }}">@include('control-calidad.icon', ['name' => 'download']) PDF</a></div></td></tr>
                    @empty<tr><td colspan="8" class="empty">No hay registros para esta búsqueda.</td></tr>@endforelse
                </tbody></table></div>
                <nav class="pagination" aria-label="Páginas del historial"><span>{{ $registros->total() }} registros · Hora de El Salvador</span><div>@if($registros->previousPageUrl())<a href="{{ $registros->previousPageUrl() }}#historial" rel="prev">Anterior</a>@endif<span>Página {{ $registros->currentPage() }} de {{ $registros->lastPage() }}</span>@if($registros->nextPageUrl())<a href="{{ $registros->nextPageUrl() }}#historial" rel="next">Siguiente</a>@endif</div></nav>
            @endif
        </div>
    </section>
    <section id="graficos" aria-labelledby="graficos-title">
        <div class="page-heading"><div><h2 class="section-heading" id="graficos-title">Gráficos de control</h2><p class="muted">Mediciones de la consulta filtrada. La franja indica el rango de control y la X roja una medición fuera de rango.</p></div>@if($prueba)<a class="text-button" href="{{ $exportUrl.'?'.http_build_query(collect($filtros)->except(['page', 'prueba_id'])->all() + ['prueba_id' => $prueba->id, 'incluir_graficos' => 1]) }}" target="_blank" rel="noopener">@include('control-calidad.icon', ['name' => 'download']) PDF de la prueba</a>@endif</div>
        <form method="get" action="{{ url()->current() }}#graficos" class="panel chart-filters">
            @foreach(collect($filtros)->only(['desde', 'hasta', 'q', 'laboratorista_id']) as $key => $value)@if(filled($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif @endforeach
            <label>Área y equipo<select name="area_id" id="chart-area"><option value="">Todas las áreas</option>@foreach($areas as $area)<option value="{{ $area->id }}" @selected(($filtros['area_id'] ?? '') == $area->id)>{{ $area->nombre }} · {{ $area->equipo }}</option>@endforeach</select></label>
            <label>Prueba<select name="prueba_id" id="chart-test">@foreach($pruebas as $test)<option value="{{ $test->id }}" @selected($prueba?->id == $test->id)>{{ $test->nombre }} · {{ $test->area->nombre }}</option>@endforeach</select></label>
            <button class="primary" type="submit">Ver gráficos</button>
        </form>
        @if($limiteGraficos)<p class="panel">Selecciona un período de hasta 300 registros para ver los gráficos.</p>
        @elseif(!$graficos)<p class="panel">No hay niveles configurados para esta consulta.</p>
        @else
            <div class="charts-grid" data-chart-module="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('chart', 'filament/widgets') }}">
                @foreach($graficos as $grafico)
                    <article class="panel chart-card"><div class="chart-heading"><div><h3>{{ $grafico['nombre'] }}</h3><p class="muted">{{ $prueba->nombre }}</p></div><button type="button" class="text-button" data-expand-chart="graph-{{ $grafico['id'] }}">@include('control-calidad.icon', ['name' => 'expand']) Ampliar</button></div>
                        @if(!$grafico['data']['labels'])<p class="chart-empty">No hay mediciones para esta prueba con los filtros seleccionados.</p>@endif
                        <div class="chart-canvas" data-chart="graph-{{ $grafico['id'] }}"><canvas aria-label="Gráfico de {{ $grafico['nombre'] }}" role="img"></canvas><span data-chart-color="background" style="color:#eff6ff"></span><span data-chart-color="border" style="color:#2563eb"></span><span data-chart-color="text" style="color:#526377"></span><span data-chart-color="grid" style="color:#e2e8f0"></span></div>
                        <script type="application/json" id="graph-{{ $grafico['id'] }}">{!! json_encode(['cachedData' => $grafico['data'], 'options' => $grafico['options'], 'type' => 'line'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) !!}</script>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
    <footer>ONCOSAVI · Control de Calidad Interno · Consulta de supervisión</footer>
</main>
@if($registros)
    @foreach($registros as $registro)
        <dialog id="registro-{{ $registro->id }}" class="modal record-modal" aria-labelledby="record-title-{{ $registro->id }}"><div class="modal-heading"><div><h2 id="record-title-{{ $registro->id }}">Registro #{{ $registro->id }}</h2><p class="muted">{{ $registro->fecha_hora->format('d/m/Y H:i') }} · {{ $registro->area->nombre }} · {{ $registro->area->equipo }}</p></div><button type="button" class="text-button" data-close-dialog aria-label="Cerrar registro">@include('control-calidad.icon', ['name' => 'close']) Cerrar</button></div><div class="modal-body"><div class="record-meta"><div><span>Laboratorista</span><strong>{{ $registro->laboratorista?->name ?? '—' }}</strong></div><div><span>Fecha y hora</span><strong>{{ $registro->fecha_hora->format('d/m/Y H:i') }}</strong></div><div><span>Estado</span><strong>Registrado</strong></div></div>
            <div class="table-wrap"><table class="results-table"><thead><tr><th>Prueba</th><th>Nivel</th><th>Resultado</th><th>Unidad</th><th>Valores de control</th><th>Lote</th><th>Evaluación</th></tr></thead><tbody>
            @foreach($registro->resultados as $resultado)
                @php($fuera = bccomp($resultado->valor, $resultado->minimo, 6) < 0 || bccomp($resultado->valor, $resultado->maximo, 6) > 0)
                @php($numero = fn($v) => str_replace('.', ',', rtrim(rtrim((string) $v, '0'), '.')))
                <tr><td>{{ $resultado->prueba_nombre }}</td><td>{{ $resultado->nivel_nombre }}</td><td>{{ $resultado->valor_original }}</td><td>{{ $resultado->unidad }}</td><td>{{ $numero($resultado->minimo) }} — {{ $numero($resultado->maximo) }}<br><small>Esperado: {{ $numero($resultado->esperado) }}</small></td><td>{{ $resultado->lote_numero }}</td><td class="{{ $fuera ? 'outside' : 'inside' }}">{{ $fuera ? '✕ Fuera de rango' : 'Dentro de rango' }}</td></tr>
            @endforeach
            </tbody></table></div>@if(filled($registro->observaciones))<p class="note">{{ $registro->observaciones }}</p>@endif
        </div><div class="modal-footer"><a class="text-button" href="{{ url()->current().'?'.http_build_query([...collect($filtros)->except(['page','prueba_id'])->all(), 'area_id' => $registro->area_id]).'#graficos' }}" data-go-graphs>Ver gráficos</a><a class="primary" href="{{ $exportUrl.'?'.http_build_query(['ids' => [$registro->id], 'incluir_graficos' => 1]) }}" target="_blank" rel="noopener">@include('control-calidad.icon', ['name' => 'download']) Guardar PDF</a></div></dialog>
    @endforeach
@endif
<dialog class="modal chart-modal" id="chart-expanded" aria-labelledby="expanded-title"><div class="modal-heading"><h2 id="expanded-title">Gráfico de control</h2><button type="button" class="text-button" data-close-dialog aria-label="Cerrar gráfico">@include('control-calidad.icon', ['name' => 'close']) Cerrar</button></div><div class="expanded-canvas chart-canvas"><canvas aria-label="Gráfico ampliado" role="img"></canvas><span data-chart-color="background" style="color:#eff6ff"></span><span data-chart-color="border" style="color:#2563eb"></span><span data-chart-color="text" style="color:#526377"></span><span data-chart-color="grid" style="color:#e2e8f0"></span></div></dialog>
</body></html>
