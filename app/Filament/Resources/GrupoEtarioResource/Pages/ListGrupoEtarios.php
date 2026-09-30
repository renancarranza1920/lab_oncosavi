<?php

namespace App\Filament\Resources\GrupoEtarioResource\Pages;

use App\Filament\Resources\GrupoEtarioResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGrupoEtarios extends ListRecords
{
    protected static string $resource = GrupoEtarioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
