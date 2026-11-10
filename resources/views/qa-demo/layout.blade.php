<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title>@yield('title', 'Control de calidad') · Oncosavi · Prueba</title>
    <link rel="icon" href="{{ asset('images/oncosavi.png') }}">
    <link rel="stylesheet" href="{{ asset('qa-demo/qa.css') }}?v=1">
    <script src="{{ asset('qa-demo/qa.js') }}?v=1" defer></script>
</head>
<body>
<a class="skip" href="#contenido">Ir al contenido</a>
<div class="open-banner"><strong>PRUEBA ABIERTA · DATOS DE DEMOSTRACIÓN</strong> · Sin inicio de sesión. Los nombres de quien registra y revisa se escriben manualmente.</div>
<header class="header">
    <div class="header-inner">
        <a class="brand" href="{{ route('qa-demo.index') }}"><img src="{{ asset('images/oncosavi.png') }}" width="54" height="54" alt="Logo Oncosavi"><span><b>ONCOSAVI</b><small>SAN VICENTE</small></span></a>
        <div class="header-label"><strong>Control de calidad de química</strong>Espacio independiente para revisar la funcionalidad</div>
    </div>
</header>
@php
    $links = [
        ['qa-demo.index', 'Resumen', request()->routeIs('qa-demo.index')],
        ['qa-demo.configuration.index', 'Configuración', request()->routeIs('qa-demo.configuration.*', 'qa-demo.equipment.*')],
        ['qa-demo.runs.create', 'Registrar mediciones', request()->routeIs('qa-demo.runs.create', 'qa-demo.runs.edit')],
        ['qa-demo.charts', 'Gráficas', request()->routeIs('qa-demo.charts')],
        ['qa-demo.runs.index', 'Historial', request()->routeIs('qa-demo.runs.index', 'qa-demo.runs.show')],
        ['qa-demo.events.index', 'Eventos del equipo', request()->routeIs('qa-demo.events.*')],
    ];
@endphp
<nav class="nav hide-print" aria-label="Control de calidad">
    @foreach($links as [$route, $label, $active])<a href="{{ route($route) }}" @if($active) aria-current="page" @endif>{{ $label }}</a>@endforeach
</nav>
<main id="contenido">
    @if(session('success'))<div class="notice success" role="status">{{ session('success') }}</div>@endif
    @if(session('status'))<div class="notice success" role="status">{{ session('status') }}</div>@endif
    @if(session('error'))<div class="notice error" role="alert">{{ session('error') }}</div>@endif
    @if($errors->any())
        <div class="notice error" role="alert" tabindex="-1"><strong>Revisa los datos antes de guardar.</strong><ul>@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>
    @endif
    @yield('content')
</main>
<footer class="footer">Este espacio guarda únicamente registros de prueba de control de calidad. Las mediciones, gráficas y revisiones de esta prueba no autorizan resultados clínicos ni ejecutan la calibración de una máquina.</footer>
</body>
</html>
