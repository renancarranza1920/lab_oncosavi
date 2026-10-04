<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::connection(config('activitylog.database_connection'))->hasTable('registros_soporte')) {
            return;
        }
        Schema::connection(config('activitylog.database_connection'))->create('registros_soporte', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('activity_id')->unique();
            $table->string('nombre_usuario');
            $table->timestamp('registrado_at')->index();
            $table->longText('datos');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Conserva los registros privados: contienen auditoría que no debe borrarse al revertir.
    }
};
