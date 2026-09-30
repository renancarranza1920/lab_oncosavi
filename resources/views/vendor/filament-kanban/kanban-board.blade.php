<x-filament-panels::page>

    <div 
        x-data 
        wire:ignore.self 
        class="w-full pb-4"
    >
        {{-- Grid de columnas --}}
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-3 gap-4  px-3">
         
             @foreach($statuses as $status)
    <div class="relative">
        <!-- Botón para imprimir toda la columna -->
     
       
        
        <div>


        @include(static::$statusView, [
            'columnWidth' => $this->getColumnWidth() ?? 'w-full'

        ])
    
           
        </div>
    </div>
@endforeach
        </div>
        

        <div wire:ignore>
            @include(static::$scriptsView)
        </div>
    </div>

    @unless($disableEditModal)
        <x-filament-kanban::edit-record-modal/>
    @endunless
</x-filament-panels::page>