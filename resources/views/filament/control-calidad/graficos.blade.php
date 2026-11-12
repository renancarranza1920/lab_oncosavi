<x-filament-panels::page>
    <form wire:submit="aplicarFiltros" class="space-y-4">
        {{ $this->form }}
        <x-filament::button type="submit" icon="heroicon-o-funnel" wire:loading.attr="disabled" wire:target="aplicarFiltros">Ver gráficos</x-filament::button>
    </form>

    @if($this->getNiveles()->isNotEmpty())
        <x-filament-widgets::widgets :widgets="$this->getChartWidgets()" :columns="['default' => 1, 'lg' => 2, '2xl' => 3]" />
    @else
        <x-filament::section heading="No hay niveles configurados">
            <p class="text-sm text-gray-600 dark:text-gray-400">Selecciona una prueba con niveles de control para consultar sus gráficos.</p>
            @if(\App\ControlCalidad\Filament\Pages\Graficos::allowed(\App\Support\ControlCalidadPermissions::MAINTAIN))
                <x-filament::link :href="\App\ControlCalidad\Filament\Pages\Mantenimiento::getUrl(panel: 'admin')">Ir a Mantenimiento</x-filament::link>
            @endif
        </x-filament::section>
    @endif
</x-filament-panels::page>
