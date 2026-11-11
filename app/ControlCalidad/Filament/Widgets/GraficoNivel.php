<?php

namespace App\ControlCalidad\Filament\Widgets;

use App\ControlCalidad\Filament\Pages\PaginaControlCalidad;
use App\ControlCalidad\Models\Nivel;
use App\Support\ControlCalidadPermissions;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Locked;

class GraficoNivel extends ChartWidget
{
    #[Locked]
    public int $nivelId = 0;

    #[Locked]
    public ?string $desde = null;

    #[Locked]
    public ?string $hasta = null;

    protected static bool $isLazy = false;

    protected static ?string $pollingInterval = null;

    protected static ?string $maxHeight = '300px';

    protected static string $view = 'filament.control-calidad.widgets.grafico-nivel';

    public static function canView(): bool
    {
        return PaginaControlCalidad::allowed(ControlCalidadPermissions::CHARTS);
    }

    public function mount(): void
    {
        abort_unless(static::canView(), 403);
        Validator::make(['desde' => $this->desde, 'hasta' => $this->hasta], [
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', ...($this->desde ? ['after_or_equal:desde'] : [])],
        ])->validate();
        parent::mount();
    }

    protected function getType(): string
    {
        return 'line';
    }

    public function getHeading(): string
    {
        abort_unless(static::canView(), 403);

        return Nivel::findOrFail($this->nivelId)->nombre;
    }

    public function getDescription(): string
    {
        abort_unless(static::canView(), 403);
        $nivel = Nivel::with('prueba')->findOrFail($this->nivelId);
        $unidades = collect($this->getCachedData()['datasets'])->pluck('unidad')->unique()->implode(', ');

        return $nivel->prueba->nombre.($unidades !== '' ? ' · '.$unidades : '');
    }

    protected function getData(): array
    {
        abort_unless(static::canView(), 403);

        return app(\App\ControlCalidad\Services\DatosGraficoControlCalidad::class)
            ->obtener($this->nivelId, $this->desde, $this->hasta);
    }

    protected function getOptions(): array
    {
        $scales = ['x' => ['ticks' => ['maxTicksLimit' => 6]]];
        foreach ($this->getCachedData()['datasets'] as $dataset) {
            if (isset($scales[$dataset['yAxisID']])) {
                continue;
            }
            $scales[$dataset['yAxisID']] = [
                'type' => 'linear',
                'position' => count($scales) % 2 ? 'left' : 'right',
                'beginAtZero' => false,
                'title' => ['display' => true, 'text' => $dataset['unidad']],
                'grid' => ['drawOnChartArea' => count($scales) === 1],
            ];
        }

        return [
            'maintainAspectRatio' => false,
            'interaction' => ['mode' => 'index', 'intersect' => false],
            'plugins' => ['legend' => ['position' => 'bottom', 'labels' => ['boxWidth' => 12]]],
            'scales' => $scales,
        ];
    }
}
