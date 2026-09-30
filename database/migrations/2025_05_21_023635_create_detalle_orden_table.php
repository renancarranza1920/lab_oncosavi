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
        Schema::create('detalle_orden', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_id')
                ->constrained('ordens')
                ->onDelete('cascade');
            $table->foreignId('examen_id')
                ->constrained('examens')
                ->onDelete('cascade');
            $table->foreignId('perfil_id')
                ->nullable()
                ->constrained('perfil')
                ->onDelete('cascade');

            $table->string('nombre_examen');
            $table->string('nombre_perfil')->nullable();
            $table->decimal('precio_examen', 10, 2);
            $table->decimal('precio_perfil', 10, 2)->nullable();

            $table->string('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_orden_examens');
    }
};
