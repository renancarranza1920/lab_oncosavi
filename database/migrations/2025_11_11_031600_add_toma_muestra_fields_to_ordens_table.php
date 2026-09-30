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
        Schema::table('ordens', function (Blueprint $table) {
            // Guarda el ID del usuario que confirmó las muestras
            $table->foreignId('toma_muestra_user_id')->nullable()->constrained('users')->after('muestras_recibidas');
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ordens', function (Blueprint $table) {
            $table->dropForeign(['toma_muestra_user_id']);
            $table->dropColumn('toma_muestra_user_id');
            
        });
    }
};