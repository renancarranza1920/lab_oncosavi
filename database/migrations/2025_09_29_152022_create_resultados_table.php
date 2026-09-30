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
      Schema::create('resultados', function (Blueprint $table) {
    $table->id();
    $table->foreignId('detalle_orden_id')->constrained('detalle_orden')->cascadeOnDelete();
    $table->unsignedBigInteger('prueba_id')->nullable();
        
        // 2. Luego le agregamos la restricción de llave foránea manualmente
        $table->foreign('prueba_id')
              ->references('id')
              ->on('pruebas')
              ->cascadeOnDelete();

    $table->string('resultado');
    $table->string('valor_referencia_externo')->nullable(); // Para guardar la referencia de pruebas externas
    $table->text('observaciones')->nullable();
    $table->boolean('fuera_de_rango')->default(false);
    $table->boolean('es_externo')->default(false); // Indica si el resultado es de una prueba externa
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('resultados', function (Blueprint $table) {
            $table->dropForeign(['detalle_orden_id']);
            $table->dropForeign(['prueba_id']);
        });
        Schema::dropIfExists('resultados');

    }
};
