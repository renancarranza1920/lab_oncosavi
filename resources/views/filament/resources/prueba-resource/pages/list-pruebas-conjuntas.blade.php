<x-filament-panels::page>

    <!-- Buscador -->
    <div class="mb-4">
        <x-filament::input.wrapper>
            <x-filament::input
                type="search"
                wire:model.live.debounce.500ms="search"
                placeholder="Buscar por examen, nombre de prueba o ID de conjunto..."
            />
        </x-filament::input.wrapper>
    </div>

    @if (empty($matrices))
        <div class="flex items-center justify-center h-48 border-2 border-dashed rounded-lg">
            <p class="text-lg text-gray-500 dark:text-gray-400">
                No se encontraron pruebas de tipo conjunto.
            </p>
        </div>
    @else
        <!-- Contenedor con espaciado vertical mejorado -->
        <div class="space-y-6">
            @foreach ($matrices as $matriz)
                <x-filament::card>
                    <!-- Cabecera de la tarjeta con título y botón de acción -->
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-xl font-bold text-gray-800 dark:text-gray-200">{{ $matriz['examen'] }}</h2>
                            <p class="text-sm text-gray-500">
                                ID de Conjunto: 
                                <span class="font-mono bg-gray-100 dark:bg-gray-800 px-1 rounded">{{ $matriz['tipo_conjunto'] }}</span>
                            </p>
                        </div>
                        <!-- Contenedor para los botones de acción -->
                        <!-- Con más espacio -->
<div class="flex items-center space-x-4">
    {{-- Botón Editar --}}
    @if ($editAction = $this->editConjuntoAction())
        {{ $editAction->arguments([
            'tipo_conjunto' => $matriz['tipo_conjunto']
        ]) }}
    @endif

    {{-- Botón Eliminar --}}
    @if ($deleteAction = $this->deleteConjuntoAction())
        {{ $deleteAction->arguments([
            'tipo_conjunto' => $matriz['tipo_conjunto']
        ]) }}
    @endif
</div>



                    </div>

                    <!-- Tabla de la Matriz -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-4 py-2"></th> <!-- Esquina vacía -->
                                    @foreach ($matriz['columnas'] as $columna)
                                        <th scope="col" class="px-4 py-2 text-center">{{ $columna }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($matriz['filas'] as $fila)
                                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                        <th scope="row" class="px-4 py-2 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                            {{ $fila }}
                                        </th>
                                        @foreach ($matriz['columnas'] as $columna)
                                            <td class="px-4 py-2 text-center border-l dark:border-gray-700">
                                                @php
                                                    $prueba = $matriz['data'][$fila][$columna] ?? null;
                                                @endphp

                                                @if ($prueba)
                                                    <span class="text-xs">{{ $prueba->nombre }}</span>
                                                @else
                                                    <span class="text-xs text-red-400">(Error)</span>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::card>
            @endforeach
        </div>
    @endif

</x-filament-panels::page>

