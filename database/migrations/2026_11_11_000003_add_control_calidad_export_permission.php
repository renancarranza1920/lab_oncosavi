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
    }

    public function down(): void
    {
        Permission::query()->where('guard_name', 'web')->where('name', ControlCalidadPermissions::EXPORT)
            ->get()->each(fn (Permission $permission) => $permission->delete());
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
