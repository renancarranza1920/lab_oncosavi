<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Primero creamos la tabla del catálogo 'tipos_pruebas'
        Schema::create('tipos_pruebas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->timestamps();
        });

        // Ahora creamos la tabla 'pruebas' que se relaciona con el catálogo
        Schema::create('pruebas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->foreignId('examen_id')
              ->nullable() // Permite que pruebas existentes no fallen
              ->constrained('examens') // Apunta a la tabla 'examens'
              ->cascadeOnDelete(); // Si se borra el examen, se borran sus pruebas
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');

            // Columna para la relación. Puede ser nula, como pediste.
            $table->foreignId('tipo_prueba_id')->nullable()->constrained('tipos_pruebas')->onDelete('set null');
            $table->string('tipo_conjunto')->nullable(); // Agrégala aquí, el orden no afecta la funcionalidad.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pruebas');
        Schema::dropIfExists('tipos_pruebas');
    }
};