<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyGrupoEtarioColumnInClientesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('clientes', function (Blueprint $table) {
            // Cambiar el tipo de la columna 'grupo_etario' a VARCHAR(255)
            // Esto permitirá guardar el ID como texto
            $table->string('grupo_etario', 255)->nullable()->change();
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
            // Si se revierte la migración, lo volvemos a un enum limitado
            $table->enum('grupo_etario', ['Ninos', 'Adultos', 'AdultosMayores', 'RecienNacidos'])->nullable()->change();
        });
    }
}
