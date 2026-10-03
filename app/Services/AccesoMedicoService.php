<?php

namespace App\Services;

use App\Models\Medico;
use Illuminate\Support\Facades\Gate;

class AccesoMedicoService
{
    public function configurar(Medico $medico, bool $activo, bool $todosPacientes, ?string $password): void
    {
        Gate::authorize('manage_settings');
        validator(['password' => $password], [
            'password' => ($activo && ! $medico->password ? 'required' : 'nullable').'|string|min:8|max:128',
        ])->validate();

        $medico->portal_activo = $activo;
        $medico->portal_todos_pacientes = $todosPacientes;
        if ($password !== null && $password !== '') {
            $medico->password = $password;
        }
        $medico->save();

        activity('Acceso de médicos')
            ->performedOn($medico)
            ->causedBy(auth()->user())
            ->withProperties(['activo' => $activo, 'todos_pacientes' => $todosPacientes])
            ->log("Acceso al portal de {$medico->nombre} ".($activo ? 'habilitado o actualizado' : 'deshabilitado'));
    }
}
