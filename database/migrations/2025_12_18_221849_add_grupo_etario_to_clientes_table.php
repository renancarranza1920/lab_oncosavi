<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGrupoEtarioToClientesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('clientes', function (Blueprint $table) {
            // Añadimos el campo grupo_etario
            $table->enum('grupo_etario', ['Ninos', 'Adultos', 'AdultosMayores', 'RecienNacidos'])->nullable()->after('fecha_nacimiento');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('clientes', function (Blueprint $table) {
            // Eliminamos el campo grupo_etario si es necesario revertir la migración
            $table->dropColumn('grupo_etario');
        });
    }
}
