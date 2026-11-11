<x-filament-panels::page>
    {{ $this->table }}

    @if(\App\ControlCalidad\Filament\Pages\Resumen::allowed(\App\Support\ControlCalidadPermissions::CHARTS))
        @php($prueba = $this->getPruebaGrafico())
        @if($prueba)
            <x-filament::section :heading="'Gráficos · '.$prueba->nombre" :description="$prueba->area->nombre.' · '.$prueba->area->equipo.' · Últimos 30 días'">
                <x-filament-widgets::widgets :widgets="$this->getChartWidgets()" :columns="['default' => 1, 'lg' => 2, '2xl' => 3]" />
            </x-filament::section>
        @else
            <x-filament::section heading="Gráficos">
                <p class="text-sm text-gray-600 dark:text-gray-400">Configura las pruebas y sus niveles para consultar los gráficos.</p>
                @if(\App\ControlCalidad\Filament\Pages\Resumen::allowed(\App\Support\ControlCalidadPermissions::MAINTAIN))
                    <x-filament::link :href="\App\ControlCalidad\Filament\Pages\Mantenimiento::getUrl(panel: 'control-calidad')">Ir a Mantenimiento</x-filament::link>
                @endif
            </x-filament::section>
        @endif
    @endif
</x-filament-panels::page>
