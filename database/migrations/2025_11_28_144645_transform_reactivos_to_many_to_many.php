<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Crear la tabla pivote (Intermedia)
        Schema::create('prueba_reactivo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prueba_id')->constrained('pruebas')->onDelete('cascade');
            $table->foreignId('reactivo_id')->constrained('reactivos')->onDelete('cascade');
            $table->timestamps();
        });

        // 2. MIGRAR DATOS EXISTENTES (Salvar lo que ya tienes)
        // Copiamos: Si el reactivo 10 tiene prueba_id 5, creamos una fila en la pivote (5, 10)
        // Solo ejecutamos esto si existen las tablas y columnas
        if (Schema::hasColumn('reactivos', 'prueba_id')) {
            DB::statement('INSERT INTO prueba_reactivo (prueba_id, reactivo_id, created_at, updated_at) 
                           SELECT prueba_id, id, NOW(), NOW() 
                           FROM reactivos 
                           WHERE prueba_id IS NOT NULL');
        }

        // 3. Eliminar la columna vieja de reactivos
        Schema::table('reactivos', function (Blueprint $table) {
            $table->dropForeign(['prueba_id']); // Nombre puede variar, a veces es reactivos_prueba_id_foreign
            $table->dropColumn('prueba_id');
        });

        // 4. Agregar prueba_id a valor_referencias (Para tu nueva lógica de filtrado)
        Schema::table('valor_referencias', function (Blueprint $table) {
            $table->foreignId('prueba_id')
                  ->nullable()
                  ->after('reactivo_id')
                  ->constrained('pruebas')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        // Revertir es complejo porque perdimos la unicidad, pero hacemos lo posible
        Schema::table('valor_referencias', function (Blueprint $table) {
            $table->dropForeign(['prueba_id']);
            $table->dropColumn('prueba_id');
        });

        Schema::table('reactivos', function (Blueprint $table) {
            $table->foreignId('prueba_id')->nullable()->constrained('pruebas');
        });

        Schema::dropIfExists('prueba_reactivo');
    }
};