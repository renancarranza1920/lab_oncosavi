<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
// 1. Importa TODOS los widgets
use App\Filament\Widgets\OrdenStatsWidget;
use App\Filament\Widgets\OrdenesChart;
use App\Filament\Widgets\UltimasOrdenesWidget;
use App\Filament\Widgets\EstadosOrdenesChart;   // <-- Nuevo
use App\Filament\Widgets\ExamenesPopularesChart; // <-- Nuevo

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Escritorio';
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?int $navigationSort = -2;

    public function getWidgets(): array
    {
        return [
            // 2. Añade TODOS a la lista
            OrdenStatsWidget::class,
            OrdenesChart::class,
            EstadosOrdenesChart::class,   // <-- Nuevo
            ExamenesPopularesChart::class, // <-- Nuevo
            UltimasOrdenesWidget::class,
        ];
    }

    // --- ¡NUEVO MÉTODO PARA EL DISEÑO! ---
    // Esto organiza tus widgets en columnas
    public function getColumns(): int | string | array
    {
        return 2; // Un diseño de 2 columnas
    }
}