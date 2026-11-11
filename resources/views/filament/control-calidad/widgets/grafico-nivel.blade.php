@php
    $heading = $this->getHeading();
@endphp

<x-filament-widgets::widget class="fi-wi-chart">
    <div
        x-data="{
            ampliado: false,
            async ampliar() {
                this.ampliado = true;
                try {
                    await this.$refs.panel.requestFullscreen?.();
                } catch (error) {
                    // Se mantiene la vista ampliada cuando el navegador no permite pantalla completa.
                }
                this.$nextTick(() => window.dispatchEvent(new Event('resize')));
            },
            async cerrar() {
                if (document.fullscreenElement === this.$refs.panel) {
                    await document.exitFullscreen();
                }
                this.ampliado = false;
                this.$nextTick(() => window.dispatchEvent(new Event('resize')));
            },
        }"
        x-ref="panel"
        x-trap.inert.noscroll="ampliado"
        x-on:keydown.escape.window="if (ampliado) cerrar()"
        x-on:fullscreenchange.document="if (document.fullscreenElement !== $refs.panel) ampliado = false; $nextTick(() => window.dispatchEvent(new Event('resize')))"
        x-bind:role="ampliado ? 'dialog' : null"
        x-bind:aria-modal="ampliado ? 'true' : null"
        aria-label="Gráfico de {{ $heading }}"
        x-bind:style="ampliado ? 'position: fixed; inset: 0; z-index: 100; padding: 1rem; overflow: auto;' : ''"
        class="bg-white dark:bg-gray-900"
    >
        <x-filament::section :heading="$heading" :description="$this->getDescription()">
            <x-slot name="headerEnd">
                @if(\App\ControlCalidad\Filament\Pages\PaginaControlCalidad::allowed(\App\Support\ControlCalidadPermissions::EXPORT))
                    <x-filament::button tag="a" color="gray" outlined size="sm" target="_blank" rel="noopener"
                        :href="route('cci.exportar', ['tipo' => 'nivel', 'nivel_id' => $nivelId, 'desde' => $desde, 'hasta' => $hasta, 'incluir_graficos' => 1])"
                        aria-label="Exportar PDF del gráfico de {{ $heading }}" style="min-height: 44px"
                    >PDF</x-filament::button>
                @endif
                <x-filament::button
                    type="button" color="gray" outlined size="sm"
                    icon="heroicon-o-arrows-pointing-out"
                    x-show="! ampliado" x-on:click="ampliar()"
                    aria-label="Ampliar gráfico de {{ $heading }}" style="min-height: 44px"
                >
                    Ampliar
                </x-filament::button>
                <x-filament::button
                    type="button" color="gray" outlined size="sm"
                    icon="heroicon-o-arrows-pointing-in"
                    x-show="ampliado" x-cloak x-on:click="cerrar()"
                    aria-label="Salir de pantalla completa" style="min-height: 44px"
                >
                    Cerrar
                </x-filament::button>
            </x-slot>

            <div
                x-load
                x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('chart', 'filament/widgets') }}"
                wire:ignore
                x-data="chart({
                    cachedData: @js($this->getCachedData()),
                    options: @js($this->getOptions()),
                    type: @js($this->getType()),
                })"
                x-bind:style="ampliado ? 'position: relative; height: calc(100dvh - 13rem); min-height: 200px;' : 'position: relative; height: 300px;'"
            >
                <canvas x-ref="canvas" aria-label="Mediciones, mínimo, máximo y valor esperado" role="img"></canvas>
                <span x-ref="backgroundColorElement" class="text-custom-50 dark:text-custom-400/10"
                    @style([\Filament\Support\get_color_css_variables($this->getColor(), shades: [50, 400], alias: 'widgets::chart-widget.background')])
                ></span>
                <span x-ref="borderColorElement" class="text-custom-500 dark:text-custom-400"
                    @style([\Filament\Support\get_color_css_variables($this->getColor(), shades: [400, 500], alias: 'widgets::chart-widget.border')])
                ></span>
                <span x-ref="gridColorElement" class="text-gray-200 dark:text-gray-800"></span>
                <span x-ref="textColorElement" class="text-gray-500 dark:text-gray-400"></span>
            </div>
        </x-filament::section>
    </div>
</x-filament-widgets::widget>
