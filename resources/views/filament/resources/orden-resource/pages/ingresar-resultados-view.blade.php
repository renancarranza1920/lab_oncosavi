@php
    $examenes = $getState();
    
    // 1. Agrupamos por TIPO DE EXAMEN (Hematología, Química, etc.)
    // preserveKeys: true es vital para mantener el ID del detalle de la orden
    $examenesAgrupados = collect($examenes)->groupBy('tipo_examen', preserveKeys: true);
@endphp
<x-filament::card class="mb-6">
    <div class="flex flex-wrap items-center justify-between gap-6 text-sm">

        <div class="flex flex-col">
            <span class="text-xs text-gray-500 dark:text-gray-400 uppercase">Paciente</span>
            <span class="font-semibold text-gray-900 dark:text-white">
                {{ $this->record->cliente->nombre }}
                {{ $this->record->cliente->apellido }}
            </span>
        </div>

        <div class="flex flex-col">
            <span class="text-xs text-gray-500 dark:text-gray-400 uppercase">Edad</span>
            <span class="font-semibold">
                {{ $this->record->cliente->edad_legible ?? '—' }}
            </span>
        </div>

        <div class="flex flex-col">
            <span class="text-xs text-gray-500 dark:text-gray-400 uppercase">Género</span>
            <span class="font-semibold">
                {{ $this->record->cliente->genero ?? '—' }}
            </span>
        </div>

        <div class="flex flex-col">
            <span class="text-xs text-gray-500 dark:text-gray-400 uppercase">Orden</span>
            <span class="font-bold ui-text-primary text-lg">
                #{{ $this->record->id }}
            </span>
        </div>

        <div class="flex flex-col">
            <span class="text-xs text-gray-500 dark:text-gray-400 uppercase">Fecha</span>
            <span class="font-semibold">
                {{ $this->record->created_at->format('d/m/Y H:i') }}
            </span>
        </div>

        <div>
            <span class="px-3 py-1 rounded-full text-xs font-semibold
                {{ \App\Support\EstadoVisual::clase($this->record->estado) }}">
                {{ strtoupper($this->record->estado) }}
            </span>
        </div>

    </div>
