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
            // El ID del cupón que se usó
            $table->foreignId('codigo_id')->nullable()->constrained('codigos')->after('estado');
            // El monto exacto que se descontó
            $table->decimal('descuento', 10, 2)->default(0)->after('total');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ordens', function (Blueprint $table) {
            $table->dropForeign(['codigo_id']);
            $table->dropColumn('codigo_id');
            $table->dropColumn('descuento');
        });
    }
};