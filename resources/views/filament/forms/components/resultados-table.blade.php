{{-- resources/views/filament/forms/components/resultados-table.blade.php --}}
@php
    $state = $getState();
@endphp

<style>
    /* Estilos para la tabla de resultados */
    .results-table { width: 100%; border-collapse: collapse; margin-top: 0.5rem; }
    .results-table th, .results-table td { border: 1px solid var(--ui-border); padding: 0.75rem; text-align: left; font-size: 0.875rem; vertical-align: top; }
    .results-table th { background-color: var(--ui-subtle); font-weight: 600; }
    .results-table input[type="text"] { width: 100%; border-radius: 0.375rem; border: 1px solid var(--ui-border); padding: 0.5rem 0.75rem; }
    .examen-header { font-size: 1.125rem; font-weight: bold; color: var(--ui-heading); margin-bottom: 0.5rem; padding-top: 1rem; }
    .results-table { color: var(--ui-text); }
    .results-table input[type="text"] { background: var(--ui-surface); color: var(--ui-text); }
    .results-table input[disabled] { background-color: var(--ui-subtle); cursor: not-allowed; }
    .results-table input[type="text"]:focus-visible { outline: 2px solid rgb(var(--primary-500)); outline-offset: 1px; }
</style>

<div>
    {{-- El bucle principal ahora nos da el $detalleId y el $examenData --}}
    @foreach ($state as $detalleId => $examenData)
        {{-- Usamos la clave 'examen_nombre' para el título --}}
        <h3 class="examen-header">{{ $examenData['examen_nombre'] }}</h3>

        <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <table class="results-table">
                <thead>
                    <tr>
                        <th style="width: 25%;">Prueba</th>
                        <th style="width: 20%;">Resultado</th>
                        <th style="width: 30%;">Valor de Referencia</th>
                        <th style="width: 10%;">Unidades</th>
                        <th style="width: 15%;">Nota</th>
                        <th style="width: 5%;"></th>
                    </tr>
                </thead>
                <tbody>
                    {{-- El bucle interno ahora itera sobre la clave 'pruebas' --}}
                    @foreach ($examenData['pruebas'] as $index => $prueba)
                        <tr>
                            <td>{{ $prueba['prueba_nombre'] }}</td>
                            <td>
                                {{-- La ruta de wire:model se ajusta a la nueva estructura --}}
                                <input 
                                    type="text" 
                                    x-mask:dynamic="/^-?[\d,]*(\.\d*)?$/.test($input) ? $money($input, '.', ',', 20) : ''" wire:model.defer="data.resultados_tabla.{{ $detalleId }}.pruebas.{{ $index }}.resultado"
                                    >
                            </td>
                            <td>
                                @if ($prueba['es_externo'])
                                    <input 
                                        type="text" 
                                        placeholder="Ingrese referencia externa"
                                        wire:model.defer="data.resultados_tabla.{{ $detalleId }}.pruebas.{{ $index }}.valor_referencia_externo">
                                @else
                                    {!! $prueba['valor_referencia_display'] !!}
                                @endif
                            </td>
                            <td>{!! $prueba['unidades_display'] !!}</td>
                            <td>{!! $prueba['nota_display'] !!}</td>
                            <td>
    {{-- El botón solo aparece si ya existe un resultado guardado --}}
    @if ($prueba['resultado_id'])
        <x-filament::icon-button
            color="danger"
            icon="heroicon-s-trash"
            label="Eliminar resultado"
            wire:click="deleteResultado({{ $prueba['resultado_id'] }})"
            wire:target="deleteResultado({{ $prueba['resultado_id'] }})"
            wire:confirm="¿Estás seguro de que quieres eliminar este resultado? Esta acción es irreversible."
        />
    @endif
</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
</div>