</x-filament::card>
<div class="space-y-12">
    @forelse($examenesAgrupados as $tipoExamen => $grupoExamenes)
        <div class="space-y-4">
            {{-- SEPARADOR Y TÍTULO DE GRUPO (NIVEL 1) --}}
            <div class="relative">
                <div class="absolute inset-0 flex items-center" aria-hidden="true">
                    <div class="w-full border-t border-gray-300 dark:border-gray-600"></div>
                </div>
                <div class="relative flex justify-start">
                    <span class="pr-3 text-lg font-bold text-primary-600 bg-white dark:bg-gray-900 dark:text-primary-400 uppercase">
                        {{ $tipoExamen ?: 'Otros Exámenes' }}
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6">
                @foreach($grupoExamenes as $detalleId => $examenData)
                    @php
                        $esReferido = $examenData['es_referido'] ?? false;
                        $tabInicial = $esReferido ? 'externo' : 'interno';
                    @endphp

                    <x-filament::card>
                        {{-- TÍTULO DEL EXAMEN (NIVEL 2) --}}
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-xl font-bold tracking-tight text-gray-900 dark:text-white">
                                {{ $examenData['examen_nombre'] }}
                            </h2>
                            @if($esReferido)
                                <span class="estado-info text-xs font-medium px-2.5 py-0.5 rounded uppercase">Examen Referido</span>
                            @endif
                        </div>

                        {{-- INICIO ALPINE --}}
                        <div x-data="{ activeTab: '{{ $tabInicial }}' }">
                            
                            {{-- Pestañas Visuales --}}
                            <div class="flex border-b border-gray-200 dark:border-gray-700 mb-6">
                                <button type="button" class="py-2 px-4 text-sm font-medium border-b-2 border-primary-500 ui-text-primary ui-tab cursor-default">
                                    {{ $esReferido ? 'Resultados Externos / Manuales' : 'Resultados Laboratorio' }}
                                </button>
                            </div>

                            {{-- CONTENIDO INTERNO (PRUEBAS DEL LABORATORIO) --}}
                            @if(!$esReferido)
                                <div x-show="activeTab === 'interno'" class="space-y-8">
                                    
                                    @php
                                        // AGRUPAMOS LAS PRUEBAS POR SU TIPO (NIVEL 3)
                                        // Usamos el campo 'tipo_prueba_nombre' que inyectamos en el controlador
                                        $pruebasPorTipo = collect($examenData['pruebas_unitarias'])->groupBy('tipo_prueba_nombre');
                                    @endphp

                                    @foreach ($pruebasPorTipo as $nombreTipoPrueba => $listaPruebas)
                                        <div class="space-y-3">
                                            {{-- SUBTÍTULO DEL TIPO DE PRUEBA (Ej: Serie Roja, Enzimas, etc.) --}}
                                            <h3 class="text-xs font-extrabold text-gray-600 dark:text-gray-400 uppercase tracking-widest border-l-4 border-primary-500 pl-2">
                                                {{ $nombreTipoPrueba }}
                                            </h3>

                                            <div class="overflow-x-auto border rounded-lg dark:border-gray-700 shadow-sm">
                                                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                                    <thead class="text-xs text-gray-700 dark:text-gray-200 uppercase bg-gray-50 dark:bg-gray-700">
                                                        <tr>
                                                            <th class="px-3 py-2 w-1/3">Prueba</th>
                                                            <th class="px-3 py-2">Resultado</th>
                                                            <th class="px-3 py-2">Valor de Referencia</th>
                                                            <th class="px-3 py-2">Unid.</th>
                                                             <th class="px-3 py-2 text-center">Colorear</th>
                                                            <th class="px-3 py-2">Notas</th>
                                                           
                                                            <th class="px-3 py-2"></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($listaPruebas as $prueba)
                                                            @php
                                                                // IMPORTANTE: Buscamos el índice original en el array plano de 'pruebas_unitarias'
                                                                // Esto asegura que wire:model apunte a la posición correcta en el servidor
                                                                $originalIndex = collect($examenData['pruebas_unitarias'])
                                                                    ->search(fn($item) => $item['prueba_id'] === $prueba['prueba_id']);
                                                            @endphp

                                                            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 ui-table-row">
                                                                <th class="px-3 py-2 font-medium text-gray-900 dark:text-white">
                                                                    {{ $prueba['prueba_nombre'] }}
                                                                </th>
                                                                <td class="px-3 py-2">
                                                                    <x-filament::input.wrapper>
                                                                        @php
    $inputId = "resultado_{$detalleId}_{$originalIndex}";
    $sinReferencia = empty(strip_tags($prueba['valor_referencia'])) 
                     || $prueba['valor_referencia'] === 'N/A';
@endphp

@if($sinReferencia)
    {{-- INPUT CON SUGERENCIAS CUALITATIVAS --}}
    <x-filament::input 
        type="text"
        list="sugerencias_{{ $inputId }}"
        x-mask:dynamic="/^-?[\d,]*(\.\d*)?$/.test($input) ? $money($input, '.', ',', 20) : ''" wire:model.defer="data.resultados_examenes.{{ $detalleId }}.pruebas_unitarias.{{ $originalIndex }}.resultado"
    />

    <datalist id="sugerencias_{{ $inputId }}">
        <option value="POSITIVO">
        <option value="NEGATIVO">
        <option value="NO SE OBSERVAN">
        <option value="ERY/μL">
        <option value="LEU/μL">
        <option value="AMARILLO">
        <option value="LIMPIO">
        <option value="LIGERAMENTE TURBIO">
        <option value="CAFE">
        <option value="BEIGE">
        <option value="NEGRO">
        <option value="PASTOSA">
        <option value="BLANDA">
        <option value="LIQUIDA">
        <option value="SEMI-LIQUIDA">
        <option value="ESCASOS">
        <option value="ESCASAS">
        <option value="MODERADA">
        <option value="MODERADOS">
        <option value="ABUNDANTE">
        <option value="GRUMO LEUCOCITARIO">
        <option value="VACUOLAS DE Blastocystis hominis ESCASAS">
        <option value="QUISTES DE Endolimax nana">
        <option value="QUISTES Y TROFOZOITOS DE Entamoeba histolytica">
        <option value="QUISTES DE Iodamoeba butschlii">
        <option value="QUISTES DE Giardia lamblia">
        


    </datalist>
