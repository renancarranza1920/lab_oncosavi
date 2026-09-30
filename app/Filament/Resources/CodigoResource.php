<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CodigoResource\Pages;
use App\Models\Codigo;
use Filament\Forms;
use Filament\Forms\Components\Card;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Get;
use Filament\Forms\Set;

class CodigoResource extends Resource
{
    protected static ?string $model = Codigo::class;
    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';
    protected static ?string $navigationLabel = 'Cupones de Descuento';
    protected static ?string $pluralModelLabel = 'Cupones';
    protected static ?string $modelLabel = 'Cupón';
    protected static ?string $navigationGroup = 'Catálogos y Ajustes';
protected static ?int $navigationSort = 3;

  public static function form(Form $form): Form
    {
        return $form->schema([
            Card::make()
                ->schema([
            Forms\Components\Section::make('Información del Cupón')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('codigo')
                        ->required()
                        
                        ->unique(ignoreRecord: true)
                        ->maxLength(50)
                        ->label('Código'),

                    Forms\Components\Select::make('tipo_descuento')
                        ->label('Tipo de descuento')
                        ->options(['porcentaje' => 'Porcentaje (%)', 'monto' => 'Monto fijo ($)'])
                        ->required()
                        ->default('porcentaje'),

                    Forms\Components\TextInput::make('valor_descuento')
                        ->numeric()
                        ->label('Valor del descuento')
                        ->required(),
                ]),
            
            Forms\Components\Section::make('Restricciones y Límites')
                ->schema([
                    // El toggle ahora se enlaza directamente a la columna de la base de datos
                    Forms\Components\Toggle::make('es_limitado')
                        ->label('¿Establecer un límite de usos?')
                        ->live(),

                    Forms\Components\TextInput::make('limite_usos')
                        ->numeric()->minValue(1)
                        ->label('Número máximo de usos')
                        ->requiredIf('es_limitado', true)
                        ->visible(fn (Get $get) => $get('es_limitado')),

                    // Igual aquí, se enlaza a la nueva columna 'tiene_vencimiento'
                    Forms\Components\Toggle::make('tiene_vencimiento')
                        ->label('¿Establecer una fecha de vencimiento?')
                        ->live(),

                    Forms\Components\DatePicker::make('fecha_vencimiento')
                        ->label('Fecha de vencimiento')
                        ->minDate(now())
                        ->requiredIf('tiene_vencimiento', true)
                        ->visible(fn (Get $get) => $get('tiene_vencimiento')),
                ]),
                ]),
        ]);
    }
    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('codigo')->searchable()->label('Código'),
            Tables\Columns\TextColumn::make('tipo_descuento')->label('Tipo'),
            Tables\Columns\TextColumn::make('valor_descuento')
                ->label('Valor')
                ->formatStateUsing(function ($state, Codigo $record) {
                    if ($record->tipo_descuento === 'porcentaje') return $state . '%';
                    if ($record->tipo_descuento === 'monto') return '$' . number_format($state, 2);
                    return $state;
                }),
            
           Tables\Columns\TextColumn::make('limite_usos')
                ->label('Usos / Límite')
                ->formatStateUsing(function (Codigo $record): string {
                    // La tabla ahora lee la bandera 'es_limitado'
                    if (!$record->es_limitado) {
                        return 'Ilimitado';
                    }
                    return $record->usos_actuales . ' / ' . ($record->limite_usos ?? 'N/A');
                }),

            Tables\Columns\IconColumn::make('tiene_vencimiento')
                ->label('Vence')
                ->boolean(),
            Tables\Columns\TextColumn::make('estado')
                ->badge()
                ->color(fn($state) => match ($state) {
                    'Activo' => 'success',
                    default => 'gray',
                }),
        ])->defaultSort('created_at', 'desc')->recordUrl(null)
        ->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCodigos::route('/'),
            'create' => Pages\CreateCodigo::route('/create'),
            'edit' => Pages\EditCodigo::route('/{record}/edit'),
        ];
    }
}

