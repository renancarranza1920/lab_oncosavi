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
        // Agregamos la columna como un entero opcional (nullable)
        $table->integer('semanas_gestacion')->nullable()->after('cliente_id');
    });
}

public function down(): void
{
    Schema::table('ordenes', function (Blueprint $table) {
        $table->dropColumn('semanas_gestacion');
    });
}
};
