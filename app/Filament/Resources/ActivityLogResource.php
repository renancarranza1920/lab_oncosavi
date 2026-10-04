<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use Filament\Infolists\Components\TextEntry;
use Spatie\Activitylog\Models\Activity;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\KeyValueEntry;
use Carbon\Carbon; // <-- ¡Asegúrate de importar Carbon!

class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'Administración';
    protected static ?string $modelLabel = 'Bitácora';
    protected static ?string $pluralModelLabel = 'Bitácora';

protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        if (!auth()->user()->can('ver_bitacora_completa')) {
            $query->where('subject_type', \App\Models\Resultado::class);
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha y Hora')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->toggleable()
                    ->timezone('America/El_Salvador'), // <-- Hora local
                
                Tables\Columns\TextColumn::make('log_name')
                    ->label('Módulo')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Acción Realizada')
                    ->searchable()
                    ->limit(50) // <--- Muestra solo 50 caracteres y agrega "..." al final
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        // Opcional: Muestra el texto completo al pasar el mouse por encima
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),
                Tables\Columns\TextColumn::make('causer.name')
                    ->label('Usuario')
                    ->searchable()
                    ->sortable(),

                // --- ¡COLUMNA DE CAMBIOS! ---
                Tables\Columns\TextColumn::make('properties.old')
                    ->label('Campos Afectados')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(function ($record) {
                        if ($record->event === \App\Models\Actividad::EVENTO_SOPORTE) {
                            return 'Reservado a soporte';
                        }
                        if ($record->event === 'created' || $record->event === 'deleted') {
                            return 'N/A';
                        }
                        $oldValues = \App\Support\Bitacora::datosVisibles($record->properties->get('old', []));
                        if (empty($oldValues)) {
                            return 'Ninguno';
                        }
                        
                        return implode(', ', array_keys($oldValues));
                    }),
                
                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Modelo')
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : 'Reservado a soporte')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('log_name')
                    ->label('Módulo')
                    ->options(fn () => static::getEloquentQuery()->select('log_name')->distinct()->pluck('log_name', 'log_name')),
                SelectFilter::make('causer_id')->label('Usuario')
                    ->options(fn () => \App\Models\User::whereIn('id', static::getEloquentQuery()->select('causer_id'))
                        ->pluck('name', 'id')),
                SelectFilter::make('event')->label('Acción')
                    ->options(['created' => 'Creación', 'updated' => 'Edición', 'deleted' => 'Eliminación', 'consulted' => 'Consulta de informe', \App\Models\Actividad::EVENTO_SOPORTE => 'Ajuste de soporte técnico']),
                Tables\Filters\Filter::make('fecha')->form([
                    Forms\Components\DatePicker::make('desde')->label('Desde'),
                    Forms\Components\DatePicker::make('hasta')->label('Hasta'),
                ])->query(fn (Builder $query, array $data) => $query
                    ->when($data['desde'] ?? null, fn ($q, $fecha) => $q->where('created_at', '>=', \Carbon\Carbon::parse($fecha, 'America/El_Salvador')->startOfDay()->utc()))
                    ->when($data['hasta'] ?? null, fn ($q, $fecha) => $q->where('created_at', '<=', \Carbon\Carbon::parse($fecha, 'America/El_Salvador')->endOfDay()->utc())))
            ])
             ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Detalles del Registro de Bitácora')
                    ->infolist(fn (Infolist $infolist): Infolist => $infolist
                        ->schema([
                            Section::make('Detalles de la Actividad')
                                ->columns(3)
                                ->schema([
                                    TextEntry::make('description')->label('Acción'),
                                    TextEntry::make('causer.name')->label('Usuario'),
                                    
                                    // --- ¡LA CORRECCIÓN DEFINITIVA! ---
                                    // Usamos TextEntry, que SÍ tiene ->datetime()
                                    TextEntry::make('created_at')
                                        ->label('Fecha')
                                        ->datetime('d/m/Y H:i:s')
                                        ->timezone('America/El_Salvador'),
                                ]),
                            Section::make('Datos Modificados (Diff)')
                                ->description('Muestra los valores antiguos y nuevos.')
                                ->visible(fn ($record) => $record->properties->has('old') || $record->properties->has('attributes'))
                                ->columns(2)
                                ->schema([
                                    // Usamos KeyValueEntry aquí porque los datos SÍ son un array
                                    KeyValueEntry::make('properties.old')
                                        ->state(fn ($record) => \App\Support\Bitacora::datosVisibles($record->properties->get('old', [])))
                                        ->label('Valores Anteriores')
                                        ->visible(fn ($record) => $record->properties->has('old')),
                                    KeyValueEntry::make('properties.attributes')
                                        ->state(fn ($record) => \App\Support\Bitacora::datosVisibles($record->properties->get('attributes', [])))
                                        ->label('Valores Nuevos')
                                        ->visible(fn ($record) => $record->properties->has('attributes')),
                                ]),
                            Section::make('Informe consultado')
                                ->visible(fn ($record) => $record->event === 'consulted')
                                ->schema([
                                    KeyValueEntry::make('consulta')->label('Detalle de consulta')
                                        ->state(fn ($record) => [
                                            'Informe' => \Illuminate\Support\Str::headline($record->properties->get('informe', '')),
                                            'Desde' => $record->properties->get('desde'),
                                            'Hasta' => $record->properties->get('hasta'),
                                            'Estado' => $record->properties->get('estado'),
                                            'Filas' => $record->properties->get('filas'),
                                        ]),
                                ])
                        ])
                    ),
            ])
            ->bulkActions([]);
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
        ];
    }

    public static function canCreate(): bool { return false; }
    public static function canEdit(Model $record): bool { return false; }
}
