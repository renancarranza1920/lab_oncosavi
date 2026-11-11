<x-filament-panels::page>
    <x-filament::tabs aria-label="Mantenimiento del Control de Calidad Interno">
        @foreach ($this->secciones() as $key => $label)
            <x-filament::tabs.item
                :active="$seccion === $key"
                wire:click="cambiarSeccion('{{ $key }}')"
                wire:loading.attr="disabled"
            >
                {{ $label }}
            </x-filament::tabs.item>
        @endforeach
    </x-filament::tabs>

    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $this->descripcion() }}</p>

    {{ $this->table }}
</x-filament-panels::page>
