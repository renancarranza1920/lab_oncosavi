<?php

namespace App\Filament\Resources\PerfilResource\Pages;

use App\Filament\Resources\PerfilResource;
use App\Models\Perfil;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePerfil extends CreateRecord
{
     protected static bool $canCreateAnother = false;
    protected static string $resource = PerfilResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        \DB::beginTransaction();
        
        try {
            // 1. Crear el perfil base
            $perfil = Perfil::create([
                'nombre' => $data['nombre'],
                'precio' => $data['precio'],
                'estado' => $data['estado'] ?? true,
            ]);
    
            // 2. Obtener datos DIRECTAMENTE del textarea
            $examenesJSON = $data['examenes_seleccionados'] ?? '[]';
            
            \Log::debug('Contenido del textarea:', ['json' => $examenesJSON]);
            
            // 3. Decodificar y extraer IDs
            $examenes = json_decode($examenesJSON, true);
            
        
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Formato JSON inválido en examenes_seleccionados');
            }
            
            $ids = array_column($examenes, 'id'); // Extracción directa de IDs
            
            \Log::debug('IDs extraídos:', $ids);
    
            // 4. Relacionar exámenes
            $perfil->examenes()->sync($ids);
            
            \DB::commit();
            return $perfil;
            
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Error al crear perfil:', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }
  

  

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}