<?php

use App\Services\RetirarUsuariosPrueba;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(RetirarUsuariosPrueba::class)->eliminar();
    }

    public function down(): void
    {
        // No se recrean usuarios ni contraseñas al revertir esta limpieza de datos.
    }
};
