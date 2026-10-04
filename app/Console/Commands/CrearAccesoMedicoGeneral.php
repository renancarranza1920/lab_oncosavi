<?php

namespace App\Console\Commands;

use App\Models\Medico;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CrearAccesoMedicoGeneral extends Command
{
    protected $signature = 'oncosavi:portal-medicos-general {--renovar : Generar una nueva contraseña y cerrar las sesiones actuales}';

    protected $description = 'Habilita el usuario medicos y guarda su contraseña aleatoria en un archivo privado.';

    public function handle(): int
    {
        $medico = Medico::where('portal_usuario', 'medicos')->firstOrFail();
        if ($medico->password && ! $this->option('renovar')) {
            $this->info('El acceso medicos ya está configurado. Se conserva su contraseña y su estado.');

            return self::SUCCESS;
        }
        $password = Str::password(24);
        $medico->forceFill(['password' => $password, 'portal_activo' => true, 'portal_todos_pacientes' => true])->save();
        $disk = Storage::disk('local');
        $disk->put('portal-medicos-general.json', json_encode(['usuario' => 'medicos', 'password' => $password, 'url' => route('expediente.login')], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        chmod($disk->path('portal-medicos-general.json'), 0600);
        $this->info('Usuario medicos habilitado. Contraseña guardada en storage/app/private/portal-medicos-general.json. Puedes cambiarla en Médicos → Acceso médico general.');

        return self::SUCCESS;
    }
}
