<h3 class="mb-2 px-4 py-2 flex items-center justify-between font-semibold text-md text-gray-900 dark:text-gray-100 bg-gray-50 dark:bg-gray-900 rounded-lg shadow-sm dark:border dark:border-gray-600">
    <div class="flex items-center">
        <span class="text-gray-500 dark:text-gray-400 mr-2">❖</span>
        <span>{{ $status['title'] }}</span>
        <span class="text-xs font-medium ml-3 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 px-2 py-0.5 rounded-full shadow-sm dark:shadow-[0_2px_4px_rgba(255,255,255,0.1)]">
            {{ count($status['records'] ?? []) }}
        </span>
    </div>

    <!-- Botón imprimir grupo -->
    @if (config('laboratorio.impresion_etiquetas_habilitada'))
    <x-filament::icon-button
        wire:click="printGroup('{{ $status['id'] }}')"
        color="gray"
        icon="heroicon-o-printer"
        label="Imprimir todas las etiquetas de este grupo"
        :disabled="count($status['records'] ?? []) === 0"
    />
    @endif
</h3>
