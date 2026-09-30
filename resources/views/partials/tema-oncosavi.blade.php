<link rel="icon" type="image/png" href="{{ asset(config('laboratorio.logo')) }}">
<style>
    :root {
        --oncosavi-marino: {{ config('laboratorio.colores.marino') }};
        --oncosavi-celeste: {{ config('laboratorio.colores.celeste') }};
        --oncosavi-rojo: {{ config('laboratorio.colores.rojo') }};
    }
    .fi-simple-layout { background: linear-gradient(145deg, #090B3B, #233F5A); }
    .fi-simple-main { border-top: 4px solid var(--oncosavi-rojo); }
    .fi-topbar nav { border-bottom: 3px solid var(--oncosavi-celeste); }
    .fi-sidebar-header { border-bottom: 3px solid var(--oncosavi-rojo); }
    .fi-logo { color: var(--oncosavi-marino); }
    .dark .fi-logo { color: #fff; }
    .dark .fi-sidebar-item-active a { background: #233F5A; }
    .dark .fi-sidebar-item-active .fi-sidebar-item-label,
    .dark .fi-sidebar-item-active .fi-sidebar-item-icon,
    .dark .fi-link.fi-color-primary { color: #A5D1DF; }
    .fi-simple-main a { text-underline-offset: 3px; }
    .fi-simple-main a:hover { text-decoration: underline; }
    .fi-body { accent-color: var(--oncosavi-marino); }
    /* Tonos de interfaz; los colores clínicos de recipientes conservan su significado. */
    .text-blue-600, .text-blue-700, .text-blue-800, .text-indigo-600 { color: #090B3B; }
    .bg-blue-50, .bg-indigo-50 { background-color: #F0F7FA; }
    .bg-blue-100, .bg-indigo-100 { background-color: #E0F0F5; }
    .bg-blue-200 { background-color: #BFDDE7; }
    .border-blue-100 { border-color: #BFDDE7; }
    .dark .dark\:text-blue-200, .dark .dark\:text-blue-300, .dark .dark\:text-blue-400 { color: #A5D1DF; }
    .dark .dark\:bg-blue-900, .dark .dark\:bg-blue-900\/50, .dark .dark\:bg-blue-900\/20 { background-color: #233F5A; }
    .dark .dark\:border-blue-800 { border-color: #32768C; }
</style>
<style>
    /* Fondos pastel y texto oscuro legible en ambos temas. */
    @foreach (config('estados') as $tipo => $paleta)
        .estado-{{ $tipo }} {
            background-color: {{ $paleta['fondo'] }} !important;
            color: {{ $paleta['texto'] }} !important;
            border: 1px solid {{ $paleta['borde'] }};
        }
        @if ($tipo !== 'gray')
            .fi-badge.fi-color-{{ $tipo }},
            .fi-btn.fi-color-{{ $tipo }},
            .fi-icon-btn.fi-color-{{ $tipo }},
            .fi-no-notification.fi-status-{{ $tipo }} {
                background-color: {{ $paleta['fondo'] }} !important;
                color: {{ $paleta['texto'] }} !important;
                --tw-ring-color: {{ $paleta['borde'] }} !important;
            }
            .fi-badge.fi-color-{{ $tipo }} .fi-badge-icon,
            .fi-btn.fi-color-{{ $tipo }} .fi-btn-icon,
            .fi-icon-btn.fi-color-{{ $tipo }} .fi-icon-btn-icon,
            .fi-no-notification.fi-status-{{ $tipo }} .fi-no-notification-title,
            .fi-no-notification.fi-status-{{ $tipo }} .fi-no-notification-body,
            .fi-no-notification.fi-status-{{ $tipo }} .fi-no-notification-icon,
            .fi-no-notification.fi-status-{{ $tipo }} .fi-no-notification-close-btn {
                color: {{ $paleta['texto'] }} !important;
            }
            .fi-btn.fi-color-{{ $tipo }}:hover { background-color: {{ $paleta['borde'] }} !important; }
        @endif
    @endforeach
</style>
