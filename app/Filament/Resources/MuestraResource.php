<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MuestraResource\Pages;
use App\Filament\Resources\ExamenResource;
use App\Models\Muestra;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MuestraResource extends Resource
{
    protected static ?string $model = Muestra::class;

    // Esta línea oculta el resource del menú de navegación.
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';
    protected static ?string $navigationLabel = 'Muestras Biológicas';
    protected static ?string $pluralModelLabel = 'Muestras Biológicas';
    protected static ?string $modelLabel = 'Muestra Biológica';
    protected static ?string $navigationGroup = 'Catálogos y Ajustes';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nombre')
                    ->label('Nombre de la Muestra Biológica')
                    ->placeholder('Ej: Sangre, Orina, Suero, Plasma, Hisopo...')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')->searchable()->sortable(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('gestionar_examenes')
                    ->label('Volver a Exámenes')
                    // Usamos ExamenResource::getUrl('index') para la redirección
                    ->url(ExamenResource::getUrl('index'))
                    ->icon('heroicon-o-arrow-left') // Icono de vuelta
                    ->color('gray'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMuestras::route('/'),
        ];
    }
}