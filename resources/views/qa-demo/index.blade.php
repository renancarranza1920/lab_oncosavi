@extends('qa-demo.layout')
@section('title','Resumen')
@section('content')
<div class="page-heading"><div><p class="eyebrow">Control de Calidad Interno</p><h1>Resumen</h1><p class="muted">Últimos controles registrados y evolución de sus lecturas.</p></div>@if($canRegister ?? false)<a class="button" href="{{ route('qa-demo.runs.create') }}">Registrar control</a>@endif</div>
<div class="stats">
    <div class="stat"><strong>{{ $overview['equipment_count'] ?? count($equipment ?? []) }}</strong><span>Áreas y equipos</span><small>Química y hematología</small></div>
    <div class="stat"><strong>{{ $overview['configuration_count'] ?? count($configurations ?? []) }}</strong><span>Configuraciones</span><small>Pruebas, lotes y niveles</small></div>
    <div class="stat"><strong>{{ $overview['run_count'] ?? 0 }}</strong><span>Registros de control</span><small>Lecturas y seguimiento</small></div>
    <div class="stat"><strong>{{ $overview['pending_count'] ?? 0 }}</strong><span>Pendientes de revisión</span><small>Controles por revisar</small></div>
</div>
<section class="section"><div class="section-heading"><div><h2>Últimos registros</h2><p class="muted">Fecha, área, laboratorista y estado del control.</p></div>@if($canHistory ?? false)<a href="{{ route('qa-demo.runs.index') }}">Ver historial completo</a>@endif</div>@include('qa-demo._runs-table',['runs'=>$overview['recent_runs'] ?? []])</section>
@if($canCharts ?? false)
<section class="section"><div class="section-heading"><div><h2>Gráficos</h2>@if(!empty($summaryChart))<p class="muted">{{ data_get($summaryChart,'equipment.area') }} · {{ data_get($summaryChart,'equipment.name') }} · {{ data_get($summaryChart,'analyte.name') }}</p>@else<p class="muted">Selecciona una prueba para ver sus niveles y límites de control.</p>@endif</div><a class="button secondary" href="{{ route('qa-demo.charts') }}">Ver todos los gráficos</a></div>
@if(!empty($summaryChart))@include('qa-demo._chart-cards',['chart'=>$summaryChart])@else<div class="empty">Aún no hay una configuración disponible para graficar.</div>@endif</section>
@endif
@endsection
