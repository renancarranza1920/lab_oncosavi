<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Expedientes') · {{ config('laboratorio.nombre') }}</title>
    <script>
        try {
            const tema = localStorage.getItem('theme');
            document.documentElement.classList.toggle('dark', tema === 'dark' || (tema !== 'light' && matchMedia('(prefers-color-scheme: dark)').matches));
        } catch (_) {}
    </script>
    <style>
        :root {
            @foreach (config('ui.primary') as $tono => $color)
                --primary-{{ $tono }}: {{ $color }};
            @endforeach
            @foreach (\Filament\Support\Colors\Color::Gray as $tono => $color)
                --gray-{{ $tono }}: {{ $color }};
            @endforeach
            @foreach (['success', 'warning', 'danger', 'info'] as $tipo)
                @foreach (\Filament\Support\Colors\Color::hex(config('estados.' . $tipo . '.base')) as $tono => $color)
                    --{{ $tipo }}-{{ $tono }}: {{ $color }};
                @endforeach
            @endforeach
        }
    </style>
    @include('partials.tema-oncosavi')
    @vite(['resources/css/app.css', 'resources/css/expediente.css', 'resources/js/expediente.js'])
</head>
<body class="portal-body @yield('body-class')">
    <header class="portal-header">
        <a href="{{ route('expediente.index') }}" class="portal-brand" aria-label="Inicio del portal">@include('components.logo')</a>
        <div class="portal-header-actions">
            <button type="button" class="portal-button portal-button-quiet" id="portal-theme" aria-label="Cambiar tema">
                <x-heroicon-o-sun class="portal-icon portal-sun" /><x-heroicon-o-moon class="portal-icon portal-moon" />
                <span class="portal-theme-label">Cambiar tema</span>
            </button>
            @isset($medico)
                <span class="portal-doctor"><x-heroicon-o-user-circle class="portal-icon" />{{ $medico->nombre }}</span>
                <form method="post" action="{{ route('expediente.logout') }}">@csrf
                    <button class="portal-button portal-button-quiet" type="submit"><x-heroicon-o-arrow-right-on-rectangle class="portal-icon" /><span>Salir</span></button>
                </form>
            @endisset
        </div>
    </header>
    <main class="portal-main">
        @if ($errors->any())
            <div class="portal-alert" role="alert"><x-heroicon-o-exclamation-circle class="portal-icon" /><div>
                @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div></div>
        @endif
        @yield('content')
    </main>
    <footer class="portal-footer">{{ config('laboratorio.nombre') }} · {{ config('laboratorio.sede') }} · Portal de expedientes para médicos</footer>
    @stack('dialogs')
</body>
</html>
