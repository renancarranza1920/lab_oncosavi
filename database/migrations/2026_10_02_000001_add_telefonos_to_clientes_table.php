<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->json('telefonos')->nullable()->after('telefono');
        });

        DB::table('clientes')
            ->whereNotNull('telefono')
            ->where('telefono', '!=', '')
            ->orderBy('id')
            ->chunkById(100, function ($clientes): void {
                foreach ($clientes as $cliente) {
                    DB::table('clientes')->where('id', $cliente->id)->update([
                        'telefonos' => json_encode([['numero' => $cliente->telefono]]),
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('telefonos');
        });
    }
};
