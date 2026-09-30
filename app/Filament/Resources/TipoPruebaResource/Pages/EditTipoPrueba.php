<?php

namespace App\Filament\Resources\TipoPruebaResource\Pages;

use App\Filament\Resources\TipoPruebaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTipoPrueba extends EditRecord
{
    protected static string $resource = TipoPruebaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
