<?php

namespace App\ControlCalidad\Filament\Pages;

use App\ControlCalidad\Filament\Widgets\GraficoNivel;
use App\ControlCalidad\Models\Prueba;
use App\ControlCalidad\Models\Registro;
use App\Support\ControlCalidadPermissions;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class Resumen extends PaginaControlCalidad implements HasTable
{
    use InteractsWithTable;

    protected static ?string $title = 'Resumen';

    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.control-calidad.resumen';

    public static function permission(): string
    {
        return ControlCalidadPermissions::VIEW;
    }

    public function table(Table $table): Table
    {
        abort_unless(static::canAccess(), 403);

        return $table->heading('Últimos registros')
            ->query(Registro::query()->with(['area', 'laboratorista'])->withCount('resultados')->latest('fecha_hora')->limit(8))
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('fecha_hora')->label('Fecha y hora')->dateTime('d/m/Y H:i', 'America/El_Salvador'),
                Tables\Columns\TextColumn::make('area.nombre')->label('Área')->description(fn (Registro $record): string => $record->area?->equipo ?? ''),
                Tables\Columns\TextColumn::make('laboratorista.name')->label('Laboratorista')->wrap(),
                Tables\Columns\TextColumn::make('resultados_count')->label('Lecturas')->alignCenter(),
            ])
            ->emptyStateHeading('Todavía no hay registros')
            ->emptyStateDescription('Los controles guardados aparecerán aquí.');
    }

    public function getPruebaGrafico(): ?Prueba
    {
        if (! static::allowed(ControlCalidadPermissions::CHARTS)) {
            return null;
        }

        return Prueba::query()->whereHas('niveles')->with(['area', 'niveles'])
            ->orderByDesc('id')->first();
    }

    public function getChartWidgets(): array
    {
        $prueba = $this->getPruebaGrafico();
        if ($prueba === null) {
            return [];
        }

        return $prueba->niveles->sortBy('orden')->mapWithKeys(fn ($nivel): array => [$nivel->id => GraficoNivel::make([
            'nivelId' => (int) $nivel->id,
            'desde' => now('America/El_Salvador')->subDays(30)->toDateString(),
            'hasta' => now('America/El_Salvador')->toDateString(),
        ])])->all();
    }
}
