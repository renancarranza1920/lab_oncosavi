<?php

use App\Support\TelefonoCliente;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cliente_telefonos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->string('numero', 20)->index();
            $table->string('codigo_pais', 3);
            $table->string('tipo', 10)->default('movil');
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
            $table->index(['cliente_id', 'orden']);
        });
        DB::table('clientes')->whereNotNull('telefono')->where('telefono', '!=', '')->orderBy('id')->chunkById(200, function ($clientes): void {
            foreach ($clientes as $cliente) {
                $numero = TelefonoCliente::normalizarExistente($cliente->telefono);
                if (! $numero) {
                    continue;
                }
                [$codigo] = TelefonoCliente::separar($cliente->telefono);
                DB::table('cliente_telefonos')->insert(['cliente_id' => $cliente->id, 'numero' => $numero, 'codigo_pais' => $codigo, 'tipo' => 'movil', 'orden' => 0, 'created_at' => now(), 'updated_at' => now()]);
                DB::table('clientes')->where('id', $cliente->id)->update(['telefono' => $numero]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cliente_telefonos');
    }
};
