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
        Schema::table('codigos', function (Blueprint $table) {
            // Añade una bandera para saber si el cupón tiene un límite de usos.
            $table->boolean('es_limitado')->default(false)->after('valor_descuento');
            // Añade una bandera para saber si el cupón tiene una fecha de vencimiento.
            $table->boolean('tiene_vencimiento')->default(false)->after('usos_actuales');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('codigos', function (Blueprint $table) {
            $table->dropColumn('es_limitado');
            $table->dropColumn('tiene_vencimiento');
        });
    }
};
