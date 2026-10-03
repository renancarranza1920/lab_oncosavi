<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CrearUsuariosPrueba extends Command
{
    protected $signature = 'oncosavi:usuarios-prueba';
    protected $description = 'Actualiza los roles y crea únicamente la cuenta de prueba del administrador.';

    public function handle(): int
    {
        $cuentas = ['prueba.admin' => 'admin'];
        foreach ($cuentas as $nickname => $rol) {
            $usuario = User::where('nickname', $nickname)->orWhere('email', $nickname . '@oncosavi.test')->first();
            if ($usuario && ($usuario->nickname !== $nickname || $usuario->email !== $nickname . '@oncosavi.test')) {
                $this->error("La cuenta {$nickname} coincide con un usuario existente. No se realizaron cambios.");
                return self::FAILURE;
            }
        }

        $credenciales = [];
        DB::transaction(function () use ($cuentas, &$credenciales) {
            $this->call('db:seed', ['--class' => RolesPermisosSeeder::class, '--force' => true]);
            foreach ($cuentas as $nickname => $rol) {
                $usuario = User::firstOrNew(['nickname' => $nickname]);
                if (!$usuario->exists) {
                    $password = Str::password(24);
                    $usuario->fill([
                        'name' => 'Prueba ' . $rol, 'email' => $nickname . '@oncosavi.test',
                        'password' => $password,
                    ])->save();
                    $credenciales[] = ['usuario' => $nickname, 'rol' => $rol, 'password' => $password];
                }
                $rolExistente = \Spatie\Permission\Models\Role::where('guard_name', 'web')->whereRaw('LOWER(name) = ?', [strtolower($rol)])->firstOrFail();
                $usuario->syncRoles([$rolExistente]);
                $this->line("Cuenta disponible: {$nickname} ({$rol})");
            }
        });

        if ($credenciales) {
            Storage::disk('local')->put('usuarios-prueba.json', json_encode($credenciales, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            chmod(Storage::disk('local')->path('usuarios-prueba.json'), 0600);
            $this->info('Contraseñas nuevas guardadas en storage/app/private/usuarios-prueba.json. No se publican ni se incluyen en Git.');
        } else {
            $this->info('Las cuentas ya existían. Se conservaron sus contraseñas.');
        }

        return self::SUCCESS;
    }
}
