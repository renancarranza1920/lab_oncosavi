<?php

namespace App\Filament\Resources\PlantillaReferenciaResource\Pages;

use App\Filament\Resources\PlantillaReferenciaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPlantillaReferencia extends EditRecord
{
    protected static string $resource = PlantillaReferenciaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
