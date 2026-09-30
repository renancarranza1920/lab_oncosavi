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
        $table->boolean('es_historico')->default(false)->after('estado');
    });
}

public function down(): void
{
    Schema::table('reactivos', function (Blueprint $table) {
        $table->dropColumn('es_historico');
    });
}
};
