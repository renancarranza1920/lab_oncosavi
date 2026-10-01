<div
    id="{{ $record->getKey() }}"
    wire:click="recordClicked('{{ $record->getKey() }}', {{ @json_encode($record) }})"
    class="record bg-white dark:bg-gray-700 rounded-lg px-3 py-2 cursor-grab font-medium text-sm text-gray-700 dark:text-gray-200 shadow-sm border border-gray-200 dark:border-gray-600"
    @if($record->timestamps && now()->diffInSeconds($record->{$record::UPDATED_AT}, true) < 3)
        x-data
        x-init="
            $el.classList.add('animate-pulse-twice', 'bg-primary-100', 'dark:bg-primary-800')
            $el.classList.remove('bg-white', 'dark:bg-gray-700')
            setTimeout(() => {
                $el.classList.remove('bg-primary-100', 'dark:bg-primary-800')
                $el.classList.add('bg-white', 'dark:bg-gray-700')
            }, 3000)
        "
    @endif
>
    {{ $record->{static::$recordTitleAttribute} }}

    <!-- Botón imprimir individual usando Livewire -->
    <x-filament::icon-button
        wire:click.stop="printSingle({{ $record->getKey() }})"
        color="gray"
        icon="heroicon-o-printer"
        label="Imprimir etiqueta"
        size="sm"
        class="ml-2"
    />
</div>
