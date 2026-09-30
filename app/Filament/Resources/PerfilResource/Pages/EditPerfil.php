<?php

namespace App\Filament\Resources\PerfilResource\Pages;

use App\Filament\Resources\PerfilResource;
use App\Models\Perfil;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPerfil extends EditRecord
{

    protected static string $resource = PerfilResource::class;
    
    protected function mutateFormDataBeforeFill(array $data): array
    {
        
        // Cargar los exámenes asociados y meterlos al textarea como JSON
        $examenes = $this->record->examenes()
            ->with('tipoExamen') // Carga la relación
            ->select('examens.id', 'examens.nombre', 'examens.tipo_examen_id')
            ->get()
            ->map(function ($examen) {
                return [
                    'id' => $examen->id,
                    'nombre' => $examen->nombre,
                    //
                    'tipo' => $examen->tipoExamen->nombre ?? '', // Nombre real del tipo
                ];
            })
            ->toArray();
      
        //log para verificar el ID del perfil que se está editando
       
        $data['examenes_seleccionados'] = json_encode($examenes, JSON_UNESCAPED_UNICODE);
       
        return $data;
    }

    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        \DB::beginTransaction();

        try {
            // Guardar los cambios en el perfil
               // Guardar campos del formulario (nombre, precio, estado, etc.)
        parent::save(false, false); // Esto guarda los datos del formulario automáticamente


            // Obtener los exámenes seleccionados desde el campo correspondiente
            $examenesJSON = $this->data['examenes_seleccionados'] ?? '[]';

            \Log::debug('Contenido del textarea en EditPerfil:', ['json' => $examenesJSON]);

            // Decodificar el JSON
            $examenes = json_decode($examenesJSON, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Formato JSON inválido en examenes_seleccionados');
            }

            // Extraer los IDs de los exámenes
            $ids = array_column($examenes, 'id');

            \Log::debug('IDs extraídos en EditPerfil:', $ids);

            // --- CAMBIO AQUÍ: Capturamos el resultado de la sincronización ---
            $cambios = $this->record->examenes()->sync($ids);
            
            // --- NUEVA LÍNEA: Llamamos al método de registro ---
            $this->registrarBitacoraExamenes($cambios);
            \DB::commit();

            // Redirigir si se solicita
            if ($shouldRedirect) {
                $this->redirectRoute('filament.admin.resources.perfils.index');
            }
             // Enviar notificación si se solicita
             if ($shouldSendSavedNotification) {
                Notification::make()
                    ->title('Perfil actualizado correctamente.')
                    ->success()
                    ->send();
            }
            
         
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Error al guardar perfil en EditPerfil:', [
                'error' => $e->getMessage(),
                'data' => $this->data,
            ]);
            throw $e;
        }
    }


/**
     * Registra en la bitácora si hubo cambios en los exámenes del perfil,
     * incluyendo explícitamente el ID y Nombre del Perfil afectado.
     */
    private function registrarBitacoraExamenes(array $cambios): void
    {
        // Si no se agregó ni quitó nada, no registramos nada.
        if (empty($cambios['attached']) && empty($cambios['detached'])) {
            return;
        }

        // 1. Datos del Perfil Modificado
        $perfilId = $this->record->id;
        $perfilNombre = $this->record->nombre;

        // 2. Obtener nombres de exámenes afectados
        $nombresAgregados = [];
        $nombresEliminados = [];

        if (!empty($cambios['attached'])) {
            $nombresAgregados = \App\Models\Examen::whereIn('id', $cambios['attached'])
                ->pluck('nombre')
                ->toArray();
        }

        if (!empty($cambios['detached'])) {
            $nombresEliminados = \App\Models\Examen::whereIn('id', $cambios['detached'])
                ->pluck('nombre')
                ->toArray();
        }

        // 3. Construir el mensaje descriptivo
        $detallesTexto = [];

        if (count($nombresAgregados) > 0) {
            $detallesTexto[] = "Agregados: [" . implode(', ', $nombresAgregados) . "]";
        }
        
        if (count($nombresEliminados) > 0) {
            $detallesTexto[] = "Eliminados: [" . implode(', ', $nombresEliminados) . "]";
        }

        $cambiosLegibles = implode(' | ', $detallesTexto);

        // 4. Guardar el Log
        activity()
            ->performedOn($this->record) // Vincula el modelo automáticamente
            ->causedBy(auth()->user())   // Usuario responsable
            ->useLog('Perfiles')         // Canal del log
            ->withProperties([           // Guardamos la data técnica en JSON
                'perfil_afectado' => [   // <--- AQUÍ GUARDAMOS EL PERFIL EXPLÍCITAMENTE
                    'id' => $perfilId,
                    'nombre' => $perfilNombre,
                ],
                'examenes_agregados_ids' => $cambios['attached'],
                'examenes_eliminados_ids' => $cambios['detached'],
            ])
            // En la descripción ponemos el nombre del perfil para lectura rápida
            ->log("Actualización de exámenes en perfil '{$perfilNombre}' (ID: {$perfilId}): {$cambiosLegibles}");
    }
    
  public function mount(int|string $record): void
{
    parent::mount($record);
    
    // Obtener el perfil
    $perfil = Perfil::find($record);
    $perfilId = $perfil ? $perfil->id : null;

    $this->viewData = ['perfilId' => $perfilId]; // Aquí asignas el perfilId de manera segura
}


}
