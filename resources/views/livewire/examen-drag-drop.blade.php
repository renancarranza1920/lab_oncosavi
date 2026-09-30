{{-- 👇 ESTE ES EL NUEVO DIV ENVOLVENTE QUE SOLUCIONA EL ERROR 👇 --}}
<div>
    <div class="grid grid-cols-2 gap-6 p-4">
        {{-- Lista de disponibles --}}
        <div class="rounded-2xl border shadow-sm p-4 bg-white dark:bg-gray-800">
            <div class="flex justify-between items-center mb-2">
                <h3 class="text-lg font-semibold">Exámenes Disponibles</h3>
                <button type="button" wire:click="toggleDisponibles"
                    class="text-sm text-blue-600 hover:underline">-</button>
            </div>

            <input type="text" wire:model.live.debounce.300ms="busquedaDisponible" placeholder="Buscar..."
                class="w-full p-2 mb-4 border rounded-lg dark:bg-gray-700 dark:text-white" />

            <div
                class="space-y-4 overflow-hidden {{ $colapsadoDisponible ? 'max-h-0 opacity-0 pointer-events-none' : 'max-h-[1000px] opacity-100' }} transition-all duration-500">
                @forelse($agrupadosDisponibles as $tipo => $examenes)
                    <div class="p-3 rounded-xl shadow-md bg-gray-100 dark:bg-gray-700">
                        <h4 class="font-bold mb-2">{{ $tipo }}</h4>

                        {{-- Este div ahora deshabilita TODA la lista mientras se añade CUALQUIER examen --}}
                        {{-- EN LA LISTA DE DISPONIBLES --}}
                        {{-- EN LA LISTA DE DISPONIBLES --}}
<div class="flex flex-wrap gap-2">
    @foreach($examenes as $examen)
        {{-- Usamos Alpine para control local inmediato --}}
        <div x-data="{ loading: false }">
            <button 
                type="button"
                wire:key="examen-disponible-{{ $examen->id }}" 
                class="examen-tag flex items-center gap-2 text-left w-auto transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                {{-- 
                   Al hacer clic:
                   1. Ponemos loading en true (bloqueo visual inmediato)
                   2. Llamamos a Livewire
                   3. Cuando Livewire termine ($wire.addExamen), el botón desaparecerá o se reiniciará
                --}}
                @click="loading = true; $wire.addExamen({{ $examen->id }}).then(() => loading = false)"
                :disabled="loading" 
            >
                <span class="flex-grow">{{ $examen->nombre }}</span>
                
                {{-- Spinner controlado por Alpine --}}
                <div x-show="loading" style="display: none;">
                    <svg class="animate-spin h-4 w-4 text-gray-800 dark:text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
            </button>
        </div>
    @endforeach
</div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-300">No hay exámenes disponibles.</p>
                @endforelse
            </div>
        </div>

        {{-- Lista de seleccionados --}}
        <div class="rounded-2xl border shadow-sm p-4 bg-white dark:bg-gray-800">
            <div class="flex justify-between items-center mb-2">
                <h3 class="text-lg font-semibold">Exámenes Seleccionados</h3>
                <button type="button" wire:click="toggleSeleccionados"
                    class="text-sm text-blue-600 hover:underline">-</button>
            </div>

            <input type="text" wire:model.live.debounce.300ms="busquedaSeleccionado" placeholder="Buscar..."
                class="w-full p-2 mb-4 border rounded-lg dark:bg-gray-700 dark:text-white" />

            <div
                class="space-y-4 overflow-hidden {{ $colapsadoSeleccionado ? 'max-h-0 opacity-0' : 'max-h-1000px opacity-100' }} transition-all duration-500">
                @forelse($agrupadosSeleccionados as $tipo => $examenes)
                    <div class="p-3 rounded-xl shadow-md bg-gray-100 dark:bg-gray-700">
                        <h4 class="font-bold mb-2">{{ $tipo }}</h4>

                        {{-- EN LA LISTA DE SELECCIONADOS --}}
<div class="flex flex-wrap gap-2">
    @foreach($examenes as $examen)
        <div x-data="{ loading: false }">
            <button 
                type="button"
                wire:key="examen-seleccionado-{{ $examen['id'] }}" 
                class="examen-tag-selected flex items-center gap-2 text-left w-auto transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                @click="loading = true; $wire.removeExamen({{ $examen['id'] }}).then(() => loading = false)"
                :disabled="loading"
            >
                <span class="flex-grow">{{ $examen['nombre'] }}</span>
                
                <div x-show="loading" style="display: none;">
                    <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
            </button>
        </div>
    @endforeach
</div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-300">No hay exámenes seleccionados.</p>
                @endforelse
            </div>
        </div>
    </div>

    @once
        <style>
            .examen-tag,
            .examen-tag-selected {
                cursor: pointer;
                padding: 0.25rem 0.75rem;
                font-size: 0.875rem;
                border-radius: 9999px;
                box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
                transition: all 0.2s ease;
            }

            .examen-tag {
                background-color: white;
            }

            .dark .examen-tag {
                background-color: #4b5563;
                /* bg-gray-600 */
            }

            .examen-tag:hover {
                background-color: #22c55e !important;
                /* bg-success-500 */
                color: white !important;
            }

            .examen-tag-selected {
                background-color: #22c55e;
                color: white;
            }

            .examen-tag-selected:hover {
                background-color: #ef4444;
                /* bg-red-500 */
            }
        </style>
    @endonce
</div>