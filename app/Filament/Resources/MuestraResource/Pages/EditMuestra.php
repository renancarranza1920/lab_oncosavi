<?php

namespace App\Filament\Resources\MuestraResource\Pages;

use App\Filament\Resources\MuestraResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMuestra extends EditRecord
{
    protected static string $resource = MuestraResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
