<?php

namespace App\Filament\Resources\CodigoResource\Pages;

use App\Filament\Resources\CodigoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCodigos extends ListRecords
{
    protected static string $resource = CodigoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
