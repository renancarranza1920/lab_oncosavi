@extends('expediente.layout')
@section('title', 'Expediente ' . $cliente->NumeroExp)
@section('content')
    <a class="portal-back" href="{{ route('expediente.index') }}"><x-heroicon-o-arrow-left class="portal-icon" />Buscar otro paciente</a>
    <div class="portal-page-heading"><div><p class="portal-eyebrow">Expediente {{ $cliente->NumeroExp }}</p><h1>{{ $cliente->nombre }} {{ $cliente->apellido }}</h1><p class="portal-muted">Historial de órdenes y resultados de laboratorio.</p></div><span class="portal-badge {{ \App\Support\EstadoVisual::clase($cliente->estado) }}">{{ $cliente->estado }}</span></div>
    <section class="portal-card portal-patient-info" aria-label="Datos del paciente"><h2>Datos del paciente</h2><dl class="portal-info-grid">
        <div><dt>DUI</dt><dd>{{ $cliente->dui ?: 'Sin registrar' }}</dd></div>
        <div><dt>Fecha de nacimiento</dt><dd>{{ $cliente->fecha_nacimiento ? \Carbon\Carbon::parse($cliente->fecha_nacimiento)->format('d/m/Y') : 'Sin registrar' }}</dd></div>
        <div><dt>Edad</dt><dd>{{ $cliente->fecha_nacimiento || $cliente->edad !== null ? $cliente->edad_legible : 'Sin registrar' }}</dd></div>
        <div><dt>Género</dt><dd>{{ $cliente->genero }}</dd></div>
        <div><dt>Teléfono</dt><dd>{{ $cliente->telefono ?: 'Sin registrar' }}</dd></div>
        <div><dt>Correo</dt><dd>{{ $cliente->correo ?: 'Sin registrar' }}</dd></div>
        <div class="portal-info-address"><dt>Dirección</dt><dd>{{ $cliente->direccion ?: 'Sin registrar' }}</dd></div>
    </dl></section>
    <div class="portal-results-heading"><h2>Historial de órdenes</h2><span class="portal-muted">{{ $ordenes->total() }} {{ $ordenes->total() === 1 ? 'orden' : 'órdenes' }}</span></div>
    <div class="portal-order-list">
        @forelse ($ordenes as $orden)
            @php($pdfDisponible = $orden->estado === 'finalizado' && $orden->reporteGuardadoExists())
            <article class="portal-card portal-order-card">
                <div class="portal-order-heading"><div><h3>Orden #{{ $orden->id }}</h3><p class="portal-muted portal-small">{{ $orden->fecha?->format('d/m/Y') }} · Registro: {{ $orden->created_at?->format('H:i') }}@if ($medico->portal_todos_pacientes && $orden->medico) · {{ $orden->medico->nombre }}@endif</p></div><span class="portal-badge {{ \App\Support\EstadoVisual::clase($orden->estado) }}">{{ ucfirst($orden->estado) }}</span></div>
                <div class="portal-order-exams">@forelse ($orden->detalleOrden as $detalle)<span class="portal-exam-chip">{{ $detalle->nombre_examen ?: ($detalle->nombre_perfil ?: 'Examen') }}</span>@empty<span class="portal-muted portal-small">Sin exámenes registrados.</span>@endforelse</div>
                <div class="portal-order-bottom"><p class="portal-muted portal-small">{{ $pdfDisponible ? 'Resultados finales disponibles' : ($orden->estado === 'finalizado' ? 'El laboratorio aún no ha guardado el PDF final.' : 'Resultados finales pendientes de publicación.') }}</p>
                    @if ($pdfDisponible)<div class="portal-order-actions"><a class="portal-button portal-button-primary" data-portal-pdf="Orden #{{ $orden->id }} · {{ $cliente->NumeroExp }}" href="{{ route('expediente.pdf', $orden->id) }}" target="_blank" rel="noopener"><x-heroicon-o-eye class="portal-icon" />Ver resultados</a><a class="portal-button portal-button-quiet" href="{{ route('expediente.pdf', ['orden' => $orden->id, 'descargar' => 1]) }}"><x-heroicon-o-arrow-down-tray class="portal-icon" />Descargar PDF</a></div>@endif
                </div>
            </article>
        @empty
            <div class="portal-card portal-empty"><x-heroicon-o-document-text class="portal-icon" /><h3>Aún no hay órdenes</h3><p class="portal-muted">Este paciente no tiene órdenes de laboratorio disponibles.</p></div>
        @endforelse
    </div>
    @include('expediente.paginacion', ['registros' => $ordenes])
@endsection
@push('dialogs')
    <dialog id="portal-pdf-viewer" class="portal-pdf-dialog" aria-labelledby="portal-pdf-title">
        <div class="portal-pdf-header"><h2 id="portal-pdf-title">Resultados de laboratorio</h2><div class="portal-order-actions"><a id="portal-pdf-open" class="portal-button portal-button-quiet" target="_blank" rel="noopener">Abrir PDF<x-heroicon-o-arrow-top-right-on-square class="portal-icon" /></a><button class="portal-button portal-button-quiet" id="portal-pdf-close" type="button" aria-label="Cerrar visor"><x-heroicon-o-x-mark class="portal-icon" /></button></div></div>
        <iframe id="portal-pdf-frame" title="Visor de resultados de laboratorio" src="about:blank"></iframe>
        <p class="portal-pdf-help">Si el visor no aparece en tu dispositivo, usa «Abrir PDF».</p>
    </dialog>
@endpush
