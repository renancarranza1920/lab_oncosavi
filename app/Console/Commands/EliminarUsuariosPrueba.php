<?php

namespace App\Console\Commands;

use App\Services\RetirarUsuariosPrueba;
use Illuminate\Console\Command;

class EliminarUsuariosPrueba extends Command
{
    protected $signature = 'oncosavi:eliminar-usuarios-prueba';

    protected $description = 'Retira las cuentas de prueba del personal y conserva administradores, roles y datos clínicos.';

    public function handle(RetirarUsuariosPrueba $servicio): int
    {
        $eliminados = $servicio->eliminar();
        $servicio->limpiarCredenciales();
        $this->info('Cuentas de prueba retiradas: '.count($eliminados).'. Administradores y roles conservados.');

        return self::SUCCESS;
    }
}
