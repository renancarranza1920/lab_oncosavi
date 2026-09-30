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
        Schema::table('users', function (Blueprint $table) {
            // Guarda la RUTA (path) a la imagen de la firma
            $table->string('firma_path')->nullable()->after('password');
            // Guarda la RUTA (path) a la imagen del sello
            $table->string('sello_path')->nullable()->after('firma_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('firma_path');
            $table->dropColumn('sello_path');
        });
    }
};