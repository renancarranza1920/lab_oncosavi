<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'Órdenes' }} · {{ config('laboratorio.nombre') }}</title>
    @vite('resources/css/app.css')
    @livewireStyles
    @include('partials.tema-oncosavi')
</head>
<body>
    {{ $slot }}
    @livewireScripts
    @vite('resources/js/app.js')
</body>
</html>
