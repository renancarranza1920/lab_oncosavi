<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resultados', function (Blueprint $table) {
            $table->text('valor_referencia_snapshot')->nullable()->change();
            $table->text('valor_referencia_externo')->nullable()->change();
        });
    }

    public function down(): void
    {
        // No volver a VARCHAR(255) si esto recortaría referencias ya guardadas.
        $longitud = DB::connection()->getDriverName() === 'sqlite' ? 'LENGTH' : 'CHAR_LENGTH';
        if (DB::table('resultados')
            ->whereRaw("{$longitud}(valor_referencia_snapshot) > 255")
            ->orWhereRaw("{$longitud}(valor_referencia_externo) > 255")
            ->exists()) {
            throw new RuntimeException('No se puede revertir: existen referencias de más de 255 caracteres. Se conservó su contenido completo.');
        }

        Schema::table('resultados', function (Blueprint $table) {
            $table->string('valor_referencia_snapshot')->nullable()->change();
            $table->string('valor_referencia_externo')->nullable()->change();
        });
    }
};
