<?php

namespace App\Filament\Resources\CodigoResource\Pages;

use App\Filament\Resources\CodigoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditCodigo extends EditRecord
{
    protected static string $resource = CodigoResource::class;

    // El método mutateFormDataBeforeFill ya NO es necesario

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Si la bandera está apagada, nos aseguramos de que el valor sea null
        if (empty($data['es_limitado'])) {
            $data['limite_usos'] = null;
        }

        if (empty($data['tiene_vencimiento'])) {
            $data['fecha_vencimiento'] = null;
        }
        
        $record->update($data);
        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

