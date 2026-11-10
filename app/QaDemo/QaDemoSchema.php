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
        $db->table('qa_meta')->updateOrInsert(['key' => 'schema_version'], ['value' => (string) config('qa_demo.schema_version', 1)]);
    }
}
