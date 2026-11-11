<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title>Historial · Control de Calidad Interno · Oncosavi</title>
    <style>
        *{box-sizing:border-box}body{margin:0;background:#f6f8fb;color:#172435;font:16px/1.5 system-ui,sans-serif}header{background:#fff;border-bottom:1px solid #dce2ea;padding:18px 24px}.brand{max-width:1200px;margin:auto;display:flex;align-items:center;gap:14px}.brand img{width:54px;height:54px;object-fit:contain}.brand strong{display:block;letter-spacing:1px}.brand small{color:#526377}main{max-width:1248px;margin:auto;padding:30px 24px}h1{font-size:28px;margin:0}h2{font-size:18px;margin:0}.intro{color:#526377;margin:8px 0 24px}.panel{background:#fff;border:1px solid #dce2ea;border-radius:14px;padding:22px;margin-bottom:22px}form{display:grid;grid-template-columns:1fr 1fr 2fr auto;gap:16px;align-items:end}label{font-size:14px;font-weight:600;display:grid;gap:7px}input,button,.clear{font:inherit;border-radius:8px;min-height:44px}input{width:100%;border:1px solid #bdc9d7;padding:9px 11px;background:white;color:#172435}button{border:0;background:#23647b;color:white;font-weight:600;padding:10px 20px;cursor:pointer}a{color:#23647b}.clear{display:inline-flex;align-items:center;margin-top:12px}.count{color:#526377;font-size:14px;margin:0 0 14px}.record-head{display:flex;justify-content:space-between;gap:16px;margin-bottom:18px}.record-head p{margin:3px 0 0;color:#526377}.badge{font-size:13px;background:#ecf6f8;color:#23647b;border-radius:6px;padding:4px 9px;white-space:nowrap}.table-wrap{overflow:auto}table{border-collapse:collapse;width:100%;font-size:14px;text-align:left}th{background:#f1f5f8;color:#526377;font-weight:600}td,th{padding:11px 12px;border-bottom:1px solid #e8edf3;vertical-align:top}.outside{color:#b91c1c;font-weight:600}.inside{color:#146353}.note{background:#f6f8fb;border-radius:8px;padding:12px;font-size:14px;white-space:pre-wrap}.pagination{display:flex;justify-content:space-between;gap:20px;margin:22px 0}.errors{color:#b91c1c}footer{color:#526377;font-size:13px;padding-bottom:24px}input:focus,button:focus-visible,a:focus-visible{outline:3px solid #75b0c5;outline-offset:2px}@media(max-width:800px){form{grid-template-columns:1fr 1fr}main{padding:22px 14px}.panel{padding:16px}.search{grid-column:1/-1}.record-head{flex-wrap:wrap}}@media(max-width:480px){form{grid-template-columns:1fr}.search{grid-column:auto}h1{font-size:24px}}
    </style>
</head>
<body>
<header><div class="brand"><img src="{{ asset(config('laboratorio.logo')) }}" alt="Oncosavi"><div><strong>ONCOSAVI</strong><small>Control de Calidad Interno</small></div></div></header>
<main>
    <h1>Historial de Control de Calidad Interno</h1>
    <p class="intro">Consulta para supervisión. Selecciona las fechas o busca un área, equipo, prueba, nivel o lote.</p>
    <section class="panel" aria-label="Buscar en el historial">
        <form method="get" action="{{ url()->current() }}">
            <label>Desde<input type="date" name="desde" value="{{ $filtros['desde'] ?? '' }}"></label>
            <label>Hasta<input type="date" name="hasta" value="{{ $filtros['hasta'] ?? '' }}"></label>
            <label class="search">Buscar<input type="search" name="q" maxlength="180" value="{{ $filtros['q'] ?? '' }}" placeholder="Área, equipo, prueba, nivel o lote"></label>
            <button type="submit">Buscar</button>
        </form>
        <a class="clear" href="{{ url()->current() }}">Limpiar filtros</a>
        @if($errors->any())<div class="errors" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    </section>
    @if($registros)
        <p class="count">{{ $registros->total() }} registros encontrados. Fecha y hora de El Salvador.</p>
        @forelse($registros as $registro)
            <article class="panel">
                <div class="record-head"><div><h2>{{ $registro->area->nombre }} · {{ $registro->area->equipo }}</h2><p>Registro #{{ $registro->id }} · {{ $registro->fecha_hora->format('d/m/Y H:i') }}</p></div><span class="badge">Solo lectura</span></div>
                <div class="table-wrap"><table><thead><tr><th>Prueba</th><th>Nivel</th><th>Resultado</th><th>Unidad</th><th>Rango de control</th><th>Lote</th><th>Evaluación</th></tr></thead><tbody>
                    @foreach($registro->resultados as $resultado)
                        @php($fuera = bccomp($resultado->valor, $resultado->minimo, 6) < 0 || bccomp($resultado->valor, $resultado->maximo, 6) > 0)
                        @php($numero = fn($v) => str_replace('.', ',', rtrim(rtrim((string) $v, '0'), '.')))
                        <tr><td>{{ $resultado->prueba_nombre }}</td><td>{{ $resultado->nivel_nombre }}</td><td>{{ $resultado->valor_original }}</td><td>{{ $resultado->unidad }}</td><td>{{ $numero($resultado->minimo) }} — {{ $numero($resultado->maximo) }}<br><small>Esperado: {{ $numero($resultado->esperado) }}</small></td><td>{{ $resultado->lote_numero }}</td><td class="{{ $fuera ? 'outside' : 'inside' }}">{{ $fuera ? '✕ Fuera de rango' : 'Dentro de rango' }}</td></tr>
                    @endforeach
                </tbody></table></div>
                @if(filled($registro->observaciones))<p class="note">{{ $registro->observaciones }}</p>@endif
            </article>
        @empty
            <section class="panel"><h2>No hay registros para esta búsqueda</h2><p>Prueba con otras fechas o cambia el texto de búsqueda.</p></section>
        @endforelse
        <nav class="pagination" aria-label="Páginas del historial">
            <span>@if($registros->previousPageUrl())<a href="{{ $registros->previousPageUrl() }}" rel="prev">← Anterior</a>@endif</span>
            <span>Página {{ $registros->currentPage() }} de {{ $registros->lastPage() }}</span>
            <span>@if($registros->nextPageUrl())<a href="{{ $registros->nextPageUrl() }}" rel="next">Siguiente →</a>@endif</span>
        </nav>
    @endif
    <footer>ONCOSAVI · Consulta del historial de Control de Calidad Interno.</footer>
</main>
</body>
</html>
