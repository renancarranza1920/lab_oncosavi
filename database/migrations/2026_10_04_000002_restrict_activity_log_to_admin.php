<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $tablas = config('permission.table_names');
        $roles = DB::table($tablas['roles'])->where('guard_name', 'web')
            ->whereRaw('LOWER(name) NOT IN (?, ?)', ['admin', 'super_admin'])->pluck('id');
        $permisos = DB::table($tablas['permissions'])->where('guard_name', 'web')
            ->where(fn ($query) => $query->where('name', 'like', '%activity::log')
                ->orWhereIn('name', ['ver_bitacora_completa', 'ver_bitacora_soporte']))->pluck('id');
        DB::table($tablas['role_has_permissions'])->whereIn('role_id', $roles)
            ->whereIn('permission_id', $permisos)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // No vuelve a conceder acceso a la bitácora a roles no administrativos.
    }
};
