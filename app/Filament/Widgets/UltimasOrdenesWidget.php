<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\DetalleOrdenKanban;
use App\Filament\Resources\OrdenResource;
use App\Models\Orden;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;

class UltimasOrdenesWidget extends BaseWidget
{
    protected static ?string $heading = 'Últimas Órdenes Pendientes o en Proceso';

    // 🔢 Orden visual (fila 3 izquierda)
    protected static ?int $sort = 3;

    // 📐 SOLO una columna (para que no baje el Top 5)
    protected int | string | array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Orden::query()
                    ->whereIn('estado', ['pendiente', 'en proceso'])
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('cliente.nombre')
                    ->label('Cliente')
                    ->formatStateUsing(
                        fn (Orden $record) => "{$record->cliente->nombre} {$record->cliente->apellido}"
                    )
                    ->description(fn (Orden $record) => 'DUI: ' . ($record->cliente->dui ?? 'N/A'))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        $dui = preg_replace('/\D/', '', $search);

                        return $query->whereHas('cliente', function (Builder $query) use ($search, $dui) {
                            $query->where('nombre', 'like', "%{$search}%")
                                ->orWhere('apellido', 'like', "%{$search}%")
                                ->orWhere('dui', 'like', "%{$search}%")
                                ->when($dui !== '', fn (Builder $query) => $query->orWhereRaw("REPLACE(dui, '-', '') like ?", ["%{$dui}%"]));
                        });
                    }),

                Tables\Columns\TextColumn::make('estado')
                    ->badge()
                    ->color(fn ($state) => \App\Support\EstadoVisual::color($state)),

               Tables\Columns\TextColumn::make('created_at')
    ->label('Fecha de Creación')
    ->since() // sigue mostrando "hace X tiempo"
    ->sortable()
    ->tooltip(
        fn (Orden $record) =>
            $record->created_at->format('d/m/Y H:i:s')
    ),

            ])
            ->actions([
                Action::make('etiquetas')
                    ->label('Etiquetas')
                    ->icon('heroicon-o-ticket')
                    ->color('gray')
                    ->url(fn (Orden $record) => DetalleOrdenKanban::getUrl([
                        'ordenId' => $record->id,
                    ]))
                    ->openUrlInNewTab(),

                Action::make('resultados')
                    ->label('Resultados')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->visible(fn (Orden $record): bool => $record->estado === 'en proceso')
                    ->url(fn (Orden $record) => OrdenResource::getUrl(
                        'ingresar-resultados',
                        ['record' => $record]
                    )),
            ]);
    }
}
