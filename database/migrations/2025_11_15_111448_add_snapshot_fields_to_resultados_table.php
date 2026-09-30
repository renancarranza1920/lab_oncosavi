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
        Schema::table('resultados', function (Blueprint $table) {
            // El nombre de la prueba en el momento de guardar
            $table->string('prueba_nombre_snapshot')->nullable()->after('resultado');
            // El rango de referencia en el momento de guardar
            $table->string('valor_referencia_snapshot')->nullable()->after('prueba_nombre_snapshot');
            // Las unidades en el momento de guardar
            $table->string('unidades_snapshot')->nullable()->after('valor_referencia_snapshot');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('resultados', function (Blueprint $table) {
            $table->dropColumn('prueba_nombre_snapshot');
            $table->dropColumn('valor_referencia_snapshot');
            $table->dropColumn('unidades_snapshot');
        });
    }
};