@else
    {{-- INPUT NORMAL NUMÉRICO --}}
    <x-filament::input 
        type="text"
        step="any"
        x-mask:dynamic="/^-?[\d,]*(\.\d*)?$/.test($input) ? $money($input, '.', ',', 20) : ''" wire:model.defer="data.resultados_examenes.{{ $detalleId }}.pruebas_unitarias.{{ $originalIndex }}.resultado"
    />
@endif

                                                                    </x-filament::input.wrapper>
                                                                </td>
                                                                <td class="px-3 py-2 text-xs">
                                                                    @if($prueba['es_alerta'] ?? false)
                                                                        <div class="flex flex-col">
                                                                            <div class="flex items-center ui-text-warning mb-1">
                                                                                <x-heroicon-s-exclamation-triangle class="w-4 h-4 mr-1"/>
                                                                                <span class="font-bold uppercase">@php
    $ref = $prueba['valor_referencia'];

    $refFormateado = \App\Support\NumeroLaboratorio::formatearTexto($ref);
@endphp

{!! $refFormateado !!}</span>
                                                                            </div>
                                                                            <div class="text-[10px] leading-tight estado-warning p-1 rounded">
                                                                                {{ $prueba['mensaje_alerta'] }}
                                                                            </div>
                                                                        </div>
                                                                    @else
                                                                       <span class="text-gray-600 dark:text-gray-400">
                                                                            @php
    $ref = $prueba['valor_referencia'];

    $refFormateado = \App\Support\NumeroLaboratorio::formatearTexto($ref);
@endphp

{!! $refFormateado !!}
                                                                        </span>

                                                                    @endif
                                                                </td>
                                                                <td class="px-3 py-2 text-gray-600 dark:text-gray-400">{{ $prueba['unidades'] }}</td>
                                                                <td class="px-3 py-2 text-center">
    <x-filament::input.checkbox
        wire:model.defer="data.resultados_examenes.{{ $detalleId }}.pruebas_unitarias.{{ $originalIndex }}.alertar"
    />
</td>
                                                                <td class="px-3 py-2 text-gray-600 dark:text-gray-400 text-xs leading-snug">
    {!! $prueba['notas'] !!}
