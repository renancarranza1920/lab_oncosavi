@php
    // Agrupamos la lógica al inicio para mantener el HTML limpio
    $agrupadoPorPerfil = $record->detalleOrden->groupBy('perfil_id');

    $orden = $record; // Renombramos para claridad
    $cliente = $orden->cliente;

    // Mapeo de colores para los estados
    $statusColor = \App\Support\EstadoVisual::clase($record->estado);
@endphp

<div class="space-y-6 text-sm">

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <div>
            <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Cliente</h3>
            <p><strong>Expediente:</strong> {{ $record->cliente->NumeroExp }}</p>
            <p><strong>DUI:</strong> {{ $record->cliente->dui ?? 'N/A' }}</p>
            <p><strong>Nombre:</strong> {{ $record->cliente->nombre }} {{ $record->cliente->apellido }}</p>
        </div>
        <div>
            <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Orden</h3>
            <p><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($record->fecha)->format('d/m/Y') }}</p>

            <div class="flex items-center space-x-2">
                <strong>Estado:</strong>
                <span class="px-2 py-1 text-xs font-medium rounded-full {{ $statusColor }}">
                    {{ ucfirst(str_replace('_', ' ', $record->estado)) }}
                </span>
            </div>

            @if (!empty($orden->observaciones))
                <div class="col-span-2 pt-4 border-t dark:border-gray-600">
                    <dt class="text-gray-500 dark:text-gray-400">Observaciones de la Orden:</dt>
                    <dd class="font-medium text-gray-900 dark:text-white whitespace-pre-wrap">{{ $orden->observaciones }}
                    </dd>
                </div>
            @endif

            @if ($record->estado === 'pausada' && $record->motivo_pausa)
                <div
                    class="mt-2 p-2 estado-warning border-l-4 rounded-r-lg">
                    <p class="font-semibold ui-text-warning">Motivo de la Pausa:</p>
                    <p class="ui-text-warning">{{ $record->motivo_pausa }}</p>
                </div>
            @endif
        </div>
    </div>

    {{-- TABLA MEJORADA: DISEÑO VISUAL DIFERENCIADO --}}
    <div>
        <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Detalles de la Orden</h3>
        <div class="border rounded-lg overflow-hidden dark:border-gray-700 shadow-sm">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-100 dark:bg-gray-800 border-b dark:border-gray-700">
                    <tr>
                        <th class="p-3 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Descripción</th>
                        <th
                            class="p-3 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">
                            Precio</th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-gray-700 bg-white dark:bg-gray-900">

                    {{-- PERFILES --}}
                    @foreach ($agrupadoPorPerfil as $perfilId => $items)
                        @if ($perfilId)
                            @php $primerItem = $items->first(); @endphp

                            {{-- Cabecera del Perfil (Con fondo y etiqueta) --}}
                            <tr class="bg-gray-50 dark:bg-gray-800/50">
                                <td class="p-3">
                                    <div class="flex items-center gap-2">
                                        <x-heroicon-o-rectangle-stack class="w-5 h-5 text-primary-600 dark:text-primary-400"/>
                                        <div>
                                            <span class="font-bold text-gray-900 dark:text-white text-base block">
                                                {{ $primerItem->nombre_perfil ?? 'Perfil' }}
                                            </span>
                                            <span class="text-[10px] font-bold text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/30 px-1.5 py-0.5 rounded border border-primary-100 dark:border-primary-800">
                                                PERFIL
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-3 text-right font-bold font-mono text-gray-900 dark:text-white align-top pt-4">
                                    ${{ number_format($primerItem->precio_perfil, 2) }}
                                </td>
                            </tr>

                            {{-- Ítems del Perfil (Indentados y más pequeños) --}}
                            @foreach ($items as $detalle)
                                <tr class="bg-white dark:bg-gray-900">
                                    <td class="py-2 pl-10 pr-3 border-l-4 border-gray-50 dark:border-gray-800">
                                        <div class="flex items-center justify-between">
                                            <span class="text-gray-600 dark:text-gray-400 text-sm flex items-center gap-2">
                                                <span class="w-1.5 h-1.5 rounded-full bg-gray-300 dark:bg-gray-600"></span>
                                                {{ $detalle->nombre_examen }}
                                            </span>
                                            @if($detalle->status)
                                                <span class="text-[10px] text-gray-400 bg-gray-50 dark:bg-gray-800 px-1 rounded border dark:border-gray-700">
                                                    {{ $detalle->status }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td></td> {{-- Columna vacía --}}
                                </tr>
                            @endforeach
                        @endif
                    @endforeach

                    {{-- EXÁMENES INDIVIDUALES --}}
                    @if ($agrupadoPorPerfil->has(null) || $agrupadoPorPerfil->has(''))
                        @foreach ($agrupadoPorPerfil[null] ?? [] as $detalle)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30 transition-colors">
                                <td class="p-3 pl-4">
                                    <div class="flex items-center gap-3">
                                        <x-heroicon-o-beaker class="w-5 h-5 text-gray-400 dark:text-gray-500"/>
                                        <div>
                                            <span class="font-semibold text-gray-700 dark:text-gray-200 text-sm block">
                                                {{ $detalle->nombre_examen }}
                                            </span>
                                            @if($detalle->status)
                                                <span class="text-[10px] text-gray-400 bg-gray-50 dark:bg-gray-800 px-1 rounded border dark:border-gray-700">
                                                    {{ $detalle->status }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="p-3 text-right font-mono text-gray-700 dark:text-gray-300 font-medium">
                                    ${{ number_format($detalle->precio_examen, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </div>
    {{-- FIN TABLA MEJORADA --}}

    <section>
        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-2">Gestión de Muestras</h3>
        <div class="p-4 border rounded-lg dark:border-gray-700 text-sm">
            @if($orden->tomaMuestraUser)
                <div class="mb-2">
                    <dt class="text-gray-500 dark:text-gray-400">Muestras recibidas por:</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $orden->tomaMuestraUser->name }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Fecha de recepción:</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">
                        {{ $orden->fecha_toma_muestra->format('d/m/Y h:i A') }}</dd>
                </div>
            @else
                <p class="text-gray-500 dark:text-gray-400">Las muestras de esta orden aún no han sido recibidas.</p>
            @endif
        </div>
    </section>

    <hr class="my-4 dark:border-gray-700">
    
    {{-- AQUÍ MOSTRAMOS EL ESTADO REAL DE LAS PRUEBAS (SNAPSHOT O VIVO) --}}
    {{-- Usamos la variable $lista_pruebas_visual que pasamos desde el Action --}}
    <div>
        <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Estado de las Pruebas</h3>
        <div class="space-y-4">
             @foreach($lista_pruebas_visual as $item)
                 {{-- Solo mostramos si tiene pruebas y no es externo --}}
                 @if(count($item['pruebas']) > 0 && !$item['es_externo'])
                    <div class="p-3 border rounded-lg dark:border-gray-700">
                        <p class="font-semibold text-gray-800 dark:text-gray-200">{{ $item['nombre_examen'] }}</p>
                        <ul class="mt-2 space-y-1 pl-4">
                            @foreach($item['pruebas'] as $prueba)
                                <li class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                    @if($prueba['completado'])
                                        <x-heroicon-s-check-circle class="h-5 w-5 ui-text-success mr-2 flex-shrink-0" />
                                        <span>{{ $prueba['nombre'] }}</span>
                                    @else
                                        <x-heroicon-o-clock class="h-5 w-5 text-gray-400 mr-2 flex-shrink-0" />
                                        <span>{{ $prueba['nombre'] }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            @endforeach
        </div>
    </div>

    <div class="mt-6 pt-4 border-t-2 border-gray-300 dark:border-gray-600">
        <div class="flex flex-col items-end space-y-2">

            @if(isset($record->descuento) && $record->descuento > 0)
                <div class="flex justify-between w-full sm:w-1/2 text-sm text-gray-600 dark:text-gray-400">
                    <span>Subtotal:</span>
                    <span class="font-mono">${{ number_format($record->total + $record->descuento, 2) }}</span>
                </div>

                <div class="flex justify-between w-full sm:w-1/2 text-sm ui-text-danger font-medium">
                    <span>
                        Descuento
                        @if($record->codigo)
                            <span class="text-xs text-gray-500">({{ $record->codigo->codigo }})</span>
                        @endif
                        :
                    </span>
                    <span class="font-mono">- ${{ number_format($record->descuento, 2) }}</span>
                </div>

                <div class="w-full sm:w-1/2 border-t border-gray-200 dark:border-gray-700 my-1"></div>
            @endif

            <div class="flex justify-between w-full sm:w-1/2 text-xl font-bold">
                <span class="text-gray-900 dark:text-white">Total a Pagar:</span>
                <span
                    class="font-mono ui-text-primary">${{ number_format($record->total, 2) }}</span>
            </div>

        </div>
    </div>
</div>
