<link rel="icon" type="image/png" href="{{ asset(config('laboratorio.logo')) }}">
<style>
    :root {
        --oncosavi-marino: {{ config('laboratorio.colores.marino') }};
        --oncosavi-celeste: {{ config('laboratorio.colores.celeste') }};
        --oncosavi-rojo: {{ config('laboratorio.colores.rojo') }};
        --ui-surface: #fff;
        --ui-subtle: rgb(var(--gray-50));
        --ui-border: rgb(var(--gray-200));
        --ui-text: rgb(var(--gray-800));
        --ui-muted: rgb(var(--gray-600));
        --ui-heading: rgb(var(--primary-700));
        --ui-soft: rgb(var(--primary-50));
    }
    .dark {
        --ui-surface: rgb(var(--gray-900));
        --ui-subtle: rgb(var(--gray-800));
        --ui-border: rgb(var(--gray-700));
        --ui-text: rgb(var(--gray-200));
        --ui-muted: rgb(var(--gray-400));
        --ui-heading: rgb(var(--primary-300));
        --ui-soft: rgb(var(--primary-900));
    }
    .fi-simple-layout { background: linear-gradient(145deg, var(--oncosavi-marino), rgb(var(--primary-800))); }
    .fi-simple-main { border-top: 4px solid var(--oncosavi-rojo); }
    .fi-topbar nav { border-bottom: 3px solid var(--oncosavi-celeste); }
    .fi-sidebar-header { border-bottom: 3px solid var(--oncosavi-rojo); }
    .fi-logo { color: var(--oncosavi-marino); }
    .dark .fi-logo { color: #fff; }
    .fi-simple-main a { text-underline-offset: 3px; }
    .fi-simple-main a:hover { text-decoration: underline; }
    .fi-body { accent-color: rgb(var(--primary-600)); }

    /* Filament 3 usa el tono 400 en hover oscuro; con blanco pierde contraste.
       Sólo ajustamos botones rellenos, sin alterar badges, links ni outlined. */
    .dark .fi-btn.fi-color-custom.bg-custom-600:not(.fi-btn-outlined) { background-color: rgb(var(--c-600)); }
    .dark .fi-btn.fi-color-custom.bg-custom-600:not(.fi-btn-outlined):hover:not(:disabled) { background-color: rgb(var(--c-500)); }

    /* Iconos neutros nativos: el tono 400 sobre blanco queda por debajo de 3:1. */
    .fi-icon-btn.fi-color-gray:not(:disabled) { color: rgb(var(--gray-500)); }
    .fi-icon-btn.fi-color-gray:hover:not(:disabled) { color: rgb(var(--gray-600)); }
    .dark .fi-icon-btn.fi-color-gray:not(:disabled) { color: rgb(var(--gray-400)); }
    .dark .fi-icon-btn.fi-color-gray:hover:not(:disabled) { color: rgb(var(--gray-300)); }

    .fi-sidebar-item:not(.fi-sidebar-item-active) .fi-sidebar-item-icon { color: rgb(var(--gray-500)); }
    .dark .fi-sidebar-item:not(.fi-sidebar-item-active) .fi-sidebar-item-icon { color: rgb(var(--gray-400)); }
    .ui-table-row:hover { background-color: rgb(var(--gray-50)); }
    .dark .ui-table-row:hover { background-color: rgb(var(--gray-700)); }

    /* Componentes propios: mismos tokens que Filament, sin redefinir Tailwind. */
    @foreach (['primary', 'success', 'warning', 'danger', 'info', 'gray'] as $tipo)
        .ui-text-{{ $tipo }} { color: rgb(var(--{{ $tipo }}-700)); }
        .dark .ui-text-{{ $tipo }} { color: rgb(var(--{{ $tipo }}-300)); }
        .estado-{{ $tipo }} {
            background-color: rgb(var(--{{ $tipo }}-50));
            color: rgb(var(--{{ $tipo }}-700));
            border: 1px solid rgb(var(--{{ $tipo }}-200));
        }
        .dark .estado-{{ $tipo }} {
            background-color: rgb(var(--{{ $tipo }}-950));
            color: rgb(var(--{{ $tipo }}-300));
            border-color: rgb(var(--{{ $tipo }}-700));
        }
    @endforeach
    .ui-chip { display: inline-flex; align-items: center; gap: .5rem; border-radius: 9999px; padding: .25rem .75rem; font-size: .875rem; transition: background-color .15s, color .15s; }
    .ui-chip-link { background: var(--ui-soft); color: var(--ui-heading); border: 1px solid var(--ui-border); }
    .ui-chip-link:hover { background: rgb(var(--primary-100)); }
    .dark .ui-chip-link:hover { background: rgb(var(--primary-800)); }
    .ui-tab:focus-visible, .ui-chip-link:focus-visible, .examen-tag:focus-visible, .examen-tag-selected:focus-visible {
        outline: 2px solid rgb(var(--primary-500)); outline-offset: 3px;
    }
    .examen-tag, .examen-tag-selected { cursor: pointer; border: 1px solid var(--ui-border); border-radius: 9999px; padding: .25rem .75rem; font-size: .875rem; transition: background-color .15s, color .15s; }
    .examen-tag { background: var(--ui-surface); color: var(--ui-text); }
    .examen-tag:hover:not(:disabled), .examen-tag-selected { background: var(--ui-soft); color: var(--ui-heading); border-color: rgb(var(--primary-400)); }
    .examen-tag-selected:hover:not(:disabled) { background: rgb(var(--danger-50)); color: rgb(var(--danger-700)); border-color: rgb(var(--danger-300)); }
    .dark .examen-tag-selected:hover:not(:disabled) { background: rgb(var(--danger-950)); color: rgb(var(--danger-300)); }
    .examen-tag:disabled, .examen-tag-selected:disabled { cursor: not-allowed; opacity: .5; }
</style>
