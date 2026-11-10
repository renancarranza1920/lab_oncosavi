<?php

namespace App\QaDemo;

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use RuntimeException;

final class QaDemoService
{
    public const LEVELS = ['low' => 'Bajo', 'medium' => 'Medio', 'high' => 'Alto'];
    public const KINDS = ['routine', 'additional', 'repeat', 'correction', 'before_event', 'after_event'];

    public function root(): string
    {
        return rtrim((string) config('qa_demo.path', storage_path('app/private/qa-quimica')), DIRECTORY_SEPARATOR);
    }

    public function enabled(): bool
    {
        return is_file($this->root().'/enabled.flag');
    }

    public function ready(): bool
    {
        if (! is_file($this->root().'/demo.sqlite')) {
            return false;
        }
        try {
            return $this->connection()->getSchemaBuilder()->hasTable('qa_meta')
                && (int) $this->connection()->table('qa_meta')->where('key', 'schema_version')->value('value') === (int) config('qa_demo.schema_version', 1);
        } catch (\Throwable) {
            return false;
        }
    }

    public function connection(): Connection
    {
        $path = $this->root().'/demo.sqlite';
        if (! is_file($path)) {
            throw new RuntimeException('La base privada de prueba aún no está preparada. Ejecute oncosavi:qa-demo-preparar.');
        }
        if (config('database.connections.qa_demo.database') !== $path) {
            DB::purge('qa_demo');
            config(['database.connections.qa_demo' => [
                'driver' => 'sqlite', 'database' => $path, 'prefix' => '',
                'foreign_key_constraints' => true, 'busy_timeout' => 10000,
                'journal_mode' => 'WAL', 'synchronous' => 'NORMAL',
            ]]);
        }
        return DB::connection('qa_demo');
    }

    /** CLI only: create a private database without running clinical migrations. */
    public function initialize(): void
    {
        if (! extension_loaded('pdo_sqlite')) {
            throw new RuntimeException('Se necesita la extensión pdo_sqlite para la base privada de QA.');
        }
        foreach (['', '/sessions', '/cache', '/views'] as $suffix) {
            $dir = $this->root().$suffix;
            if (! is_dir($dir) && ! mkdir($dir, 0700, true) && ! is_dir($dir)) {
                throw new RuntimeException('No se pudo crear el almacenamiento privado de QA.');
            }
            @chmod($dir, 0700);
        }
        $file = $this->root().'/demo.sqlite';
        if (! is_file($file)) {
            touch($file);
        }
        @chmod($file, 0600);
        QaDemoSchema::install($this->connection());
    }

    public function activate(): void
    {
        if (! $this->ready()) {
            throw new RuntimeException('Prepare primero la base de QA.');
        }
        if (file_put_contents($this->root().'/enabled.flag', $this->now()->toIso8601String(), LOCK_EX) === false) {
            throw new RuntimeException('No se pudo activar la ruta de prueba.');
        }
        @chmod($this->root().'/enabled.flag', 0600);
    }

    public function deactivate(): void
    {
        if ($this->enabled() && ! unlink($this->root().'/enabled.flag')) {
            throw new RuntimeException('No se pudo desactivar la ruta de prueba.');
        }
    }

    public function importCatalog(array $rows, string $source = 'Catálogo de Oncosavi'): int
    {
        if ($rows === []) {
            throw new RuntimeException('No se encontraron pruebas activas de química en el catálogo. No se activó la demostración.');
        }
        $db = $this->connection();
        $db->transaction(function () use ($rows, $source, $db): void {
            $db->table('qa_catalog')->update(['active' => false]);
            foreach ($rows as $row) {
                $db->table('qa_catalog')->updateOrInsert(['source_id' => (int) $row['source_id']], [
                    'name' => mb_substr((string) $row['name'], 0, 255),
                    'exam_name' => mb_substr((string) $row['exam_name'], 0, 255),
                    'unit' => mb_substr((string) ($row['unit'] ?? ''), 0, 40),
                    'area' => mb_substr((string) ($row['area'] ?? 'Química sanguínea'), 0, 255),
                    'active' => true,
                ]);
            }
            foreach (['catalog_imported_at' => $this->stamp(), 'catalog_source' => $source] as $key => $value) {
                $db->table('qa_meta')->updateOrInsert(['key' => $key], ['value' => $value]);
            }
        });
        return count($rows);
    }

