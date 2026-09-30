<?php

namespace App\Filament\Widgets;

use App\Models\Orden;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class EstadosOrdenesChart extends ChartWidget
{
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
                        fn ($estado) => \App\Support\EstadoVisual::fondo($estado)
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
