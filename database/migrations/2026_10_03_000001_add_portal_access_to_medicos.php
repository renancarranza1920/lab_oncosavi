<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicos', function (Blueprint $table) {
            $table->string('password')->nullable();
            $table->boolean('portal_activo')->default(false);
            $table->boolean('portal_todos_pacientes')->default(false);
            $table->unsignedInteger('portal_version')->default(0);
        });
        Schema::table('ordens', function (Blueprint $table) {
            $table->index(['medico_id', 'cliente_id', 'fecha'], 'ordens_portal_medico_index');
            $table->index(['cliente_id', 'fecha'], 'ordens_portal_cliente_index');
        });
    }

    public function down(): void
    {
        // MySQL puede retirar el índice automático de una FK cuando aparece
        // uno compuesto que la cubre. Restituirlo antes de quitar los del portal.
        if (DB::connection()->getDriverName() === 'mysql') {
            foreach (['medico_id', 'cliente_id'] as $columna) {
                if (! Schema::hasIndex('ordens', [$columna])) {
                    Schema::table('ordens', fn (Blueprint $table) => $table->index($columna, 'ordens_'.$columna.'_foreign'));
                }
            }
        }
        Schema::table('ordens', function (Blueprint $table) {
            $table->dropIndex('ordens_portal_medico_index');
            $table->dropIndex('ordens_portal_cliente_index');
        });
        Schema::table('medicos', function (Blueprint $table) {
            $table->dropColumn(['password', 'portal_activo', 'portal_todos_pacientes', 'portal_version']);
        });
    }
};
