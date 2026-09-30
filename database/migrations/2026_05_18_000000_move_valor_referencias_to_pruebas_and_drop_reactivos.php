<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $dropForeignIfExists = function (string $table, string $column): void {
            if (DB::getDriverName() === 'mysql') {
                $constraints = DB::table('information_schema.KEY_COLUMN_USAGE')
                    ->where('TABLE_SCHEMA', DB::getDatabaseName())
                    ->where('TABLE_NAME', $table)
                    ->where('COLUMN_NAME', $column)
                    ->whereNotNull('REFERENCED_TABLE_NAME')
                    ->pluck('CONSTRAINT_NAME')
                    ->unique();

                foreach ($constraints as $constraint) {
                    DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraint}`");
                }

                return;
            }

            try {
                Schema::table($table, fn (Blueprint $table) => $table->dropForeign([$column]));
            } catch (Throwable) {
                //
            }
        };

        if (Schema::hasTable('valor_referencias')) {
            if (! Schema::hasColumn('valor_referencias', 'prueba_id')) {
                Schema::table('valor_referencias', function (Blueprint $table) {
                    $table->foreignId('prueba_id')
                        ->nullable()
                        ->after('id')
                        ->constrained('pruebas')
                        ->cascadeOnDelete();
                });
            }

            if (Schema::hasColumn('valor_referencias', 'reactivo_id')) {
                $rows = DB::table('valor_referencias')
                    ->whereNull('prueba_id')
                    ->whereNotNull('reactivo_id')
                    ->orderBy('id')
                    ->get();

                foreach ($rows as $row) {
                    $pruebaIds = collect();

                    if (Schema::hasTable('prueba_reactivo')) {
                        $pruebaIds = DB::table('prueba_reactivo')
                            ->where('reactivo_id', $row->reactivo_id)
                            ->pluck('prueba_id');
                    }

                    if ($pruebaIds->isEmpty() && Schema::hasTable('reactivos') && Schema::hasColumn('reactivos', 'prueba_id')) {
                        $pruebaId = DB::table('reactivos')
                            ->where('id', $row->reactivo_id)
                            ->value('prueba_id');

                        if ($pruebaId) {
                            $pruebaIds = collect([$pruebaId]);
                        }
                    }

                    $pruebaIds = $pruebaIds->filter()->unique()->values();

                    if ($pruebaIds->isEmpty()) {
                        continue;
                    }

                    DB::table('valor_referencias')
                        ->where('id', $row->id)
                        ->update(['prueba_id' => $pruebaIds->first()]);

                    foreach ($pruebaIds->slice(1) as $pruebaId) {
                        $copy = (array) $row;
                        unset($copy['id']);
                        $copy['prueba_id'] = $pruebaId;

                        DB::table('valor_referencias')->insert($copy);
                    }
                }

                DB::table('valor_referencias')
                    ->whereNull('prueba_id')
                    ->delete();

                $dropForeignIfExists('valor_referencias', 'reactivo_id');

                Schema::table('valor_referencias', function (Blueprint $table) {
                    $table->dropColumn('reactivo_id');
                });
            }
        }

        Schema::dropIfExists('prueba_reactivo');
        Schema::dropIfExists('reactivos');

        if (Schema::hasTable('permissions')) {
            $permissionIds = DB::table('permissions')
                ->where('name', 'like', '%reactivo%')
                ->pluck('id');

            if ($permissionIds->isNotEmpty()) {
                if (Schema::hasTable('role_has_permissions')) {
                    DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
                }

                if (Schema::hasTable('model_has_permissions')) {
                    DB::table('model_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
                }

                DB::table('permissions')->whereIn('id', $permissionIds)->delete();
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('reactivos')) {
            Schema::create('reactivos', function (Blueprint $table) {
                $table->id();
                $table->string('nombre');
                $table->string('lote')->nullable();
                $table->date('fecha_caducidad')->nullable();
                $table->text('descripcion')->nullable();
                $table->boolean('en_uso')->default(false);
                $table->string('estado')->default('disponible');
                $table->boolean('es_historico')->default(false);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('prueba_reactivo')) {
            Schema::create('prueba_reactivo', function (Blueprint $table) {
                $table->id();
                $table->foreignId('prueba_id')->constrained('pruebas')->cascadeOnDelete();
                $table->foreignId('reactivo_id')->constrained('reactivos')->cascadeOnDelete();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('valor_referencias') && ! Schema::hasColumn('valor_referencias', 'reactivo_id')) {
            Schema::table('valor_referencias', function (Blueprint $table) {
                $table->foreignId('reactivo_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('reactivos')
                    ->nullOnDelete();
            });
        }
    }
};
