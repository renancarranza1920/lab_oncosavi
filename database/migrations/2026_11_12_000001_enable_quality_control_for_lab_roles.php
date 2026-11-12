<?php

use App\Support\ControlCalidadPermissions;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        ControlCalidadPermissions::register();
    }

    // No retirar permisos que el administrador pudiera haber asignado manualmente.
    public function down(): void {}
};