    public function catalog(): array
    {
        return $this->connection()->table('qa_catalog')->where('active', true)->orderBy('name')->get()
            ->map(fn ($r) => ['id' => (int) $r->id, 'source_id' => (int) $r->source_id, 'name' => $r->name,
                'exam_name' => $r->exam_name, 'unit' => $r->unit, 'area' => $r->area])->all();
    }

    public function catalogInfo(): array
    {
        return $this->connection()->table('qa_meta')->whereIn('key', ['catalog_imported_at', 'catalog_source'])->pluck('value', 'key')->all();
    }

    public function equipment(): array
    {
        return $this->connection()->table('qa_equipment')->orderBy('name')->get()->map(fn ($r) => $this->equipmentRow($r))->all();
    }

    public function createEquipment(array $data): int
    {
        $valid = $this->equipmentInput($data);
        if ($this->connection()->table('qa_equipment')->count() >= 100) {
            $this->invalid('name', 'La prueba admite hasta 100 equipos.');
        }
        return (int) $this->connection()->table('qa_equipment')->insertGetId($valid + ['created_at' => $this->stamp(), 'updated_at' => $this->stamp()]);
    }

    public function updateEquipment(int $id, array $data): void
    {
        $this->equipmentById($id);
        $valid = $this->equipmentInput($data, $id);
        $this->connection()->table('qa_equipment')->where('id', $id)->update($valid + ['updated_at' => $this->stamp()]);
    }

