@extends('qa-demo.layout')
@section('title','Gráficas de control')
@section('content')
@php
 $filters=$filters ?? []; $chart=$chart ?? null;
 $chosenId=$filters['configuration_id'] ?? data_get($chart,'configuration.id');
 $configurationForSelector=($chart['configuration'] ?? null);
 if(!$configurationForSelector && isset($qa) && $chosenId) $configurationForSelector=$qa->configuration((int)$chosenId);
 $analyteList=$configurationForSelector['analytes'] ?? [];
 $chosenCatalogId=$filters['catalog_id'] ?? data_get($chart,'analyte.catalog_id');
 $exportFilters=array_filter(['configuration_id'=>$chosenId,'catalog_id'=>$chosenCatalogId,'from'=>$filters['from'] ?? null,'to'=>$filters['to'] ?? null],fn($value)=>$value!==null&&$value!=='');
 $allPointRows=[];
 foreach(($chart['levels'] ?? []) as $key=>$level) foreach($level['points'] ?? [] as $point) $allPointRows[]=array_merge($point,['level_label'=>$level['label'] ?? $key,'unit'=>$level['unit'] ?? '','lot'=>$level['lot'] ?? '','target'=>$level['target'] ?? null,'min'=>$level['min'] ?? null,'max'=>$level['max'] ?? null]);
 usort($allPointRows,fn($a,$b)=>strcmp($b['measured_at'],$a['measured_at']));
@endphp
<div class="page-heading"><div><p class="eyebrow">Tres niveles de la misma prueba</p><h1>Gráficas de los controles</h1><p class="muted">Elige una configuración y una prueba. Cada gráfica mantiene los límites de esa versión.</p></div><div class="actions hide-print"><button type="button" class="secondary" data-print>Imprimir gráficas</button></div></div>
<section class="section hide-print"><form method="get" action="{{ route('qa-demo.charts') }}" class="filters" data-testid="chart-filters">
<div><label for="chart-configuration">Equipo y configuración</label><select id="chart-configuration" name="configuration_id"><option value="">Seleccionar configuración</option>@foreach($configurations ?? [] as $cfg)<option value="{{ $cfg['id'] }}" @selected((string)$chosenId===(string)$cfg['id'])>{{ $cfg['equipment_name'] ?? '' }} · {{ $cfg['label'] ?? '' }} · v{{ $cfg['version'] ?? 1 }}</option>@endforeach</select></div>
<div><label for="chart-analyte">Prueba</label><select id="chart-analyte" name="catalog_id"><option value="">Primera prueba de la configuración</option>@foreach($analyteList as $analyte)<option value="{{ $analyte['catalog_id'] }}" @selected((string)$chosenCatalogId===(string)$analyte['catalog_id'])>{{ $analyte['name'] }} · {{ $analyte['exam_name'] ?? '' }}</option>@endforeach</select><p class="hint">Al cambiar de configuración, aplica el filtro para cargar sus pruebas.</p></div>
<div><label for="chart-from">Desde</label><input id="chart-from" type="date" name="from" value="{{ $filters['from'] ?? '' }}"></div><div><label for="chart-to">Hasta</label><input id="chart-to" type="date" name="to" value="{{ $filters['to'] ?? '' }}"></div><div class="filter-submit"><button type="submit">Ver gráficas</button></div>
</form></section>
@if($chart)
@if(!empty($chart['truncated']))<div class="notice warning">Se muestran las últimas 500 mediciones de este filtro. Reduce el periodo para consultar todas las lecturas de interés.</div>@endif
<section class="section"><div class="section-heading"><div><h2>{{ data_get($chart,'analyte.name','Prueba') }}</h2><p class="muted">{{ data_get($chart,'equipment.name',data_get($chart,'configuration.equipment.name','Equipo')) }} · {{ data_get($chart,'configuration.label','Controles') }} · versión {{ data_get($chart,'configuration.version',1) }}</p></div><div class="actions hide-print"><a class="button secondary small" href="{{ route('qa-demo.export.csv',$exportFilters) }}">Exportar lecturas CSV</a><a class="button secondary small" href="{{ route('qa-demo.export.pdf',$exportFilters) }}">Descargar PDF</a></div></div>
<div class="chart-grid">
@foreach(['low'=>'Bajo','medium'=>'Medio','high'=>'Alto'] as $key=>$label)
@php $level=$chart['levels'][$key] ?? ['label'=>$label,'points'=>[]]; @endphp
<article class="chart-card"><h2>Nivel {{ mb_strtolower($label) }}</h2><h3>Esperado {{ $level['target'] ?? '—' }} {{ $level['unit'] ?? '' }}</h3><p class="hint">Límites {{ $level['min'] ?? '—' }} - {{ $level['max'] ?? '—' }} · {{ $level['lot'] ?? '' }}</p>
@include('qa-demo._chart-svg',['level'=>$level,'pdfMode'=>false])
<div class="point-details" data-point-details aria-live="polite">Toca un punto o selecciónalo con el teclado para consultar su lectura.</div></article>
@endforeach
</div><div class="chart-legend"><span><i class="legend-line" aria-hidden="true"></i>Valores medidos</span><span><i class="legend-band" aria-hidden="true"></i>Mínimo y máximo</span><span><i class="legend-target" aria-hidden="true"></i>Valor esperado</span><span>× Fuera de límites</span><span>◆ Repetición / corrección</span></div>
<p class="hint">Los puntos se presentan en la secuencia de medición. Se conservan tanto el resultado inicial como las repeticiones.</p></section>
<section class="section"><h2>Lecturas de estas gráficas</h2>@if(count($allPointRows))<div class="table-wrap table-mobile-cards"><table><thead><tr><th>Fecha</th><th>Nivel / lote</th><th>Medido</th><th>Límites</th><th>Estado</th><th>Registro</th></tr></thead><tbody>@foreach($allPointRows as $point)<tr><td data-label="Fecha">{{ \Carbon\Carbon::parse($point['measured_at'])->format('d/m/Y H:i') }}</td><td data-label="Nivel / lote">{{ $point['level_label'] }}<div class="hint">{{ $point['lot'] }}</div></td><td data-label="Medido" class="numeric"><strong>{{ $point['value'] }}</strong> {{ $point['unit'] }}</td><td data-label="Límites">{{ $point['min'] }} - {{ $point['max'] }} {{ $point['unit'] }}</td><td data-label="Estado">@include('qa-demo._status',['value'=>$point['status'] ?? 'within'])</td><td><a href="{{ route('qa-demo.runs.show',$point['run_id']) }}">{{ $point['number'] ?? 'Ver registro' }}</a></td></tr>@endforeach</tbody></table></div>@else<div class="empty">No hay lecturas registradas para este periodo y configuración.</div>@endif</section>
@else<div class="empty"><h2>Selecciona controles para ver sus gráficas</h2><p>Prepara una configuración y registra lecturas. Podrás consultar bajo, medio y alto de cada prueba.</p><a class="button" href="{{ route('qa-demo.configuration.index') }}">Abrir configuración</a></div>@endif
@endsection
