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
        Schema::table('detalle_orden', function (Blueprint $table) {
            // Esta columna guardará un array (JSON) con los IDs de las MUESTRAS
            // que se han recibido para ESTE detalle de orden específico.
            $table->json('muestras_recibidas')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detalle_orden', function (Blueprint $table) {
            $table->dropColumn('muestras_recibidas');
        });
    }
};