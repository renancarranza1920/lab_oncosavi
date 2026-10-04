<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;

class AutorizarBitacoraSoporte extends Command
{
    protected $signature = 'oncosavi:autorizar-bitacora-soporte {usuario : Usuario administrador del propietario} {--revocar : Retirar el permiso directo}';
    protected $description = 'Autoriza al propietario a consultar la auditoría reservada de soporte.';

    public function handle(): int
    {
        $user = User::where('nickname', $this->argument('usuario'))->first();
        if (!$user || !$user->hasRole('admin')) {
            $this->error('Debe indicar un usuario existente con rol admin.');
            return self::FAILURE;
        }
        $permiso = Permission::findOrCreate('ver_bitacora_soporte', 'web');
        if ($this->option('revocar')) {
            $user->revokePermissionTo($permiso);
        } else {
            $user->givePermissionTo($permiso);
        }
        activity('Administración')->performedOn($user)->withProperties(['acceso_soporte' => !$this->option('revocar')])
            ->event('updated')->log('Acceso a la bitácora reservada de soporte actualizado.');
        $this->info('Acceso del propietario actualizado. No se modificaron los permisos del resto de administradores.');
        return self::SUCCESS;
    }
}
