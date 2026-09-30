<x-filament-panels::page>
    <form wire:submit="save">
        {{-- Esto renderizará el formulario definido en el método form() de la clase PHP --}}
        {{ $this->form }}

        <div class="mt-6">
            <x-filament-panels::form.actions 
                :actions="$this->getFormActions()"
            /> 
        </div>
    </form>
</x-filament-panels::page>
