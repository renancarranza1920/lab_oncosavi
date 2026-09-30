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
        Schema::create('codigos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique(); // código personalizado
            $table->string('tipo_descuento'); // tipo de descuento
            $table->decimal('valor_descuento', 10, 2); // o monto fijo si quieres
            $table->integer('limite_usos')->nullable(); // número máximo de usos
            $table->integer('usos_actuales')->default(0); // veces que ya se usó
            $table->date('fecha_vencimiento')->nullable();
            $table->string('estado'); // activo, inactivo, expirado
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('codigos');
    }
};
