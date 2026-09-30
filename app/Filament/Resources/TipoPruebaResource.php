<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TipoPruebaResource\Pages;
use App\Models\TipoPrueba;
use Filament\Forms;
use Filament\Forms\Components\Card;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TipoPruebaResource extends Resource
{
    protected static ?string $model = TipoPrueba::class;

    // 👇 ***** 1. OCULTAMOS EL RECURSO DEL MENÚ ***** 👇
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $modelLabel = 'Tipo de Prueba';
    protected static ?string $pluralModelLabel = 'Tipos de Pruebas';
    protected static ?string $navigationGroup = 'Catálogos de Laboratorio';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Card::make()->schema([
                Forms\Components\TextInput::make('nombre')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')->searchable()->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                
            ]);
    }
    
    public static function getRelations(): array
    {
        return [];
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTipoPruebas::route('/'),
            'create' => Pages\CreateTipoPrueba::route('/create'),
            'edit' => Pages\EditTipoPrueba::route('/{record}/edit'),
        ];
    }    
}