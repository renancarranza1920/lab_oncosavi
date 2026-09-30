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
        Schema::create('valor_referencias', function (Blueprint $table) {
            $table->id();

            // --- CONEXIONES ---
            $table->foreignId('reactivo_id')->constrained()->cascadeOnDelete();
            $table->foreignId('grupo_etario_id')
      ->nullable()
      ->constrained('grupos_etarios')
      ->cascadeOnDelete();

            // --- DATOS DEL VALOR ---
            $table->string('operador')->default('rango')->comment('Define el tipo de lógica: rango, <=, >=, <, >, =');
            $table->string('descriptivo')->nullable()->comment('Ej: Fumadores, No Fumadores, Riesgoso');
            $table->string('genero')->nullable()->comment('Masculino, Femenino, Ambos.');
            $table->decimal('valor_min', 13, 2)->nullable();
            $table->decimal('valor_max', 13, 2)->nullable();
            $table->string('unidades')->nullable();
            $table->text('nota')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('valor_referencias');
    }
};