<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('envios_whatsapp', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('orden_id')->nullable()->constrained('ordens')->nullOnDelete();
            $table->string('tipo', 30);
            $table->text('telefono'); // Cifrado por Eloquent; sin contenido del mensaje o PDF.
            $table->char('huella', 64)->index();
            $table->string('estado', 20)->default('enviando')->index();
            $table->string('codigo', 40)->nullable();
            $table->string('message_id', 150)->nullable();
            $table->timestamp('enviado_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('envios_whatsapp');
    }
};
