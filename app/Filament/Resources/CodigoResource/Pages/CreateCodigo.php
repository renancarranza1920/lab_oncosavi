<?php

namespace App\Filament\Resources\CodigoResource\Pages;

use App\Filament\Resources\CodigoResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCodigo extends CreateRecord
{
    protected static string $resource = CodigoResource::class;

    /**
     * Usamos este método robusto para procesar los datos
     * justo antes de la creación del registro.
     */
    protected function handleRecordCreation(array $data): Model
    {
        // Si la bandera 'es_limitado' está apagada, nos aseguramos de que el valor sea null.
        if (empty($data['es_limitado'])) {
            $data['limite_usos'] = null;
        }

        // Si la bandera 'tiene_vencimiento' está apagada, nos aseguramos de que el valor sea null.
        if (empty($data['tiene_vencimiento'])) {
            $data['fecha_vencimiento'] = null;
        }

        return static::getModel()::create($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

