@extends('expediente.layout')
@section('title', 'Buscar expediente')
@section('content')
    <div class="portal-page-heading">
        <div><p class="portal-eyebrow">Atención al paciente</p><h1>Expedientes</h1><p class="portal-muted">Encuentra a tu paciente y consulta su historial de laboratorio.</p></div>
        <span class="portal-scope"><x-heroicon-o-eye class="portal-icon" />{{ $medico->portal_todos_pacientes ? 'Expediente general' : 'Pacientes asociados a ti' }}</span>
    </div>
    <section class="portal-card portal-search-card" aria-label="Búsqueda de pacientes">
        <form method="get" action="{{ route('expediente.index') }}">
            <label class="portal-field" for="buscar-paciente">Buscar paciente</label>
            <div class="portal-search-row">
                <div class="portal-search-input"><x-heroicon-o-magnifying-glass class="portal-icon" /><input id="buscar-paciente" name="q" value="{{ $filtros['q'] ?? '' }}" placeholder="Nombre, apellido, expediente, DUI, teléfono, correo o número de orden" maxlength="120"></div>
                <button class="portal-button portal-button-primary" type="submit">Buscar</button>
                <a class="portal-button portal-button-quiet" href="{{ route('expediente.index') }}">Limpiar</a>
            </div>
            <details class="portal-filters" {{ collect($filtros)->except('q')->filter()->isNotEmpty() ? 'open' : '' }}>
                <summary><x-heroicon-o-adjustments-horizontal class="portal-icon" />Filtros por fecha, hora y datos del paciente</summary>
                <div class="portal-filter-grid">
                    <label class="portal-field">Órdenes desde<input type="date" name="desde" value="{{ $filtros['desde'] ?? '' }}"></label>
                    <label class="portal-field">Órdenes hasta<input type="date" name="hasta" value="{{ $filtros['hasta'] ?? '' }}"></label>
                    <label class="portal-field">Hora de registro desde<input type="time" name="hora_desde" value="{{ $filtros['hora_desde'] ?? '' }}"></label>
                    <label class="portal-field">Hora de registro hasta<input type="time" name="hora_hasta" value="{{ $filtros['hora_hasta'] ?? '' }}"></label>
                    <label class="portal-field">Fecha de nacimiento<input type="date" name="fecha_nacimiento" value="{{ $filtros['fecha_nacimiento'] ?? '' }}"></label>
                    <label class="portal-field">Género<select name="genero"><option value="">Todos</option>@foreach (['Femenino', 'Masculino'] as $genero)<option value="{{ $genero }}" @selected(($filtros['genero'] ?? '') === $genero)>{{ $genero }}</option>@endforeach</select></label>
                    <label class="portal-field">Estado del paciente<select name="estado"><option value="">Todos</option>@foreach (['Activo', 'Inactivo'] as $estado)<option value="{{ $estado }}" @selected(($filtros['estado'] ?? '') === $estado)>{{ $estado }}</option>@endforeach</select></label>
                </div>
                <p class="portal-muted portal-small">Las fechas corresponden a la orden; las horas, a su registro en el laboratorio (El Salvador).</p>
            </details>
        </form>
    </section>
    <div class="portal-results-heading"><h2>Pacientes</h2><span class="portal-muted">{{ $pacientes->total() }} {{ $pacientes->total() === 1 ? 'expediente encontrado' : 'expedientes encontrados' }}</span></div>
    <div class="portal-patient-grid">
        @forelse ($pacientes as $paciente)
            <article class="portal-card portal-patient-card">
                <div class="portal-patient-top"><span class="portal-avatar">{{ mb_substr($paciente->nombre, 0, 1) }}{{ mb_substr($paciente->apellido, 0, 1) }}</span><span class="portal-badge {{ \App\Support\EstadoVisual::clase($paciente->estado) }}">{{ $paciente->estado }}</span></div>
                <p class="portal-patient-exp">{{ $paciente->NumeroExp }}</p><h3>{{ $paciente->nombre }} {{ $paciente->apellido }}</h3>
                <p class="portal-muted portal-small">{{ $paciente->genero }}@if ($paciente->fecha_nacimiento || $paciente->edad !== null) · {{ $paciente->edad_legible }}@endif</p>
                <dl class="portal-patient-meta"><div><dt>DUI</dt><dd>{{ $paciente->dui ?: 'Sin registrar' }}</dd></div><div><dt>Teléfono</dt><dd>{{ implode(' · ', $paciente->telefonos_contacto) ?: 'Sin registrar' }}</dd></div><div><dt>Última orden</dt><dd>{{ $paciente->ultima_orden ? \Carbon\Carbon::parse($paciente->ultima_orden)->format('d/m/Y') : 'Sin órdenes' }}</dd></div></dl>
                <div class="portal-patient-bottom"><span class="portal-muted portal-small">{{ $paciente->ordenes_portal_count }} {{ $paciente->ordenes_portal_count === 1 ? 'orden' : 'órdenes' }}</span><a class="portal-button portal-button-quiet" href="{{ route('expediente.show', $paciente->id) }}">Ver expediente<x-heroicon-o-arrow-right class="portal-icon" /></a></div>
            </article>
        @empty
            <div class="portal-card portal-empty"><x-heroicon-o-magnifying-glass class="portal-icon" /><h3>No encontramos pacientes</h3><p class="portal-muted">Prueba con parte del nombre o amplía las fechas de las órdenes.</p></div>
        @endforelse
    </div>
    @include('expediente.paginacion', ['registros' => $pacientes])
@endsection
