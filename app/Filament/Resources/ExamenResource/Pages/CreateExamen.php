<?php
namespace App\Filament\Resources\ExamenResource\Pages;
use App\Filament\Resources\ExamenResource;
use Filament\Resources\Pages\CreateRecord;

class CreateExamen extends CreateRecord
{
    protected static string $resource = ExamenResource::class;

    protected function afterCreate(): void
    {
        $pruebasNombres = $this->data['pruebas_nombres'] ?? [];
        if (empty($pruebasNombres)) {
            return;
        }

        foreach ($pruebasNombres as $nombrePrueba) {
            $this->record->pruebas()->create(['nombre' => $nombrePrueba]);
        }
    }

    //redireccionar a la lista de examenes despues de crear uno nuevo
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');   
    }
}