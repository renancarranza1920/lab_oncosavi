<?php

namespace App\Filament\Resources\ClientesResource\Pages;

use App\Filament\Resources\ClientesResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Cliente;

class CreateClientes extends CreateRecord
{
    protected static string $resource = ClientesResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Obtener las primeras 2 letras del nombre y apellido
        $prefijo = strtoupper(substr($data['nombre'], 0, 1) . substr($data['apellido'], 0, 1));

        $año = date('y');

        // Base del Número de expediente
        $base = $prefijo . $año;

        // Contar cuántos ya existen con ese mismo prefijo + año
        $cantidadExistentes = Cliente::where('NumeroExp', 'LIKE', "$base%")->count();

        // Generar el correlativo con ceros a la izquierda (ej: 001, 002...)
        $correlativo = str_pad($cantidadExistentes + 1, 3, '0', STR_PAD_LEFT);

        // Armar el NumeroExp final
        $data['NumeroExp'] = $base . $correlativo;

        $data['estado'] = 'Activo';

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        // Redirige al listado principal después de guardar
        return $this->getResource()::getUrl('index');
    }
}


