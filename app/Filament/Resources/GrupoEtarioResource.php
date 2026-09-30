<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GrupoEtarioResource\Pages;
use App\Models\GrupoEtario;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification; // <-- Importante para las notificaciones

class GrupoEtarioResource extends Resource
{
    protected static ?string $model = GrupoEtario::class;

    // Icono que coincide con el tema de usuarios
    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $modelLabel = 'Grupo Etario';
    protected static ?string $pluralModelLabel = 'Grupos Etarios';

    protected static ?string $navigationGroup = 'Catálogos y Ajustes';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Section::make('Detalles del Grupo Etario')
                            ->schema([
                                Forms\Components\TextInput::make('nombre')
                                    ->required()
                                    ->maxLength(255)
                                    ->label('Nombre del Grupo')
                                    ->unique(ignoreRecord: true), // Asegurar nombre único

                                Forms\Components\Select::make('genero')
                                    ->label('Género Aplicable')
                                    ->options(['Masculino' => 'Masculino', 'Femenino' => 'Femenino', 'Ambos' => 'Ambos'])
                                    ->default('Ambos')
                                    ->required(),
                                
                                Forms\Components\Grid::make(3) // Usamos 3 columnas para el rango
                                    ->schema([
                                        Forms\Components\TextInput::make('edad_min')
                                            ->label('Edad Mínima')
                                            ->numeric()
                                            ->minValue(0)
                                            ->required(),

                                        Forms\Components\TextInput::make('edad_max')
                                            ->label('Edad Máxima')
                                            ->numeric()
                                            ->minValue(0)
                                            ->required(),
                                        
                                        Forms\Components\Select::make('unidad_tiempo')
                                            ->label('Unidad')
                                            ->options([
                                                'días' => 'Días', 
                                                'semanas' => 'Semanas', 
                                                'meses' => 'Meses', 
                                                'años' => 'Años'
                                            ])
                                            ->required(),
                                    ]),
                                
                            ])->columns(2),
                    ])->columns(1), // El Card tiene 1 columna principal
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')->searchable()->sortable(),
                
                Tables\Columns\TextColumn::make('rango_edad')
                    ->label('Rango')
                    ->getStateUsing(fn (GrupoEtario $record) => 
                        // Muestra el rango completo (ej: 18 - 64 años)
                        "{$record->edad_min} - {$record->edad_max} {$record->unidad_tiempo}"
                    )
                    ->sortable(['edad_min']),
                    
                Tables\Columns\TextColumn::make('genero')->searchable()->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Masculino' => 'info',
                        'Femenino' => 'danger',
                        'Ambos' => 'success',
                    }),

                Tables\Columns\TextColumn::make('estado')->label('Estado')
                    ->formatStateUsing(fn (int $state): string => $state === 1 ? 'Activo' : 'Inactivo')
                    ->badge()
                    ->color(fn (int $state): string => $state === 1 ? 'success' : 'danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y')
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->filters([
                Tables\Filters\SelectFilter::make('unidad_tiempo')
                    ->options([
                        'días' => 'Días', 
                        'semanas' => 'Semanas', 
                        'meses' => 'Meses', 
                        'años' => 'Años'
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                
                // Cambiar estado con confirmación y notificación
                Tables\Actions\Action::make('toggleEstado')
                    ->label(fn (GrupoEtario $record) => $record->estado === 1 ? 'Dar de baja' : 'Dar de alta')
                    ->color(fn (GrupoEtario $record) => $record->estado === 1 ? 'danger' : 'success')
                    ->icon(fn (GrupoEtario $record) => $record->estado === 1 ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->visible( auth()->user()->can('cambiar_estado_grupos'))
                    ->requiresConfirmation() // <--- Pide confirmación antes de ejecutar
                    ->modalHeading(fn (GrupoEtario $record) => $record->estado === 1 ? '¿Desactivar Grupo Etario?' : '¿Activar Grupo Etario?')
                    ->modalDescription(fn (GrupoEtario $record) => $record->estado === 1 
                        ? 'El grupo dejará de estar disponible para nuevos registros.' 
                        : 'El grupo volverá a estar activo en el sistema.')
                    ->action(function (GrupoEtario $record) {
                        $nuevoEstado = $record->estado === 1 ? 0 : 1;
                        $record->estado = $nuevoEstado;
                        $record->save();

                        Notification::make()
                            ->title('Estado actualizado')
                            ->body('El grupo etario ha sido ' . ($nuevoEstado === 1 ? 'activado' : 'dado de baja') . ' correctamente.')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
             
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGrupoEtarios::route('/'),
            'create' => Pages\CreateGrupoEtario::route('/create'),
            'edit' => Pages\EditGrupoEtario::route('/{record}/edit'),
        ];
    }
}