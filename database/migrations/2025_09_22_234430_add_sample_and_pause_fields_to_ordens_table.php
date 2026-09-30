<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
    {
        Schema::table('ordens', function (Blueprint $table) {
            // Para registrar cuándo se tomaron las muestras
            $table->timestamp('fecha_toma_muestra')->nullable()->after('estado');
            // Para registrar por qué se pausó una orden
            $table->text('motivo_pausa')->nullable()->after('fecha_toma_muestra');
        });
    }

    public function down(): void
    {
        Schema::table('ordens', function (Blueprint $table) {
            $table->dropColumn(['fecha_toma_muestra', 'motivo_pausa']);
        });
    }
};
