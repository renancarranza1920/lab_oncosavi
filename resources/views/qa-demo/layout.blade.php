<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title>@yield('title', 'Control de Calidad Interno') · Oncosavi</title>
    <link rel="icon" href="{{ asset('images/oncosavi.png') }}">
    <link rel="stylesheet" href="{{ asset('qa-demo/qa.css') }}?v=3">
    <script src="{{ asset('qa-demo/qa.js') }}?v=3" defer></script>
</head>
<body>
<a class="skip" href="#contenido">Ir al contenido</a>
<div class="open-banner"><strong>DATOS DE DEMOSTRACIÓN</strong></div>
<header class="header"><div class="header-inner">
    <a class="brand" href="{{ route('qa-demo.index') }}"><img src="{{ asset('images/oncosavi.png') }}" width="54" height="54" alt="Logo Oncosavi"><span><b>ONCOSAVI</b><small>SAN VICENTE</small></span></a>
    <div class="header-label"><strong>Control de Calidad Interno</strong><span>{{ $currentOperator ?? auth()->user()?->name }}</span> · <a href="/admin">Volver a Oncosavi</a></div>
</div></header>
@php
    $links = [
        ['qa-demo.index', 'Resumen', request()->routeIs('qa-demo.index'), true],
        ['qa-demo.configuration.index', 'Mantenimiento', request()->routeIs('qa-demo.configuration.*', 'qa-demo.equipment.*', 'qa-demo.products.*', 'qa-demo.lots.*', 'qa-demo.tests.*', 'qa-demo.events.*'), $canMaintain ?? false],
        ['qa-demo.runs.create', 'Registro', request()->routeIs('qa-demo.runs.create', 'qa-demo.runs.edit'), $canRegister ?? false],
        ['qa-demo.charts', 'Gráficos', request()->routeIs('qa-demo.charts'), $canCharts ?? false],
        ['qa-demo.runs.index', 'Historial', request()->routeIs('qa-demo.runs.index', 'qa-demo.runs.show'), $canHistory ?? false],
    ];
@endphp
<nav class="nav hide-print" aria-label="Control de Calidad Interno">
    @foreach($links as [$route, $label, $active, $visible])@if($visible)<a href="{{ route($route) }}" @if($active) aria-current="page" @endif>{{ $label }}</a>@endif @endforeach
</nav>
<main id="contenido">
    @if(session('success'))<div class="notice success" role="status">{{ session('success') }}</div>@endif
    @if(session('status'))<div class="notice success" role="status">{{ session('status') }}</div>@endif
    @if(session('error'))<div class="notice error" role="alert">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="notice error" role="alert" tabindex="-1"><strong>Revisa los datos antes de guardar.</strong><ul>@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>@endif
    @yield('content')
</main>
<footer class="footer">Control de Calidad Interno · Oncosavi</footer>
</body>
</html>
