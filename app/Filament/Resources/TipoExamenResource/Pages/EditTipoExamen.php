<?php

namespace App\Filament\Resources\TipoExamenResource\Pages;

use App\Filament\Resources\TipoExamenResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTipoExamen extends EditRecord
{
    protected static string $resource = TipoExamenResource::class;

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        $updatedRecord = parent::handleRecordUpdate($record, $data);

        $this->redirect($this->getResource()::getUrl('index'));

        return $updatedRecord;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
