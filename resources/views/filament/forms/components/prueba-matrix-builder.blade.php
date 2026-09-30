{{-- Ya no necesitamos el bloque @php, porque las variables vienen del Placeholder --}}

<div class="pt-4">
    {{-- La vista ahora es más simple y solo usa las variables que le pasamos --}}
    @if ($filas > 0 && $columnas > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <!-- Esquina vacía -->
                        </th>
                        @for ($c = 1; $c <= $columnas; $c++)
                            <th class="px-4 py-2">
                                <x-filament::input.wrapper>
                                    <x-filament::input
                                        type="text"
                                        placeholder="Columna {{ $c }}"
                                        wire:model.live.debounce.500ms="data.nombres_columnas.{{ $c }}" 
                                    />
                                </x-filament::input.wrapper>
                            </th>
                        @endfor
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                    @for ($f = 1; $f <= $filas; $f++)
                        <tr>
                            <td class="px-4 py-2 font-medium">
                                <x-filament::input.wrapper>
                                    <x-filament::input
                                        type="text"
                                        placeholder="Fila {{ $f }}"
                                        wire:model.live.debounce.500ms="data.nombres_filas.{{ $f }}"
                                    />
                                </x-filament::input.wrapper>
                            </td>
                            @for ($c = 1; $c <= $columnas; $c++)
                                <td class="px-4 py-2 text-center text-sm text-gray-400 bg-gray-100 dark:bg-white/5 rounded-md">
                                    (Celda de Resultado)
                                </td>
                            @endfor
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>
    @else
        <p class="text-center text-gray-500 dark:text-gray-400 py-4">
            Selecciona un número de filas y columnas mayor que cero.
        </p>
    @endif
</div>