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
    Schema::table('reactivos', function (Blueprint $table) {
        // Añadimos la columna 'estado' con 'disponible' como valor por defecto.
        $table->string('estado')->default('disponible')->after('en_uso');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reactivos', function (Blueprint $table) {
            //
            $table->dropColumn('estado');
        });
    }
};
