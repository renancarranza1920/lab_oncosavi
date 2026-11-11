<?php

namespace App\QaDemo;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;

final class QaDemoSchema
{
    public static function install(Connection $db): void
    {
        $schema = $db->getSchemaBuilder();
        if (! $schema->hasTable('qa_meta')) {
            $schema->create('qa_meta', function (Blueprint $table): void {
                $table->string('key')->primary();
                $table->text('value');
            });
        }
        if (! $schema->hasTable('qa_catalog')) {
            $schema->create('qa_catalog', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('source_id')->unique();
                $table->string('name');
                $table->string('exam_name');
                $table->string('unit', 40)->default('');
                $table->string('area')->default('Química sanguínea');
                $table->boolean('active')->default(true);
            });
        }
        if (! $schema->hasTable('qa_equipment')) {
            $schema->create('qa_equipment', function (Blueprint $table): void {
                $table->id();
                $table->string('code', 40)->unique();
                $table->string('name', 120);
                $table->string('model', 160)->default('');
                $table->text('notes')->default('');
                $table->boolean('active')->default(true);
                $table->timestamps();
            });
        }
        if (! $schema->hasTable('qa_configurations')) {
            $schema->create('qa_configurations', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('equipment_id')->constrained('qa_equipment')->restrictOnDelete();
                $table->unsignedInteger('version');
                $table->string('label', 150);
                $table->string('configured_by', 120);
                $table->boolean('active')->default(true);
                $table->text('data_json');
                $table->timestamp('created_at');
                $table->unique(['equipment_id', 'version']);
            });
        }
        if (! $schema->hasTable('qa_events')) {
            $schema->create('qa_events', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('equipment_id')->constrained('qa_equipment')->restrictOnDelete();
                $table->string('type', 30);
                $table->timestamp('occurred_at');
                $table->string('operator', 120);
                $table->text('notes');
                $table->timestamp('created_at');
            });
        }
        if (! $schema->hasTable('qa_runs')) {
            $schema->create('qa_runs', function (Blueprint $table): void {
                $table->id();
                $table->string('number', 50)->nullable()->unique();
                $table->foreignId('equipment_id')->constrained('qa_equipment')->restrictOnDelete();
                $table->foreignId('configuration_id')->constrained('qa_configurations')->restrictOnDelete();
                $table->timestamp('measured_at')->index();
                $table->timestamp('captured_at');
                $table->string('operator', 120);
                $table->string('kind', 30);
                $table->foreignId('parent_run_id')->nullable()->constrained('qa_runs')->restrictOnDelete();
                $table->foreignId('event_id')->nullable()->constrained('qa_events')->restrictOnDelete();
                $table->string('status', 20);
                $table->string('comparison', 20);
                $table->string('review_status', 20)->default('pending');
                $table->text('notes')->default('');
                $table->text('missing_reason')->default('');
                $table->text('configuration_snapshot');
                $table->text('measurements_json');
                $table->timestamps();
            });
        }
        if (! $schema->hasTable('qa_reviews')) {
            $schema->create('qa_reviews', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('run_id')->constrained('qa_runs')->restrictOnDelete();
                $table->string('reviewer', 120);
                $table->string('decision', 20);
                $table->text('notes');
                $table->timestamp('reviewed_at');
            });
        }
        if (! $schema->hasColumn('qa_equipment', 'area')) {
            $schema->table('qa_equipment', fn (Blueprint $table) => $table->string('area', 80)->default('Química'));
        }
        if (! $schema->hasColumn('qa_catalog', 'is_local')) {
            $schema->table('qa_catalog', fn (Blueprint $table) => $table->boolean('is_local')->default(false));
        }
        if (! $schema->hasTable('qa_products')) {
            $schema->create('qa_products', function (Blueprint $table): void {
                $table->id();
                $table->string('name', 160);
                $table->timestamps();
            });
        }
        if (! $schema->hasTable('qa_lots')) {
            $schema->create('qa_lots', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('product_id')->constrained('qa_products')->restrictOnDelete();
                $table->string('name', 160);
                $table->string('lot', 100);
                $table->date('expires_on');
                $table->timestamps();
                $table->unique(['product_id', 'lot']);
            });
        }
        foreach (['operator_user_id', 'created_by', 'updated_by', 'deleted_by'] as $column) {
            if (! $schema->hasColumn('qa_runs', $column)) {
                $schema->table('qa_runs', fn (Blueprint $table) => $table->unsignedBigInteger($column)->nullable());
            }
        }
        if (! $schema->hasColumn('qa_runs', 'deleted_at')) {
            $schema->table('qa_runs', function (Blueprint $table): void {
                $table->timestamp('deleted_at')->nullable();
                $table->text('deleted_reason')->nullable();
                $table->unsignedInteger('revision')->default(1);
            });
        }
        if (! $schema->hasTable('qa_run_audits')) {
            $schema->create('qa_run_audits', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('run_id')->constrained('qa_runs')->restrictOnDelete();
                $table->string('action', 30);
                $table->unsignedBigInteger('actor_id')->nullable();
                $table->string('actor_name', 120);
                $table->text('reason')->nullable();
                $table->text('before_json');
                $table->timestamp('created_at');
            });
        }
        if (! $db->table('qa_meta')->where('key', 'lots_catalog_initialized')->exists()) {
            // Build a maintenance catalog from old configurations without rewriting their snapshots.
            foreach ($db->table('qa_configurations')->orderByDesc('id')->get() as $configuration) {
                $data = json_decode($configuration->data_json, true, 512, JSON_THROW_ON_ERROR);
                foreach ($data['levels'] ?? [] as $level) {
                    if (empty($level['product']) || empty($level['lot']) || empty($level['expires_on'])) {
                        continue;
                    }
                    $productId = $db->table('qa_products')->where('name', $level['product'])->value('id');
                    if (! $productId) {
                        $productId = $db->table('qa_products')->insertGetId(['name' => $level['product'],
                            'created_at' => $configuration->created_at, 'updated_at' => $configuration->created_at]);
                    }
                    if (! $db->table('qa_lots')->where('product_id', $productId)->where('lot', $level['lot'])->exists()) {
                        $db->table('qa_lots')->insert(['product_id' => $productId,
                            'name' => $level['label'] ?? $level['lot'], 'lot' => $level['lot'], 'expires_on' => $level['expires_on'],
                            'created_at' => $configuration->created_at, 'updated_at' => $configuration->created_at]);
                    }
                }
            }
            $db->table('qa_meta')->insert(['key' => 'lots_catalog_initialized', 'value' => '1']);
        }
        $db->table('qa_meta')->updateOrInsert(['key' => 'schema_version'], ['value' => (string) config('qa_demo.schema_version', 1)]);
    }
}
