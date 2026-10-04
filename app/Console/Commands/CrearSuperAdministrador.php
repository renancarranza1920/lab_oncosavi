<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class CrearSuperAdministrador extends Command
{
    protected $signature = 'oncosavi:crear-superadmin';
    protected $description = 'Crea una cuenta identificada de soporte técnico con acceso administrativo y auditoría normal.';

    public function handle(): int
    {
        $nickname = 'soporte.superadmin';
        $email = 'soporte@oncosavi.invalid';
        $existente = User::where('nickname', $nickname)->orWhere('email', $email)->first();
        if ($existente) {
            if ($existente->nickname !== $nickname || $existente->email !== $email || ! $existente->hasRole('super_admin')) {
                $this->error('El identificador coincide con otra cuenta. No se realizaron cambios.');
                return self::FAILURE;
            }
            $this->info('La cuenta de soporte ya existe. Se conservaron su contraseña y sus roles.');
            return self::SUCCESS;
        }
        $admin = Role::where('name', 'admin')->where('guard_name', 'web')->first();
        if (! $admin) {
            $this->error('Primero debe existir el rol admin. No se realizaron cambios.');
            return self::FAILURE;
        }
        $password = Str::password(28);
        DB::transaction(function () use ($nickname, $email, $password, $admin): void {
            $usuario = User::create(['name' => 'Soporte técnico · Superadministrador', 'nickname' => $nickname, 'email' => $email, 'password' => $password]);
            $usuario->assignRole([$admin, Role::findOrCreate('super_admin', 'web')]);
            activity('Soporte técnico')->causedBy($usuario)->performedOn($usuario)
                ->event('created')->log('Cuenta de soporte técnico creada desde la consola. Visible y sujeta a auditoría.');
            $disk = Storage::disk('local');
            if (! $disk->put('soporte-superadmin.json', json_encode(['usuario' => $nickname, 'password' => $password], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                throw new \RuntimeException('No se pudo guardar el archivo privado de acceso.');
            }
            chmod($disk->path('soporte-superadmin.json'), 0600);
        });
        $this->info('Cuenta de soporte creada. Contraseña en storage/app/private/soporte-superadmin.json. Sus acciones se registran en la bitácora.');
        return self::SUCCESS;
    }
}
