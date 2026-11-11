<!doctype html><html lang="es"><head><meta charset="utf-8"><style>
@page{margin:28px 30px 35px}body{font:10px/1.4 'DejaVu Sans';color:#172435}h1{font-size:20px;margin:0;color:#184b64}h2{font-size:13px;margin:18px 0 5px}.brand{border-bottom:3px solid #23647b;padding-bottom:12px;margin-bottom:16px}.brand strong{font-size:13px}.meta{color:#526377;font-size:9px;margin:5px 0}.data-table{width:100%;border-collapse:collapse;table-layout:fixed;margin-bottom:10px}th{text-align:left;background:#eaf1f5;color:#234a5e}th,td{border:1px solid #dbe3ea;padding:7px;vertical-align:top;overflow-wrap:break-word}tr{page-break-inside:avoid}thead{display:table-header-group}.note{background:#f4f7f9;padding:8px;white-space:pre-wrap}.chart{page-break-before:always;page-break-inside:avoid}.chart img{width:100%;height:auto}.footer{position:fixed;bottom:-20px;font-size:8px;color:#526377}
</style></head><body>
<div class="footer">ONCOSAVI · Control de Calidad Interno · {{ $generado }} · Hora de El Salvador</div>
<div class="brand"><strong>ONCOSAVI · CONTROL DE CALIDAD INTERNO</strong><h1>{{ $titulo }}</h1><p class="meta">Generado: {{ $generado }} · Usuario: {{ $usuario }}</p>
<p class="meta">Desde: {{ $filtros['desde'] ?? 'Sin límite' }} · Hasta: {{ $filtros['hasta'] ?? 'Sin límite' }} @if(filled($filtros['q'] ?? null)) · Búsqueda: {{ $filtros['q'] }} @endif</p></div>
@forelse($tablas as $tabla)
    <h2>{{ $tabla['titulo'] }}</h2>@if(isset($tabla['detalle']))<p class="meta">{{ $tabla['detalle'] }}</p>@endif
    <table class="data-table"><thead><tr>@foreach($tabla['columnas'] as $columna)<th>{{ $columna }}</th>@endforeach</tr></thead><tbody>
    @forelse($tabla['filas'] as $fila)<tr>@foreach($fila as $valor)<td>{!! nl2br(e($valor)) !!}</td>@endforeach</tr>
    @empty<tr><td colspan="{{ count($tabla['columnas']) }}">No hay datos para esta consulta.</td></tr>@endforelse
    </tbody></table>
    @if(filled($tabla['nota'] ?? null))<p class="note">{{ $tabla['nota'] }}</p>@endif
@empty<p>No hay registros para las fechas o filtros seleccionados.</p>@endforelse
@foreach($graficos as $grafico)<section class="chart"><h1>{{ $grafico['titulo'] }}</h1><p>Unidad: {{ $grafico['unidad'] }}</p><p class="meta">La franja sombreada indica el rango de control. La X roja identifica mediciones fuera de rango.</p><img src="{{ $grafico['src'] }}" alt="Gráfico de mediciones"></section>@endforeach
</body></html>