    private function equipmentInput(array $data, ?int $id = null): array
    {
        $valid = Validator::make($data, [
            'code' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9_-]+$/D'],
            'name' => 'required|string|max:120', 'model' => 'nullable|string|max:160',
            'notes' => 'nullable|string|max:2000', 'active' => 'sometimes|boolean',
        ], [], ['code' => 'código', 'name' => 'nombre del equipo'])->validate();
        $exists = $this->connection()->table('qa_equipment')->where('code', $valid['code']);
        if ($id) {
            $exists->where('id', '!=', $id);
        }
        if ($exists->exists()) {
            $this->invalid('code', 'Ya existe un equipo con ese código.');
        }
        return ['code' => trim($valid['code']), 'name' => trim($valid['name']),
            'model' => trim($valid['model'] ?? ''), 'notes' => trim($valid['notes'] ?? ''),
            'active' => (bool) ($valid['active'] ?? true)];
    }

    public function configurations(?int $equipmentId = null): array
    {
        $q = $this->connection()->table('qa_configurations as c')->join('qa_equipment as e', 'e.id', '=', 'c.equipment_id');
        if ($equipmentId !== null) {
            $q->where('c.equipment_id', $equipmentId);
        }
        return $q->orderByDesc('c.active')->orderByDesc('c.id')->get(['c.*', 'e.name as equipment_name'])->map(fn ($r) => [
            'id' => (int) $r->id, 'equipment_id' => (int) $r->equipment_id, 'equipment_name' => $r->equipment_name,
            'version' => (int) $r->version, 'label' => $r->label, 'active' => (bool) $r->active,
            'configured_by' => $r->configured_by, 'created_at' => $r->created_at,
        ])->all();
    }

    public function configuration(int $id): array
    {
        $row = $this->find('qa_configurations', $id);
        $body = $this->decode($row->data_json);
        return array_merge($body, [
            'id' => (int) $row->id, 'equipment_id' => (int) $row->equipment_id, 'version' => (int) $row->version,
            'label' => $row->label, 'active' => (bool) $row->active, 'configured_by' => $row->configured_by,
            'created_at' => $row->created_at,
        ]);
    }

    public function saveConfiguration(array $data): int
    {
        Validator::make($data, [
            'equipment_id' => 'required|integer|min:1', 'configured_by' => 'required|string|max:120',
            'label' => 'required|string|max:150', 'levels' => 'required|array',
            'analytes' => 'required|array|min:1|max:'.(int) config('qa_demo.max_analytes', 50),
        ])->validate();
        $equipment = $this->equipmentById((int) $data['equipment_id']);
        if (! $equipment['active']) {
            $this->invalid('equipment_id', 'El equipo está inactivo. Actívelo antes de configurar nuevos controles.');
        }
        $levels = [];
        foreach (self::LEVELS as $key => $label) {
            $level = $data['levels'][$key] ?? [];
            Validator::make(['levels' => [$key => $level]], [
                "levels.$key.lot" => 'required|string|max:100', "levels.$key.product" => 'required|string|max:160',
                "levels.$key.expires_on" => 'required|date_format:Y-m-d',
                "levels.$key.opened_on" => 'required|date_format:Y-m-d',
                "levels.$key.stability_days" => 'required|integer|min:1|max:730',
            ])->validate();
            $opened = $this->date($level['opened_on'], "levels.$key.opened_on");
            $expires = $this->date($level['expires_on'], "levels.$key.expires_on");
            if ($opened->gt($expires)) {
                $this->invalid("levels.$key.opened_on", 'La apertura no puede ser posterior al vencimiento del lote.');
            }
            $openLimit = $opened->addDays((int) $level['stability_days']);
            $useUntil = $openLimit->lt($expires) ? $openLimit : $expires;
            $levels[$key] = ['label' => $label, 'lot' => trim($level['lot']), 'product' => trim($level['product']),
                'expires_on' => $expires->toDateString(), 'opened_on' => $opened->toDateString(),
                'stability_days' => (int) $level['stability_days'], 'use_until' => $useUntil->toDateString()];
        }
        $catalog = collect($this->catalog())->keyBy('id');
        $analytes = [];
        $seen = [];
        foreach ($data['analytes'] as $i => $input) {
            Validator::make(['analytes' => [$i => $input]], [
                "analytes.$i.catalog_id" => 'required|integer|min:1', "analytes.$i.method" => 'required|string|max:120',
                "analytes.$i.unit" => 'required|string|max:40', "analytes.$i.targets" => 'required|array',
            ])->validate();
            $catalogId = (int) $input['catalog_id'];
            if (! $catalog->has($catalogId) || isset($seen[$catalogId])) {
                $this->invalid("analytes.$i.catalog_id", 'Seleccione pruebas diferentes del catálogo importado.');
            }
            $seen[$catalogId] = true;
            $targets = [];
            foreach (self::LEVELS as $key => $label) {
                $target = $input['targets'][$key] ?? [];
                $min = $this->number($target['min'] ?? null, "analytes.$i.targets.$key.min");
                $max = $this->number($target['max'] ?? null, "analytes.$i.targets.$key.max");
                $value = $this->number($target['target'] ?? null, "analytes.$i.targets.$key.target");
                if ($min > $max || $value < $min || $value > $max) {
                    $this->invalid("analytes.$i.targets.$key.target", 'El valor esperado debe estar entre el mínimo y el máximo.');
                }
                $targets[$key] = ['target' => $value, 'min' => $min, 'max' => $max];
            }
            $entry = $catalog->get($catalogId);
            $analytes[] = ['catalog_id' => $catalogId, 'source_id' => $entry['source_id'], 'name' => $entry['name'],
                'exam_name' => $entry['exam_name'], 'method' => trim($input['method']),
                'unit' => trim($input['unit']), 'targets' => $targets];
        }
        $db = $this->connection();
        return $db->transaction(function () use ($db, $equipment, $levels, $analytes, $data): int {
            $version = (int) $db->table('qa_configurations')->where('equipment_id', $equipment['id'])->max('version') + 1;
            $db->table('qa_configurations')->where('equipment_id', $equipment['id'])->update(['active' => false]);
            return (int) $db->table('qa_configurations')->insertGetId([
                'equipment_id' => $equipment['id'], 'version' => $version, 'label' => trim($data['label']),
                'configured_by' => trim($data['configured_by']), 'active' => true, 'created_at' => $this->stamp(),
                'data_json' => $this->encode(['equipment' => $equipment, 'levels' => $levels, 'analytes' => $analytes]),
            ]);
        });
    }

    public function overview(): array
    {
        $db = $this->connection();
        $next = $this->now()->startOfDay()->addDay();
        while (! in_array($next->dayOfWeekIso, [1, 3, 5], true)) {
            $next = $next->addDay();
        }
        return ['equipment_count' => $db->table('qa_equipment')->count(),
            'configuration_count' => $db->table('qa_configurations')->where('active', true)->count(),
            'run_count' => $db->table('qa_runs')->where('status', 'submitted')->count(),
            'pending_count' => $db->table('qa_runs')->where('status', 'submitted')->where('review_status', '!=', 'reviewed')->count(),
            'recent_runs' => array_slice($this->runs(), 0, 8), 'next_scheduled_date' => $next->toDateString(),
            'today' => $this->now()->toDateString(), 'catalog_info' => $this->catalogInfo()];
    }

    public function runs(array $filters = []): array
    {
        $q = $this->runQuery($filters);
        return $q->orderByDesc('measured_at')->orderByDesc('id')->limit(500)->get()->map(fn ($row) => $this->runRow($row))->all();
    }

    public function countRuns(array $filters = []): int
    {
        return $this->runQuery($filters)->count();
    }

    private function runQuery(array $filters)
    {
        $q = $this->connection()->table('qa_runs');
        foreach (['equipment_id', 'configuration_id', 'parent_run_id', 'event_id'] as $field) {
            if (isset($filters[$field]) && $filters[$field] !== '') {
                $q->where($field, (int) $filters[$field]);
            }
        }
        foreach (['comparison' => ['within', 'outside', 'incomplete'], 'review_status' => ['pending', 'followup', 'reviewed'], 'status' => ['draft', 'submitted']] as $field => $allowed) {
            if (in_array($filters[$field] ?? null, $allowed, true)) {
                $q->where($field, $filters[$field]);
            }
        }
        if (! empty($filters['from'])) {
            $q->where('measured_at', '>=', $this->date((string) $filters['from'], 'from')->toDateString().' 00:00:00');
        }
        if (! empty($filters['to'])) {
            $q->where('measured_at', '<=', $this->date((string) $filters['to'], 'to')->toDateString().' 23:59:59');
        }
        return $q;
    }

    public function run(int $id): array
    {
        $run = $this->runRow($this->find('qa_runs', $id));
        $run['reviews'] = $this->connection()->table('qa_reviews')->where('run_id', $id)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
        $run['related_runs'] = $this->runs(['parent_run_id' => $id]);
        return $run;
    }

    private function runRow(object $row): array
    {
        $snapshot = $this->decode($row->configuration_snapshot);
        $result = (array) $row;
        foreach (['id', 'equipment_id', 'configuration_id'] as $field) {
            $result[$field] = (int) $result[$field];
        }
        foreach (['parent_run_id', 'event_id'] as $field) {
            $result[$field] = $result[$field] === null ? null : (int) $result[$field];
        }
        unset($result['measurements_json']);
        $result['equipment'] = $snapshot['equipment'];
        $result['configuration_snapshot'] = $snapshot;
        $result['measurements'] = $this->decode($row->measurements_json);
        $result['reviews'] = [];
        return $result;
    }

    public function recordRun(array $data, bool $draft = false, ?int $id = null): int
    {
        Validator::make($data, [
            'configuration_id' => 'required|integer|min:1', 'measured_at' => 'required|string|max:25',
            'operator' => 'required|string|max:120', 'kind' => 'required|in:'.implode(',', self::KINDS),
            'parent_run_id' => 'nullable|integer|min:1', 'event_id' => 'nullable|integer|min:1',
            'notes' => 'nullable|string|max:4000', 'missing_reason' => 'nullable|string|max:2000', 'values' => 'required|array',
        ])->validate();
        $configuration = $this->configuration((int) $data['configuration_id']);
        $measured = $this->dateTime($data['measured_at'], 'measured_at');
        if ($measured->gt($this->now()->addMinute())) {
            $this->invalid('measured_at', 'La medición no puede tener una fecha u hora futura.');
        }
        $equipment = $this->equipmentById($configuration['equipment_id']);
        if (! $equipment['active']) {
            $this->invalid('configuration_id', 'El equipo está inactivo.');
        }
        $linked = in_array($data['kind'], ['repeat', 'correction'], true);
        $parentId = ! empty($data['parent_run_id']) ? (int) $data['parent_run_id'] : null;
        $parent = null;
        if ($parentId) {
            $parent = $this->run($parentId);
            if ($parent['status'] !== 'submitted' || $parent['configuration_id'] !== $configuration['id']) {
                $this->invalid('parent_run_id', 'La repetición o corrección debe corresponder al mismo equipo y configuración de un registro presentado.');
            }
            if (! $linked) {
                $this->invalid('kind', 'Seleccione repetición o corrección para vincular el registro anterior.');
            }
        } elseif ($linked) {
            $this->invalid('parent_run_id', 'Seleccione el registro original de esta repetición o corrección.');
        }
        if ($data['kind'] === 'correction' && trim($data['notes'] ?? '') === '') {
            $this->invalid('notes', 'Explique el motivo de la corrección. El dato original se conservará.');
        }
        if ($parent && $data['kind'] === 'correction'
            && $measured->format('Y-m-d H:i:s') !== $parent['measured_at']) {
            $this->invalid('measured_at', 'Una corrección de transcripción conserva la fecha y hora de la medición original. La fecha de captura nueva se guarda aparte.');
        }
        if ($parent && $data['kind'] === 'repeat' && $measured->lt(CarbonImmutable::parse($parent['measured_at'], $this->timezone()))) {
            $this->invalid('measured_at', 'La repetición no puede ser anterior a la medición original.');
        }
        $eventId = ! empty($data['event_id']) ? (int) $data['event_id'] : null;
        if ($eventId) {
            $event = $this->event($eventId);
            if ($event['equipment_id'] !== $configuration['equipment_id']) {
                $this->invalid('event_id', 'El evento pertenece a otro equipo.');
            }
            $eventAt = CarbonImmutable::parse($event['occurred_at'], $this->timezone());
            if (($data['kind'] === 'before_event' && $measured->gt($eventAt)) || ($data['kind'] === 'after_event' && $measured->lt($eventAt))) {
                $this->invalid('measured_at', 'La hora de la medición debe corresponder al antes o después del evento seleccionado.');
            }
        } elseif (in_array($data['kind'], ['before_event', 'after_event'], true)) {
            $this->invalid('event_id', 'Seleccione el evento de calibración o mantenimiento relacionado.');
        }
        $measurements = [];
        $parentPairs = $parent ? array_fill_keys(array_map(
            fn (array $measurement): string => $measurement['catalog_id'].':'.$measurement['level'],
            $parent['measurements'],
        ), true) : [];
        $outside = false;
        $missing = false;
        $known = array_column($configuration['analytes'], 'catalog_id');
        foreach ($data['values'] as $catalogId => $values) {
            if (! in_array((int) $catalogId, $known, true) || ! is_array($values)) {
                $this->invalid('values', 'Las lecturas deben pertenecer a las pruebas de esta configuración.');
            }
            if (array_diff(array_keys($values), array_keys(self::LEVELS))) {
                $this->invalid('values', 'Se recibió un nivel de control desconocido.');
            }
        }
        foreach ($configuration['analytes'] as $analyte) {
            foreach (self::LEVELS as $level => $label) {
                $raw = $data['values'][$analyte['catalog_id']][$level] ?? '';
                if (is_array($raw) || is_object($raw)) {
                    $this->invalid('values', 'Cada lectura debe ser un número.');
                }
                $raw = trim((string) $raw);
                if ($linked && $raw === '') {
                    continue;
                }
                if ($linked && ! isset($parentPairs[$analyte['catalog_id'].':'.$level])) {
                    $this->invalid("values.{$analyte['catalog_id']}.$level", 'Esta prueba y nivel no pertenecen al registro original seleccionado.');
                }
                $value = $raw === '' ? null : $this->number($raw, "values.{$analyte['catalog_id']}.$level");
                $target = $analyte['targets'][$level];
                if ($value !== null) {
                    $lot = $configuration['levels'][$level];
                    $date = $measured->toDateString();
                    if ($date < $lot['opened_on'] || $date > $lot['use_until']) {
                        $this->invalid("values.{$analyte['catalog_id']}.$level", "El control $label no está vigente en la fecha de medición: {$lot['opened_on']} a {$lot['use_until']}.");
                    }
                }
                $status = $value === null ? 'missing' : (($value < $target['min'] || $value > $target['max']) ? 'outside' : 'within');
                $outside = $outside || $status === 'outside';
                $missing = $missing || $status === 'missing';
                $measurements[] = ['catalog_id' => $analyte['catalog_id'], 'analyte_name' => $analyte['name'],
                    'unit' => $analyte['unit'], 'level' => $level, 'raw_value' => $raw, 'value' => $value,
                    'target' => $target['target'], 'min' => $target['min'], 'max' => $target['max'], 'status' => $status];
            }
        }
        if ($linked && $measurements === []) {
            $this->invalid('values', 'Escriba al menos la lectura que se repitió o corrigió.');
        }
        if ($missing && ! $draft && trim($data['missing_reason'] ?? '') === '') {
            $this->invalid('missing_reason', 'Indique el motivo de las lecturas no realizadas; no se rellenan con cero.');
        }
        $db = $this->connection();
        return $db->transaction(function () use ($data, $draft, $id, $configuration, $measured, $parentId, $eventId, $measurements, $outside, $missing, $db): int {
            if ($id) {
                $existing = $this->find('qa_runs', $id);
                if ($existing->status !== 'draft') {
                    $this->invalid('run', 'Las mediciones presentadas se conservan. Registre una repetición o corrección vinculada.');
                }
            }
            $values = [
                'equipment_id' => $configuration['equipment_id'], 'configuration_id' => $configuration['id'],
                'measured_at' => $measured->format('Y-m-d H:i:s'), 'operator' => trim($data['operator']),
                'kind' => $data['kind'], 'parent_run_id' => $parentId, 'event_id' => $eventId,
                'status' => $draft ? 'draft' : 'submitted', 'comparison' => $outside ? 'outside' : ($missing ? 'incomplete' : 'within'),
                'review_status' => 'pending', 'notes' => trim($data['notes'] ?? ''), 'missing_reason' => trim($data['missing_reason'] ?? ''),
                'configuration_snapshot' => $this->encode($configuration), 'measurements_json' => $this->encode($measurements),
                'updated_at' => $this->stamp(),
            ];
            if ($id) {
                $changed = $db->table('qa_runs')->where('id', $id)->where('status', 'draft')->update($values);
                if (! $changed) {
                    $this->invalid('run', 'Este borrador ya fue presentado. Recargue la página.');
                }
            } else {
                if ($db->table('qa_runs')->count() >= 20000) {
                    $this->invalid('values', 'Se alcanzó el límite de registros de la demostración.');
                }
                $id = (int) $db->table('qa_runs')->insertGetId($values + ['captured_at' => $this->stamp(), 'created_at' => $this->stamp()]);
                $db->table('qa_runs')->where('id', $id)->update(['number' => 'QC-'.$measured->format('ymd').'-'.str_pad((string) $id, 4, '0', STR_PAD_LEFT)]);
            }
            return $id;
        });
    }

    public function reviewRun(int $id, array $data): int
    {
        Validator::make($data, ['reviewer' => 'required|string|max:120', 'decision' => 'required|in:reviewed,followup', 'notes' => 'required|string|max:4000'])->validate();
        $db = $this->connection();
        return $db->transaction(function () use ($db, $id, $data): int {
            $run = $this->find('qa_runs', $id);
            if ($run->status !== 'submitted') {
                $this->invalid('decision', 'Presente primero las mediciones del borrador antes de revisarlas.');
            }
            $review = (int) $db->table('qa_reviews')->insertGetId(['run_id' => $id, 'reviewer' => trim($data['reviewer']),
                'decision' => $data['decision'], 'notes' => trim($data['notes']), 'reviewed_at' => $this->stamp()]);
            $db->table('qa_runs')->where('id', $id)->update(['review_status' => $data['decision'], 'updated_at' => $this->stamp()]);
            return $review;
        });
    }

    public function recordEvent(array $data): int
    {
        Validator::make($data, ['equipment_id' => 'required|integer|min:1', 'type' => 'required|in:calibration,maintenance',
            'occurred_at' => 'required|string|max:25', 'operator' => 'required|string|max:120', 'notes' => 'required|string|max:4000'])->validate();
        $this->equipmentById((int) $data['equipment_id']);
        $occurred = $this->dateTime($data['occurred_at'], 'occurred_at');
        if ($occurred->gt($this->now()->addMinute())) {
            $this->invalid('occurred_at', 'El evento no puede tener una fecha u hora futura.');
        }
        return (int) $this->connection()->table('qa_events')->insertGetId([
            'equipment_id' => (int) $data['equipment_id'], 'type' => $data['type'],
            'occurred_at' => $occurred->format('Y-m-d H:i:s'), 'operator' => trim($data['operator']),
            'notes' => trim($data['notes']), 'created_at' => $this->stamp(),
        ]);
    }

    public function events(array $filters = []): array
    {
        $q = $this->connection()->table('qa_events as v')->join('qa_equipment as e', 'e.id', '=', 'v.equipment_id');
        if (! empty($filters['equipment_id'])) {
            $q->where('v.equipment_id', (int) $filters['equipment_id']);
        }
        return $q->orderByDesc('v.occurred_at')->limit(500)->get(['v.*', 'e.name as equipment_name'])->map(function ($row): array {
            $r = (array) $row;
            $r['id'] = (int) $r['id']; $r['equipment_id'] = (int) $r['equipment_id'];
            return $r;
        })->all();
    }

    public function event(int $id): array
    {
        $row = $this->find('qa_events', $id);
        $r = (array) $row;
        $r['id'] = (int) $r['id']; $r['equipment_id'] = (int) $r['equipment_id'];
        $r['equipment_name'] = $this->equipmentById($r['equipment_id'])['name'];
        $r['runs'] = $this->runs(['event_id' => $id]);
        return $r;
    }

    public function chartData(array $filters = []): array
    {
        $configurationId = (int) ($filters['configuration_id'] ?? 0);
        if (! $configurationId) {
            $configurationId = (int) ($this->configurations(isset($filters['equipment_id']) && $filters['equipment_id'] !== '' ? (int) $filters['equipment_id'] : null)[0]['id'] ?? 0);
        }
        if (! $configurationId) {
            return [];
        }
        $configuration = $this->configuration($configurationId);
        if (! empty($filters['equipment_id']) && (int) $filters['equipment_id'] !== $configuration['equipment_id']) {
            $this->invalid('configuration_id', 'La configuración seleccionada pertenece a otro equipo.');
        }
        $catalogId = (int) ($filters['catalog_id'] ?? $configuration['analytes'][0]['catalog_id']);
        $analyte = collect($configuration['analytes'])->firstWhere('catalog_id', $catalogId);
        if (! $analyte) {
            $this->invalid('catalog_id', 'Seleccione una prueba de esta configuración.');
        }
        $levels = [];
        foreach (self::LEVELS as $level => $label) {
            $levels[$level] = ['label' => $label, 'lot' => $configuration['levels'][$level]['lot'], 'unit' => $analyte['unit']]
                + $analyte['targets'][$level] + ['points' => []];
        }
        $chartFilters = array_merge($filters, ['configuration_id' => $configurationId, 'status' => 'submitted']);
        $totalRuns = $this->countRuns($chartFilters);
        $runs = array_reverse($this->runs($chartFilters));
        foreach ($runs as $run) {
            foreach ($run['measurements'] as $measurement) {
                if ($measurement['catalog_id'] === $catalogId && $measurement['value'] !== null) {
                    $levels[$measurement['level']]['points'][] = [
                        'run_id' => $run['id'], 'number' => $run['number'], 'measured_at' => $run['measured_at'],
                        'value' => $measurement['value'], 'status' => $measurement['status'],
                        'kind' => $run['kind'], 'operator' => $run['operator'],
                    ];
                }
            }
        }
        return ['equipment' => $configuration['equipment'], 'configuration' => $configuration, 'analyte' => $analyte, 'levels' => $levels,
            'total_runs' => $totalRuns, 'truncated' => $totalRuns > 500];
    }

    public function exportRows(array $filters = []): array
    {
        $rows = [];
        foreach ($this->runs($filters) as $run) {
            foreach ($run['measurements'] as $m) {
                $lot = $run['configuration_snapshot']['levels'][$m['level']];
                $rows[] = ['Registro' => $run['number'], 'Equipo' => $run['equipment']['name'],
                    'Configuración' => $run['configuration_snapshot']['label'], 'Versión' => $run['configuration_snapshot']['version'],
                    'Fecha de medición' => $run['measured_at'], 'Fecha de captura' => $run['captured_at'],
                    'Registrado por (declarado)' => $run['operator'], 'Tipo' => $run['kind'], 'Prueba' => $m['analyte_name'],
                    'Nivel' => self::LEVELS[$m['level']], 'Lote' => $lot['lot'], 'Valor medido' => $m['raw_value'],
                    'Unidad' => $m['unit'], 'Valor esperado' => $m['target'], 'Mínimo' => $m['min'], 'Máximo' => $m['max'],
                    'Comparación' => $m['status'], 'Estado' => $run['status'], 'Revisión' => $run['review_status'],
                    'Registro relacionado' => $run['parent_run_id'] ?? '', 'Evento' => $run['event_id'] ?? '',
                    'Motivo no realizado' => $run['missing_reason'], 'Notas' => $run['notes']];
            }
        }
        return $rows;
    }

    public function meta(string $key): ?string
    {
        return $this->connection()->table('qa_meta')->where('key', $key)->value('value');
    }

    public function setMeta(string $key, string $value): void
    {
        $this->connection()->table('qa_meta')->updateOrInsert(['key' => $key], ['value' => $value]);
    }

    private function equipmentById(int $id): array
    {
        return $this->equipmentRow($this->find('qa_equipment', $id));
    }

    private function equipmentRow(object $r): array
    {
        return ['id' => (int) $r->id, 'code' => $r->code, 'name' => $r->name, 'model' => $r->model,
            'notes' => $r->notes, 'active' => (bool) $r->active];
    }

    private function find(string $table, int $id): object
    {
        $row = $this->connection()->table($table)->where('id', $id)->first();
        if (! $row) {
            throw (new ModelNotFoundException)->setModel($table, [$id]);
        }
        return $row;
    }

    private function number(mixed $raw, string $field): float
    {
        if (! is_string($raw) && ! is_int($raw) && ! is_float($raw)) {
            $this->invalid($field, 'Escriba un número con coma o punto decimal, sin separadores de miles.');
        }
        $raw = trim((string) $raw);
        if (! preg_match('/^[+-]?\d+(?:[.,]\d{1,6})?$/D', $raw)) {
            $this->invalid($field, 'Escriba un número con coma o punto decimal y hasta seis decimales, sin separadores de miles.');
        }
        $value = (float) str_replace(',', '.', $raw);
        if (! is_finite($value) || abs($value) > 1000000000000) {
            $this->invalid($field, 'El número excede el límite permitido para esta demostración.');
        }
        return round($value, 6);
    }

    private function date(string $raw, string $field): CarbonImmutable
    {
        $d = DateTimeImmutable::createFromFormat('!Y-m-d', $raw, new DateTimeZone($this->timezone()));
        $errors = DateTimeImmutable::getLastErrors();
        if (! $d || ($errors && ($errors['warning_count'] || $errors['error_count'])) || $d->format('Y-m-d') !== $raw) {
            $this->invalid($field, 'Escriba una fecha válida.');
        }
        return CarbonImmutable::instance($d);
    }

    private function dateTime(string $raw, string $field): CarbonImmutable
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}(?::\d{2})?$/D', $raw)) {
            $this->invalid($field, 'Escriba la fecha y hora de la medición.');
        }
        $value = str_replace(' ', 'T', $raw);
        if (strlen($value) === 16) {
            $value .= ':00';
        }
        $d = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s', $value, new DateTimeZone($this->timezone()));
        $errors = DateTimeImmutable::getLastErrors();
        if (! $d || ($errors && ($errors['warning_count'] || $errors['error_count'])) || $d->format('Y-m-d\TH:i:s') !== $value) {
            $this->invalid($field, 'Escriba una fecha y hora válidas.');
        }
        return CarbonImmutable::instance($d);
    }

    private function timezone(): string
    {
        return (string) config('qa_demo.timezone', 'America/El_Salvador');
    }

    private function now(): CarbonImmutable
    {
        return CarbonImmutable::now($this->timezone());
    }

    private function stamp(): string
    {
        return $this->now()->format('Y-m-d H:i:s');
    }

    private function encode(array $data): string
    {
        return json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
    }

    private function decode(string $data): array
    {
        return json_decode($data, true, 512, JSON_THROW_ON_ERROR);
    }

    private function invalid(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
