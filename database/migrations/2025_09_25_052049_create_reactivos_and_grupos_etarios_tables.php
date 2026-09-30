<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reactivos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->foreignId('prueba_id')->constrained('pruebas')->cascadeOnDelete();
            $table->string('lote')->nullable();
            $table->date('fecha_caducidad')->nullable();
            $table->text('descripcion')->nullable();
            $table->boolean('en_uso')->default(true);
            $table->timestamps();
        });

        Schema::create('grupos_etarios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->integer('edad_min');
            $table->integer('edad_max');
            $table->enum('unidad_tiempo', ['días', 'semanas', 'meses', 'años']);
            $table->enum('genero', ['Masculino', 'Femenino', 'Ambos'])->default('Ambos');
            $table->integer("estado")->default(1); // 1: activo, 0: inactivo
          
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reactivos');
        Schema::dropIfExists('grupos_etarios');
    }
};