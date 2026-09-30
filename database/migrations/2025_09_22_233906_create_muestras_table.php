<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla para el catálogo de muestras
        Schema::create('muestras', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->text('descripcion')->nullable();
            $table->text('instrucciones_paciente')->nullable();
            $table->timestamps();
        });

        // Tabla pivote para la relación Muchos a Muchos entre Examen y Muestra
        Schema::create('examen_muestra', function (Blueprint $table) {
            $table->foreignId('examen_id')->constrained('examens')->onDelete('cascade');
            $table->foreignId('muestra_id')->constrained('muestras')->onDelete('cascade');
            $table->primary(['examen_id', 'muestra_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('examen_muestra');
        Schema::dropIfExists('muestras');
    }
};
