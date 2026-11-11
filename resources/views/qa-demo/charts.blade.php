@extends('qa-demo.layout')
@section('title','Gráficos')
@section('content')
@php
 $filters=$filters ?? []; $chart=$chart ?? null;
 $chosenId=$filters['configuration_id'] ?? data_get($chart,'configuration.id');
 $configurationForSelector=$chart['configuration'] ?? $configuration ?? null;
 $analyteList=$configurationForSelector['analytes'] ?? [];
 $chosenCatalogId=$filters['catalog_id'] ?? data_get($chart,'analyte.catalog_id');
 $exportFilters=array_filter(['configuration_id'=>$chosenId,'catalog_id'=>$chosenCatalogId,'from'=>$filters['from'] ?? null,'to'=>$filters['to'] ?? null],fn($value)=>$value!==null&&$value!=='');
 $allPointRows=[];
 foreach(($chart['levels'] ?? []) as $key=>$level) foreach($level['points'] ?? [] as $point) $allPointRows[]=array_merge($point,['level_label'=>$level['label'] ?? $key,'unit'=>$level['unit'] ?? '','lot'=>$level['lot'] ?? '','target'=>$level['target'] ?? null,'min'=>$level['min'] ?? null,'max'=>$level['max'] ?? null]);
 usort($allPointRows,fn($a,$b)=>strcmp($b['measured_at'],$a['measured_at']));
@endphp
<div class="page-heading"><div><p class="eyebrow">Control de Calidad Interno</p><h1>Gráficos</h1><p class="muted">Consulta la evolución de cada nivel por prueba, área y configuración.</p></div>@if($canExport ?? false)<button type="button" class="secondary hide-print" data-print>Imprimir gráficos</button>@endif</div>
<section class="section hide-print"><form method="get" action="{{ route('qa-demo.charts') }}" class="filters" data-testid="chart-filters">
<div><label for="chart-configuration">Área y configuración</label><select id="chart-configuration" name="configuration_id"><option value="">Seleccionar configuración</option>@foreach($configurations ?? [] as $cfg)<option value="{{ $cfg['id'] }}" @selected((string)$chosenId===(string)$cfg['id'])>{{ $cfg['area'] ?? '' }} · {{ $cfg['equipment_name'] }} · {{ $cfg['label'] }} · v{{ $cfg['version'] }}</option>@endforeach</select></div>
<div><label for="chart-analyte">Prueba</label><select id="chart-analyte" name="catalog_id"><option value="">Primera prueba de la configuración</option>@foreach($analyteList as $analyte)<option value="{{ $analyte['catalog_id'] }}" @selected((string)$chosenCatalogId===(string)$analyte['catalog_id'])>{{ $analyte['name'] }}</option>@endforeach</select><p class="hint">Al cambiar la configuración, aplica el filtro para cargar sus pruebas.</p></div>
<div><label for="chart-from">Desde</label><input id="chart-from" type="date" name="from" value="{{ $filters['from'] ?? '' }}"></div><div><label for="chart-to">Hasta</label><input id="chart-to" type="date" name="to" value="{{ $filters['to'] ?? '' }}"></div><div class="filter-submit"><button type="submit">Ver gráficos</button></div>
</form></section>
@if($chart)
@if(!empty($chart['truncated']))<div class="notice warning">Se muestran las últimas 500 mediciones de este filtro. Reduce el periodo para consultar las demás lecturas.</div>@endif
<section class="section"><div class="section-heading"><div><h2>{{ data_get($chart,'analyte.name','Prueba') }}</h2><p class="muted">{{ data_get($chart,'equipment.area') }} · {{ data_get($chart,'equipment.name',data_get($chart,'configuration.equipment.name','Equipo')) }} · {{ data_get($chart,'configuration.label','Controles') }} · versión {{ data_get($chart,'configuration.version',1) }}</p></div>@if($canExport ?? false)<div class="actions hide-print"><a class="button secondary small" href="{{ route('qa-demo.export.csv',$exportFilters) }}">Exportar CSV</a><a class="button secondary small" href="{{ route('qa-demo.export.pdf',$exportFilters) }}">Descargar PDF</a></div>@endif</div>
@include('qa-demo._chart-cards',['chart'=>$chart])
<p class="hint">Cada nivel conserva los límites de la configuración utilizada. Se muestran las lecturas originales y las repeticiones.</p></section>
<section class="section"><h2>Lecturas de estos gráficos</h2>@if(count($allPointRows))<div class="table-wrap table-mobile-cards"><table><thead><tr><th>Fecha</th><th>Nivel / lote</th><th>Medido</th><th>Límites</th><th>Estado</th><th>Registro</th></tr></thead><tbody>@foreach($allPointRows as $point)<tr><td data-label="Fecha">{{ \Carbon\Carbon::parse($point['measured_at'])->format('d/m/Y H:i') }}</td><td data-label="Nivel / lote">{{ $point['level_label'] }}<div class="hint">{{ $point['lot'] }}</div></td><td data-label="Medido" class="numeric"><strong>{{ $point['value'] }}</strong> {{ $point['unit'] }}</td><td data-label="Límites">{{ $point['min'] }} - {{ $point['max'] }} {{ $point['unit'] }}</td><td data-label="Estado">@include('qa-demo._status',['value'=>$point['status'] ?? 'within'])</td><td data-label="Registro">@if($canViewRecord ?? $canHistory ?? false)<a href="{{ route('qa-demo.runs.show',$point['run_id']) }}">{{ $point['number'] ?? 'Ver registro' }}</a>@else{{ $point['number'] ?? '' }}@endif</td></tr>@endforeach</tbody></table></div>@else<div class="empty">No hay lecturas registradas para este periodo y configuración.</div>@endif</section>
@else<div class="empty"><h2>No hay una configuración disponible</h2><p>Los gráficos aparecerán cuando se configuren pruebas y niveles.</p>@if($canMaintain ?? false)<a class="button" href="{{ route('qa-demo.configuration.index') }}">Ir a mantenimiento</a>@endif</div>@endif
@endsection
