<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicos', function (Blueprint $table): void {
            $table->string('portal_usuario', 32)->nullable()->unique();
        });
        DB::table('medicos')->insert([
            'nombre' => 'Acceso médico general', 'portal_usuario' => 'medicos',
            'portal_activo' => false, 'portal_todos_pacientes' => true,
            'portal_version' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('medicos')->where('portal_usuario', 'medicos')->delete();
        Schema::table('medicos', function (Blueprint $table): void {
            $table->dropUnique(['portal_usuario']);
            $table->dropColumn('portal_usuario');
        });
    }
};
