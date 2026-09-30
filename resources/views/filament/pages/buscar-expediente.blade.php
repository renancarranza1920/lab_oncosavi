<x-filament-panels::page>
    {{-- SECCIÓN DE FILTROS --}}
    <x-filament::section>
        <x-slot name="heading">
            Filtros de Búsqueda
        </x-slot>
        <x-slot name="description">
            Ingresa los datos del paciente para localizar su expediente.
        </x-slot>

        {{-- Formulario --}}
        <div class="space-y-6">
            {{ $this->form }}
        </div>

        {{-- Botones de Acción --}}
        <div class="mt-6 flex justify-end gap-3 border-t border-gray-200 dark:border-gray-700 pt-4">
            <x-filament::button 
                color="gray" 
                outlined 
                wire:click="limpiar"
            >
                Limpiar Filtros
            </x-filament::button>

            <x-filament::button 
                wire:click="buscar" 
                icon="heroicon-m-magnifying-glass"
            >
                Buscar Paciente
            </x-filament::button>
        </div>
    </x-filament::section>

    {{-- SECCIÓN DE RESULTADOS --}}
    <div class="mt-6">
        <h3 class="text-xl font-bold tracking-tight text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            Resultados 
            <span class="inline-flex items-center justify-center w-6 h-6 ms-2 text-xs font-semibold text-blue-800 bg-blue-200 rounded-full">
                {{ count($resultados) }}
            </span>
        </h3>

        @if(count($resultados) > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                @foreach($resultados as $cliente)
                @php
                        // Verificamos si tiene permiso para entrar a la carpeta
                        $puedeVerExpediente = auth()->user()->can('ver_expediente_clientes');
                    @endphp
                   {{-- 
                        Si tiene permiso, es un enlace (a). 
                        Si no, es un simple div (no clickeable) con opacidad reducida.
                    --}}
                    <{{ $puedeVerExpediente ? 'a' : 'a' }} 
                        @if($puedeVerExpediente)
                            href="{{ \App\Filament\Resources\ClientesResource::getUrl('expediente', ['record' => $cliente->id]) }}"
                        @endif
                        class="group relative flex flex-col p-5 bg-white border border-gray-200 rounded-xl shadow-sm transition-all duration-200 
                        {{ $puedeVerExpediente 
                            ? 'hover:shadow-md hover:border-primary-500 cursor-pointer' 
                            : 'opacity-75 cursor-not-allowed bg-gray-50' 
                        }} 
                        dark:bg-gray-900 dark:border-gray-800"
                    >
                        {{-- Cabecera Card: Icono y Estado --}}
                        <div class="flex justify-between items-start mb-4">
                            <div class="p-2 bg-primary-50 rounded-lg group-hover:bg-primary-100 dark:bg-white/5 dark:group-hover:bg-primary-500/20 transition-colors">
                                <x-heroicon-o-folder class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                            </div>
                            
                            {{-- Badge de Estado --}}
                            @php
                                $estadoColor = match($cliente->estado) {
                                    'Activo' => 'text-green-700 bg-green-50 ring-green-600/20 dark:bg-green-500/10 dark:text-green-400 dark:ring-green-500/20',
                                    default => 'text-red-700 bg-red-50 ring-red-600/20 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/20',
                                };
                            @endphp
                            <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $estadoColor }}">
                                {{ $cliente->estado }}
                            </span>
                        </div>

                        {{-- Contenido Principal --}}
                        <div class="flex-1">
                            <h5 class="mb-1 text-base font-bold tracking-tight text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors line-clamp-1">
                                {{ $cliente->nombre }} {{ $cliente->apellido }}
                            </h5>
                            <p class="text-xs font-mono text-gray-500 dark:text-gray-400 mb-3">
                                Exp: <span class="font-semibold">{{ $cliente->NumeroExp }}</span>
                            </p>
                            <p class="text-xs font-mono text-gray-500 dark:text-gray-400 mb-3">
                                DUI: <span class="font-semibold">{{ $cliente->dui ?? 'N/A' }}</span>
                            </p>

                            {{-- Datos Extra con Iconos --}}
                            <div class="space-y-1.5">
                                {{-- Edad --}}
                                <div class="flex items-center text-xs text-gray-600 dark:text-gray-400">
                                    <x-heroicon-m-calendar class="w-3.5 h-3.5 mr-2 text-gray-400 flex-shrink-0"/>
                                    <span>
                                        @if($cliente->fecha_nacimiento)
                                            {{ \Carbon\Carbon::parse($cliente->fecha_nacimiento)->age }} años
                                        @else
                                            <span class="italic text-gray-400">Sin fecha</span>
                                        @endif
                                    </span>
                                </div>

                                {{-- Teléfono --}}
                                <div class="flex items-center text-xs text-gray-600 dark:text-gray-400">
                                    <x-heroicon-m-phone class="w-3.5 h-3.5 mr-2 text-gray-400 flex-shrink-0"/>
                                    <span>{{ $cliente->telefono ?? 'N/A' }}</span>
                                </div>

                                {{-- Correo (con truncate por si es largo) --}}
                                <div class="flex items-center text-xs text-gray-600 dark:text-gray-400" title="{{ $cliente->correo }}">
                                    <x-heroicon-m-envelope class="w-3.5 h-3.5 mr-2 text-gray-400 flex-shrink-0"/>
                                    <span class="truncate max-w-[150px]">{{ $cliente->correo ?? 'N/A' }}</span>
                                </div>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            {{-- ESTADO VACÍO --}}
            <div class="flex flex-col items-center justify-center py-12 px-4 text-center rounded-xl border-2 border-dashed border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-white/5">
                <div class="p-3 rounded-full bg-gray-100 dark:bg-gray-800 mb-4">
                    <x-heroicon-o-magnifying-glass class="w-8 h-8 text-gray-400" />
                </div>
                <h3 class="text-sm font-medium text-gray-900 dark:text-white">Sin resultados</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 max-w-sm">
                    No se encontraron expedientes con los filtros seleccionados. Intenta buscar por otro dato.
                </p>
            </div>
        @endif
    </div>
</x-filament-panels::page>
