<?php

namespace App\Filament\Resources\GrupoEtarioResource\Pages;

use App\Filament\Resources\GrupoEtarioResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateGrupoEtario extends CreateRecord
{
    protected static string $resource = GrupoEtarioResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

  