</td>

                                                                <td class="px-3 py-2 text-right">
                                                                    @if($prueba['resultado_id'])
                                                                        <x-filament::icon-button color="danger" icon="heroicon-o-trash" label="Eliminar resultado" wire:click="deleteResultado({{ $prueba['resultado_id'] }})" />
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    @endforeach

                                    {{-- SECCIÓN DE MATRICES --}}
                                    @if (!empty($examenData['matrices']))
                                        @foreach ($examenData['matrices'] as $tipoConjunto => $matriz)
                                            <div class="mt-8">
                                                <h3 class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-3 flex items-center">
                                                    <x-heroicon-m-table-cells class="w-4 h-4 mr-2"/>
                                                    Matriz: {{ ucfirst($tipoConjunto) }}
                                                </h3>
                                                <div class="overflow-x-auto border rounded-lg shadow-sm">
                                                    <table class="w-full text-sm text-left">
                                                        <thead class="bg-gray-50 dark:bg-gray-700 text-xs uppercase text-gray-700 dark:text-gray-300">
                                                            <tr>
                                                                <th class="px-4 py-2"></th>
                                                                @foreach ($matriz['columnas'] as $columna)
                                                                    <th class="px-4 py-2 text-center border-l dark:border-gray-600">{{ $columna }}</th>
                                                                @endforeach
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($matriz['filas'] as $fila)
                                                                <tr class="border-b dark:border-gray-700 bg-white dark:bg-gray-800">
                                                                    <th class="px-4 py-2 font-medium text-gray-900 dark:text-white bg-gray-50/50 dark:bg-gray-700/30">{{ $fila }}</th>
                                                                    @foreach ($matriz['columnas'] as $columna)
                                                                        <td class="px-4 py-2 text-center border-l dark:border-gray-700">
                                                                            @if (isset($matriz['data'][$fila][$columna]))
                                                                                <x-filament::input.wrapper>
                                                                                    <x-filament::input type="text" 
                                                                                        x-mask:dynamic="/^-?[\d,]*(\.\d*)?$/.test($input) ? $money($input, '.', ',', 20) : ''" wire:model.defer="data.resultados_examenes.{{ $detalleId }}.matrices.{{ $tipoConjunto }}.data.{{ $fila }}.{{ $columna }}.resultado"/>
                                                                                </x-filament::input.wrapper>
                                                                            @endif
                                                                        </td>
                                                                    @endforeach
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                            @endif

                            {{-- CONTENIDO EXTERNO (PARA EXÁMENES REFERIDOS) --}}
                            @if($esReferido)
                                <div x-show="activeTab === 'externo'" class="space-y-4">
                                    <div class="p-3 estado-info rounded-lg text-sm">
                                        Ingrese los resultados del laboratorio externo de forma manual.
                                    </div>
                                    <div class="overflow-x-auto border rounded-lg">
                                        <table class="w-full text-sm text-left">
                                            <thead class="bg-gray-50 dark:bg-gray-700 text-xs uppercase">
                                                <tr>
                                                    <th class="px-3 py-2 w-1/3">Prueba</th>
                                                    <th class="px-3 py-2">Resultado</th>
                                                    <th class="px-3 py-2">Valor Ref.</th>
                                                    <th class="px-3 py-2">Unid.</th>
                                                    <th class="px-3 py-2 text-center">Colorear</th>
                                                    <th class="px-3 py-2"></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($examenData['externos'] ?? [] as $index => $ext)
                                                    <tr wire:key="externo-{{ $detalleId }}-{{ $ext['temp_id'] }}" class="border-b dark:border-gray-700">
                                                        <td class="px-3 py-2">
                                                            <x-filament::input type="text" placeholder="Nombre..." wire:model.defer="data.resultados_examenes.{{ $detalleId }}.externos.{{ $index }}.prueba_nombre"/>
                                                        </td>
                                                        <td class="px-3 py-2">
                                                            <x-filament::input type="text" placeholder="Resultado..." x-mask:dynamic="/^-?[\d,]*(\.\d*)?$/.test($input) ? $money($input, '.', ',', 20) : ''" wire:model.defer="data.resultados_examenes.{{ $detalleId }}.externos.{{ $index }}.resultado"/>
                                                        </td>
                                                        <td class="px-3 py-2">
                                                            <x-filament::input type="text" placeholder="Referencia..." wire:model.defer="data.resultados_examenes.{{ $detalleId }}.externos.{{ $index }}.valor_referencia"/>
                                                        </td>
                                                        <td class="px-3 py-2">
                                                            <x-filament::input type="text" placeholder="mg/dL..." wire:model.defer="data.resultados_examenes.{{ $detalleId }}.externos.{{ $index }}.unidades"/>
                                                        </td>
                                                        <td class="px-3 py-2 text-center">
                                                            <x-filament::input.checkbox
                                                                wire:model.defer="data.resultados_examenes.{{ $detalleId }}.externos.{{ $index }}.alertar"
                                                            />
                                                        </td>
                                                        <td class="px-3 py-2 text-center">
                                                            <x-filament::icon-button color="danger" icon="heroicon-o-trash" label="Eliminar resultado externo" wire:click="removeExternalRow({{ $detalleId }}, {{ $index }})" />
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="5" class="px-3 py-6 text-center text-gray-400 italic">No hay resultados externos agregados.</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                    <x-filament::button type="button" size="sm" color="gray" icon="heroicon-m-plus" wire:click="addExternalRow({{ $detalleId }})">
                                        Agregar Fila de Resultado
                                    </x-filament::button>
                                </div>
                            @endif

                        </div> {{-- Fin Alpine --}}
                    </x-filament::card>
                @endforeach
            </div>
        </div>
    @empty
        <div class="flex flex-col items-center justify-center py-12 text-gray-400">
            <x-heroicon-o-beaker class="w-12 h-12 mb-4 opacity-20"/>
            <p>No hay exámenes registrados en esta orden.</p>
        </div>
    @endforelse
</div>
