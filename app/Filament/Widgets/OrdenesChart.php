<?php

namespace App\Filament\Widgets;

use App\Models\Orden;
use Filament\Widgets\ChartWidget;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OrdenesChart extends ChartWidget
{
    public static function canView(): bool
    {
        return auth()->user()?->can('dashboard_ordenes') ?? false;
    }

    public function mount(): void
    {
        abort_unless(static::canView(), 403);
        parent::mount();
    }

    protected static ?string $heading = 'Órdenes en los Últimos 7 Días';
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        $rawData = Orden::query()
            ->select(DB::raw('DATE(created_at) as fecha'), DB::raw('COUNT(*) as total'))
            ->whereBetween('created_at', [
                Carbon::now()->subDays(6)->startOfDay(),
                Carbon::now()->endOfDay()
            ])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('total', 'fecha');

        $labels = [];
        $values = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $labels[] = $date->format('d/m');
            $values[] = $rawData[$date->format('Y-m-d')] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'data' => $values,
                    'borderColor' => 'rgb(' . config('ui.primary.500') . ')',
                    'backgroundColor' => 'rgba(' . config('ui.primary.400') . ', 0.2)',
                    'fill' => true,
                    'tension' => 0.35,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
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
protected function getExtraAttributes(): array
{
    return [
        'style' => 'min-height: 420px;',
    ];
}

}
