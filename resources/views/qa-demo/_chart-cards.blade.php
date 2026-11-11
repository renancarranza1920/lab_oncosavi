@php $chart=$chart ?? null; @endphp
@if($chart)
<div class="chart-grid">
@foreach($chart['levels'] ?? [] as $key=>$level)
<article class="chart-card"><h2>{{ $level['label'] ?? $key }}</h2><h3>Esperado {{ $level['target'] ?? '—' }} {{ $level['unit'] ?? '' }}</h3><p class="hint">Límites {{ $level['min'] ?? '—' }} - {{ $level['max'] ?? '—' }} · {{ $level['lot'] ?? '' }}</p>
@include('qa-demo._chart-svg',['level'=>$level,'pdfMode'=>false])
<div class="point-details" data-point-details aria-live="polite">Toca un punto para consultar la lectura.</div></article>
@endforeach
</div>
<div class="chart-legend"><span><i class="legend-line" aria-hidden="true"></i>Valores medidos</span><span><i class="legend-band" aria-hidden="true"></i>Mínimo y máximo</span><span><i class="legend-target" aria-hidden="true"></i>Valor esperado</span><span>× Fuera de límites</span><span>◆ Repetición / corrección</span></div>
@endif
