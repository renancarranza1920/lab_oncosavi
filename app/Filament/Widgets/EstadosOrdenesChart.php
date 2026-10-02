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
                        fn ($estado) => config('estados.' . \App\Support\EstadoVisual::color($estado) . '.base')
                    )->toArray(),
                    'borderWidth' => 0,
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
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => [
                        'usePointStyle' => true,
                    ],
                ],
            ],
            'cutout' => '65%',
        ];
    }
}
