<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cci_areas', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre', 80);
            $table->string('equipo', 160);
            $table->timestamps();
            $table->unique(['nombre', 'equipo']);
        });
        Schema::create('cci_productos', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre', 160)->unique();
            $table->timestamps();
        });
        Schema::create('cci_lotes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('producto_id')->constrained('cci_productos')->restrictOnDelete();
            $table->string('nombre', 160);
            $table->string('numero', 100);
            $table->date('caduca_el');
            $table->timestamps();
            $table->unique(['producto_id', 'numero']);
        });
        Schema::create('cci_pruebas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('area_id')->constrained('cci_areas')->restrictOnDelete();
            $table->string('nombre', 160);
            $table->string('unidad', 40);
            $table->timestamps();
            $table->unique(['area_id', 'nombre']);
        });
        Schema::create('cci_niveles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('prueba_id')->constrained('cci_pruebas')->cascadeOnDelete();
            $table->string('nombre', 80);
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
            $table->index(['prueba_id', 'orden']);
        });
        Schema::create('cci_valores_control', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('nivel_id')->unique()->constrained('cci_niveles')->cascadeOnDelete();
            $table->foreignId('lote_id')->constrained('cci_lotes')->restrictOnDelete();
            $table->decimal('esperado', 20, 6);
            $table->decimal('minimo', 20, 6);
            $table->decimal('maximo', 20, 6);
            $table->timestamps();
        });
        Schema::create('cci_registros', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('area_id')->constrained('cci_areas')->restrictOnDelete();
            $table->foreignId('laboratorista_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('registrado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actualizado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('fecha_hora')->index();
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('cci_resultados', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('registro_id')->constrained('cci_registros')->cascadeOnDelete();
            $table->foreignId('prueba_id')->constrained('cci_pruebas')->restrictOnDelete();
            $table->foreignId('nivel_id')->constrained('cci_niveles')->restrictOnDelete();
            $table->foreignId('valor_control_id')->constrained('cci_valores_control')->restrictOnDelete();
            $table->decimal('valor', 20, 6);
            $table->string('valor_original', 40);
            $table->string('prueba_nombre', 160);
            $table->string('nivel_nombre', 80);
            $table->string('unidad', 40);
            $table->string('lote_nombre', 160);
            $table->string('lote_numero', 100);
            $table->decimal('esperado', 20, 6);
            $table->decimal('minimo', 20, 6);
            $table->decimal('maximo', 20, 6);
            $table->timestamps();
            $table->unique(['registro_id', 'nivel_id']);
        });
    }

    public function down(): void
    {
        foreach (['cci_resultados', 'cci_registros', 'cci_valores_control', 'cci_niveles', 'cci_pruebas', 'cci_lotes', 'cci_productos', 'cci_areas'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
