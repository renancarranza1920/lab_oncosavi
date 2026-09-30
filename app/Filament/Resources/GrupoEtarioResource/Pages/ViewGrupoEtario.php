<?php

namespace App\Filament\Resources\GrupoEtarioResource\Pages;

use App\Filament\Resources\GrupoEtarioResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewGrupoEtario extends ViewRecord
{
    protected static string $resource = GrupoEtarioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
