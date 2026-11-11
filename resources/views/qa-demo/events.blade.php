@extends('qa-demo.layout')
@section('title','Mantenimiento')
@section('content')
<div class="page-heading"><div><p class="eyebrow">Control de Calidad Interno</p><h1>Mantenimiento</h1></div><a class="button secondary" href="{{ route('qa-demo.configuration.index') }}">Volver a mantenimiento</a></div>
<section class="section"><h2>Eventos del equipo</h2>@include('qa-demo._events-content')</section>
@if(!empty($selectedEvent))<section class="section"><h2>Controles del evento #{{ $selectedEvent['id'] }}</h2>@include('qa-demo._runs-table',['runs'=>$selectedEvent['runs'] ?? []])</section>@endif
@endsection
