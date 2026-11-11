<?php

use App\Support\ControlCalidadPermissions;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        ControlCalidadPermissions::register();
        Permission::query()->where('guard_name', 'web')->whereIn('name', [
            'exportar_control_calidad_interno',
            'revisar_control_calidad_interno',
            'asignar_laboratorista_control_calidad_interno',
        ])->get()->each(fn (Permission $permission) => $permission->delete());
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void {}
};
