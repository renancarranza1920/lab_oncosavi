<?php

namespace App\Filament\Resources\ExamenResource\RelationManagers;

use App\Filament\Resources\PruebaResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PruebasRelationManager extends RelationManager
{
    protected static string $relationship = 'pruebas';

    protected static ?string $recordTitleAttribute = 'nombre';
    protected static ?string $modelLabel = 'Prueba';
    protected static ?string $pluralModelLabel = 'Pruebas';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nombre')
                    ->required()
                    ->maxLength(255),
                // El examen_id se asigna automáticamente
                Forms\Components\Select::make('tipo_prueba_id')
                    ->label('Tipo de Prueba')
                    ->relationship('tipoPrueba', 'nombre')
                    ->createOptionForm([
                        Forms\Components\TextInput::make('nombre')
                            ->label('Nombre del Tipo de Prueba')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->searchable()->preload()->helperText('Opcional.'),

                PruebaResource::valoresReferenciaSection(),
            ]);

    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')->searchable(),
                Tables\Columns\TextColumn::make('tipoPrueba.nombre')->label('Tipo de Prueba')->searchable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
