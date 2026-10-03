{{-- Reducido al 50% para mayor discreción --}}
<div class="absolute top-0 left-0 z-10" 
     style="
        width: 0; 
        height: 0; 
        border-top: 28px solid rgb(var(--primary-600)); /* Tamaño reducido */
        border-right: 28px solid transparent; 
        cursor: pointer;
        filter: drop-shadow(1px 1px 1px rgba(0,0,0,0.1));
     "
     x-data
     x-tooltip="'Compartir resultados'"
     @click.stop="$wire.mountTableAction('enviarPorCorreoOWhatsApp', {{ $getRecord()->id }})"
>
    {{-- Ajustamos la posición del ícono para que encaje en el nuevo tamaño --}}
    <div class="absolute" style="top: -26px; left: 2px; color: white;">
        <x-heroicon-s-share class="w-3 h-3" />
    </div>
</div>