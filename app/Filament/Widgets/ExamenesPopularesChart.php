<?php

namespace App\Filament\Widgets;

use App\Models\DetalleOrden;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ExamenesPopularesChart extends ChartWidget
{
    protected static ?string $heading = 'Top 5 Exámenes Más Solicitados';
    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        $data = DetalleOrden::query()
            ->join('examens', 'detalle_orden.examen_id', '=', 'examens.id')
            ->select('examens.nombre', DB::raw('COUNT(*) as total'))
            ->groupBy('examens.nombre')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return [
            'datasets' => [
                [
                    'data' => $data->pluck('total')->toArray(),
                    'backgroundColor' => '#A5D1DF',
                    'borderRadius' => 6,
                ],
            ],
            'labels' => $data->pluck('nombre')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
    protected function getOptions(): array
{
    return [
        
        'plugins' => [
            'legend' => [
                'display' => false,
            ],
        ],
    ];
}


}
