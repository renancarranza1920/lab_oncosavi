<x-filament-panels::page>
    <form wire:submit="guardar" class="space-y-6">
        {{ $this->form }}
        <x-filament::button type="submit" wire:loading.attr="disabled" wire:target="guardar">
            Guardar registro
        </x-filament::button>
    </form>

    @if($fechaGuardada !== null)
        <x-filament::section heading="Fecha y hora del registro guardado">
            {{ $fechaGuardada }}
        </x-filament::section>
    @endif

    @if($this->mostrarRegistros())
        {{ $this->table }}
    @endif
</x-filament-panels::page>
