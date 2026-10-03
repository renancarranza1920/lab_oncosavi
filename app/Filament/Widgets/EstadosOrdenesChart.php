<?php

namespace App\Filament\Widgets;

use App\Models\Orden;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class EstadosOrdenesChart extends ChartWidget
{
    public static function canView(): bool
    {
        return auth()->user()?->can('dashboard_estados') ?? false;
    }

    public function mount(): void
    {
        abort_unless(static::canView(), 403);
        parent::mount();
    }

    protected static ?string $heading = 'Distribución de Estados de Órdenes';
    protected static ?int $sort = 1;
    protected static ?string $maxHeight = '300px';

    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        $data = Orden::query()
            ->select('estado', DB::raw('COUNT(*) as total'))
            ->groupBy('estado')
            ->get();


        return [
            'datasets' => [
                [
                    'data' => $data->pluck('total')->toArray(),
                    'backgroundColor' => $data->pluck('estado')->map(
                        fn ($estado) => config('ui.charts.states.' . mb_strtolower(trim(str_replace('_', ' ', $estado ?? ''))), config('ui.charts.fallback'))
                    )->toArray(),
                    'borderColor' => '#FFFFFF',
                    'borderWidth' => 3,
                    'hoverOffset' => 6,
                ],
            ],
            'labels' => $data->pluck('estado')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => ['x' => ['display' => false], 'y' => ['display' => false]],
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => [
                        'usePointStyle' => true,
                        'pointStyle' => 'circle',
                        'padding' => 18,
                        'boxWidth' => 10,
                        'boxHeight' => 10,
                    ],
                ],
            ],
            'cutout' => '70%',
        ];
    }
}
