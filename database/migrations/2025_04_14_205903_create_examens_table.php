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
        Schema::create('examens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipo_examen_id')->constrained('tipo_examens')->onDelete('cascade');
            $table->string('nombre');
               $table->boolean('es_externo')->default(false);
            $table->decimal('precio',10,2);
             $table->string('recipiente')->nullable();
            $table->boolean('estado')->default(true);
             
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('examens');
    }
};
