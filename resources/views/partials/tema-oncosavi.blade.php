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

    /* Prefijo y número se presentan como un único control telefónico. */
    .telefono-compuesto .fi-fo-repeater-item-content > .fi-fo-component-ctn {
        grid-template-columns: 96px minmax(0, 1fr) !important;
        column-gap: 0 !important;
    }
    .telefono-compuesto .fi-fo-repeater-item-content > .fi-fo-component-ctn > :first-child { grid-column: 1 / -1 !important; }
    .telefono-compuesto .fi-fo-repeater-item-content > .fi-fo-component-ctn > :nth-child(2) { grid-column: 1 !important; }
    .telefono-compuesto .fi-fo-repeater-item-content > .fi-fo-component-ctn > :nth-child(3) { grid-column: 2 !important; }
    .telefono-prefijo .fi-input-wrp { border-top-right-radius: 0 !important; border-bottom-right-radius: 0 !important; }
    .telefono-numero .fi-input-wrp { margin-left: -1px; border-top-left-radius: 0 !important; border-bottom-left-radius: 0 !important; }
    .telefono-prefijo .fi-input-wrp:focus-within, .telefono-numero .fi-input-wrp:focus-within { position: relative; z-index: 2; }
    .telefono-requerido { color: rgb(var(--danger-600)); }
    .telefono-badge { display: inline-flex; align-items: center; border: 1px solid; border-radius: .3rem; padding: .12rem .45rem; font-size: .68rem; font-weight: 500; line-height: 1.25; }
    .telefono-badge-sv { border-color: #a7f3d0; color: #047857; background: #ecfdf5; }
    .telefono-badge-us { border-color: #bfdbfe; color: #1d4ed8; background: #eff6ff; }
    .telefono-badge-internacional { border-color: #bfdbfe; color: #1d4ed8; background: #eff6ff; }
    .telefono-badge-fijo { border-color: #fed7aa; color: #c2410c; background: #fff7ed; }
    .telefono-destino-compuesto > .fi-fo-component-ctn {
        grid-template-columns: 96px minmax(0, 1fr) !important;
        column-gap: 0 !important;
    }
    .telefono-destino-compuesto > .fi-fo-component-ctn > :first-child { grid-column: 1 / -1 !important; }
    .telefono-destino-compuesto > .fi-fo-component-ctn > :nth-child(2) { grid-column: 1 !important; }
    .telefono-destino-compuesto > .fi-fo-component-ctn > :nth-child(3) { grid-column: 2 !important; }

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
    .orden-detail { color: var(--ui-text); }
    .orden-detail-table tbody { background: var(--ui-surface); }
    .orden-detail-table .orden-detail-row { background: var(--ui-surface); }
    .orden-detail-table .orden-detail-row:hover { background: var(--ui-subtle); }
    .orden-detail-table .orden-detail-row td { color: var(--ui-text); }
    .orden-detail-table .orden-detail-profile-row { background: var(--ui-subtle); color: var(--ui-text); }
    .orden-detail-table .orden-detail-profile-badge { background: var(--ui-soft); color: var(--ui-heading); border-color: var(--ui-border); }
    .orden-detail-table .orden-detail-exam-tag { background: var(--ui-subtle); color: var(--ui-muted); border-color: var(--ui-border); }

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
<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('abrir-destino-envio', (evento) => {
            const url = evento?.url ?? evento?.[0]?.url;
            if (! url) return;

            const nuevaVentana = window.open(url, '_blank');
            if (nuevaVentana) {
                nuevaVentana.opener = null;
            } else {
                window.location.href = url;
            }
        });
    });
</script>
