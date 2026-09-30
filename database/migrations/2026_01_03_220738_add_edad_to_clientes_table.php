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
    Schema::table('clientes', function (Blueprint $table) {
        // Lo ponemos nullable por si usas fecha de nacimiento en su lugar
        // y 'after' para que quede ordenado en la base de datos
        $table->integer('edad')->nullable()->after('apellido');
    });
}

public function down(): void
{
    Schema::table('clientes', function (Blueprint $table) {
        $table->dropColumn('edad');
    });
}
};
