<?php

namespace App\Filament\Resources\PruebaResource\Pages;

use App\Filament\Resources\PruebaResource;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;

class EditPrueba extends EditRecord
{
    protected static string $resource = PruebaResource::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Card::make()->schema([
                    TextInput::make('nombre')
                        ->required()
                        ->maxLength(255),

                    Select::make('examen_id')
                        ->relationship('examen', 'nombre')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Select::make('tipo_prueba_id')
                        ->relationship('tipoPrueba', 'nombre')
                        ->searchable()
                        ->createOptionAction(fn ($action) => $action->visible(true))
                        ->createOptionForm([
                            TextInput::make('nombre')
                                ->label('Nombre del Tipo de Prueba')
                                ->required()
                                ->maxLength(255),
                        ])
                        ->preload(),
                ]),

                PruebaResource::valoresReferenciaSection(),
            ]);
    }

    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
