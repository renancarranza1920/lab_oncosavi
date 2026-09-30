<?php

namespace App\Filament\Resources\GrupoEtarioResource\Pages;

use App\Filament\Resources\GrupoEtarioResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGrupoEtario extends EditRecord
{
    protected static string $resource = GrupoEtarioResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
