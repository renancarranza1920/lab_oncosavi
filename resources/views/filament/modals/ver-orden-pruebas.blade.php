{{-- resources/views/filament/modals/ver-orden-pruebas.blade.php --}}

@props(['examenes', 'orden']) {{-- Recibimos la orden aquí --}}

<div class="space-y-6">
    @if($examenes && $examenes->count() > 0)
        @foreach ($examenes as $examen)
            <div class="space-y-2">
                <h3 class="text-lg font-bold ui-text-primary">
                    Examen: {{ $examen->nombre }}
                </h3>
                
                @if($examen->pruebas && $examen->pruebas->count() > 0)
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <table class="w-full text-sm text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-800">
                                    <th class="p-3 font-medium border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white">
                                        Prueba a Realizar
                                    </th>
                                    {{-- Nueva columna de Estado --}}
                                    <th class="p-3 font-medium border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white text-center w-24">
                                        Estado
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($examen->pruebas as $prueba)
                                    {{-- LÓGICA DE ESTADO --}}
                                    @php
                                        // Buscamos si existe un resultado para esta prueba en esta orden
                                        $resultadoExiste = $orden->resultados
                                            ->where('prueba_id', $prueba->id)
                                            // Opcional: filtramos por detalle si es necesario, 
                                            // pero para visualización general esto basta
                                            ->first();
                                        
                                        // Determinamos si está completado (tiene valor)
                                        $estaCompleto = $resultadoExiste && $resultadoExiste->resultado;
                                    @endphp

                                    <tr class="bg-white dark:bg-transparent">
                                        <td class="p-3 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-white">
                                            {{ $prueba->nombre }}
                                        </td>
                                        {{-- Columna de Icono --}}
                                        <td class="p-3 border border-gray-200 dark:border-gray-700 text-center">
                                            @if($estaCompleto)
                                                <div class="flex justify-center" title="Completado">
                                                    <x-heroicon-s-check-circle class="h-6 w-6 ui-text-success"/>
                                                </div>
                                            @else
                                                <div class="flex justify-center" title="Pendiente">
                                                    <x-heroicon-o-clock class="h-6 w-6 text-gray-400"/>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-4 text-center text-gray-500 border border-gray-200 rounded-lg dark:border-gray-700 dark:text-gray-400">
                        Este examen no tiene pruebas definidas.
                    </div>
                @endif
            </div>
        @endforeach
    @else
        <div class="p-4 text-center text-gray-500 dark:text-gray-400">
            No hay exámenes con pruebas en esta orden.
        </div>
    @endif
</div>