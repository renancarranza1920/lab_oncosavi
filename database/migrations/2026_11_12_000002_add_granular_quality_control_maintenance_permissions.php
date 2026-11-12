<?php

use App\Models\User;
use App\Support\ControlCalidadPermissions as Cci;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            Cci::register();
            // El permiso anterior incluía la administración completa; conserva esos accesos una sola vez.
            Role::where('guard_name', 'web')->whereHas('permissions', fn ($query) => $query->where('name', Cci::MAINTAIN))
                ->each(fn (Role $role) => $role->givePermissionTo(Cci::maintenanceActions()));
            // Solo las asignaciones directas: los usuarios de un rol siguen heredando sus permisos.
            User::whereHas('permissions', fn ($query) => $query->where('guard_name', 'web')->where('name', Cci::MAINTAIN))
                ->each(fn (User $user) => $user->givePermissionTo(Cci::maintenanceActions()));
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void {}
};
