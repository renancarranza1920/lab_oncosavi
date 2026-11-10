@extends('qa-demo.layout')
@section('title','Resumen de controles')
@section('content')
<div class="page-heading"><div><p class="eyebrow">Seguimiento de los controles</p><h1>Control de calidad de química</h1><p class="muted">El personal mide en la máquina, registra las lecturas y el responsable revisa lo ocurrido.</p></div><div class="actions"><a class="button" href="{{ route('qa-demo.runs.create') }}">Registrar mediciones</a></div></div>
<div class="stats">
    <div class="stat"><strong>{{ $overview['equipment_count'] ?? count($equipment ?? []) }}</strong><span>Equipos configurados</span><small>Identificados dentro de esta prueba</small></div>
    <div class="stat"><strong>{{ $overview['configuration_count'] ?? count($configurations ?? []) }}</strong><span>Configuraciones de control</span><small>Lotes, niveles y valores esperados</small></div>
    <div class="stat"><strong>{{ $overview['run_count'] ?? 0 }}</strong><span>Registros de medición</span><small>El historial conserva las repeticiones</small></div>
    <div class="stat"><strong>{{ $overview['pending_count'] ?? 0 }}</strong><span>Pendientes de revisión</span><small>La revisión se realiza por separado</small></div>
</div>
<section class="section"><div class="section-heading"><div><h2>Rutina de lunes, miércoles y viernes</h2><p class="muted">Estos son los días de control. La calibración se anota cuando efectivamente se realiza.</p></div><span class="badge">Próximo: {{ isset($overview['next_scheduled_date']) ? \Carbon\Carbon::parse($overview['next_scheduled_date'])->format('d/m/Y') : 'Según programación' }}</span></div>
<div class="calendar"><div class="day"><strong>Lunes</strong><span>Medir, registrar y revisar los controles.</span></div><div class="day"><strong>Miércoles</strong><span>Agregar nuevas lecturas al historial.</span></div><div class="day"><strong>Viernes</strong><span>Revisar la evolución de cada nivel.</span></div></div>
<p class="hint">También puedes registrar controles adicionales. Para una medición que no se realizó, deja el valor vacío e indica el motivo; no lo sustituyas por cero.</p></section>
<section class="section"><div class="section-heading"><div><h2>Últimos registros</h2><p class="muted">Se muestran por fecha de medición, con su comparación y revisión.</p></div><a href="{{ route('qa-demo.runs.index') }}">Consultar todo el historial</a></div>@include('qa-demo._runs-table',['runs'=>$overview['recent_runs'] ?? []])</section>
<div class="grid two"><section class="section"><h2>Primero, preparar los controles</h2><p>Selecciona las pruebas que ya existen, identifica el equipo y carga los datos de la hoja de cada lote.</p><a class="button secondary" href="{{ route('qa-demo.configuration.index') }}">Abrir configuración</a></section><section class="section"><h2>Después, ver las tres gráficas</h2><p>Elige una prueba para consultar por separado su nivel bajo, medio y alto, con los límites de esa configuración.</p><a class="button secondary" href="{{ route('qa-demo.charts') }}">Ver gráficas</a></section></div>
@endsection
