<?php

namespace App\Filament\Widgets;

use App\Models\DetalleOrden;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ExamenesPopularesChart extends ChartWidget
{
    public static function canView(): bool
    {
        return auth()->user()?->can('dashboard_examenes') ?? false;
    }

    public function mount(): void
    {
        abort_unless(static::canView(), 403);
        parent::mount();
    }

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
                    'backgroundColor' => 'rgb(' . config('ui.primary.500') . ')',
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
