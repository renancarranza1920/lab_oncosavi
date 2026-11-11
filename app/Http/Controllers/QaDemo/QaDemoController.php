<?php

namespace App\Http\Controllers\QaDemo;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ControlCalidadPermissions as Permission;
use App\QaDemo\QaDemoPresentation;
use App\QaDemo\QaDemoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QaDemoController extends Controller
{
    public function __construct(private readonly QaDemoService $qa) {}

    public function index(): View
    {
        $summaryChart = null;
        if (request()->user()->can(Permission::CHARTS)) {
            $configuration = $this->selectedConfiguration(request());
            if ($configuration !== null) {
                $summaryChart = $this->qa->chartData(['configuration_id' => $configuration['id']]);
            }
        }

        return $this->page('qa-demo.index', $this->common() + [
            'overview' => $this->qa->overview(), 'summaryChart' => $summaryChart,
        ]);
    }

    public function configuration(Request $request): View
    {
        $equipmentId = $this->optionalId($request, 'equipment_id');
        $configurationId = $this->optionalId($request, 'configuration_id')
            ?? $this->optionalId($request, 'configuration');
        $configurations = $this->qa->configurations($equipmentId);
        $selected = $configurationId !== null
            ? $this->qa->configuration($configurationId)
            : ($configurations !== [] ? $this->qa->configuration((int) $configurations[0]['id']) : null);

        return $this->page('qa-demo.configuration', $this->common() + [
            'catalog' => $this->qa->catalog(),
            'tests' => $this->qa->tests(),
            'products' => $this->qa->products(),
            'lots' => $this->qa->lots(),
            'events' => $this->qa->events($equipmentId !== null ? ['equipment_id' => $equipmentId] : []),
            'selectedEvent' => $this->optionalId($request, 'event_id') !== null ? $this->qa->event($this->optionalId($request, 'event_id')) : null,
            'selectedConfiguration' => $selected,
            'selectedEquipmentId' => $equipmentId ?? ($selected['equipment_id'] ?? null),
        ]);
    }

    public function storeEquipment(Request $request): RedirectResponse
    {
        $data = $this->equipmentData($request);
        $id = $this->qa->createEquipment($data);

        return redirect()->route('qa-demo.configuration.index', ['equipment_id' => $id])
            ->with('success', 'Equipo registrado. Ahora puede configurar sus controles.');
    }

    public function updateEquipment(Request $request, int $equipment): RedirectResponse
    {
        $data = $this->equipmentData($request);
        if (! $request->has('active')) {
            $current = collect($this->qa->equipment())->firstWhere('id', $equipment);
            abort_unless($current !== null, 404);
            $data['active'] = (bool) $current['active'];
        }
        $this->qa->updateEquipment($equipment, $data);

        return redirect()->route('qa-demo.configuration.index', ['equipment_id' => $equipment])
            ->with('success', 'Datos del equipo actualizados. El historial de mediciones se conserva.');
    }

    public function storeProduct(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:160']]);
        $this->qa->createProduct($data);
        return redirect()->route('qa-demo.configuration.index', ['tab' => 'productos'])
            ->with('success', 'Producto de control registrado.');
    }

    public function storeLot(Request $request): RedirectResponse
    {
        if (! $request->filled('lot') && $request->filled('number')) {
            $request->merge(['lot' => $request->input('number')]);
        }
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'min:1'], 'name' => ['required', 'string', 'max:160'],
            'lot' => ['required', 'string', 'max:100'], 'expires_on' => ['required', 'date_format:Y-m-d'],
        ]);
        $this->qa->createLot($data);
        return redirect()->route('qa-demo.configuration.index', ['tab' => 'productos'])
            ->with('success', 'Lote de control registrado.');
    }

    public function storeTest(Request $request): RedirectResponse
    {
        $this->qa->createTest($this->testData($request));
        return redirect()->route('qa-demo.configuration.index', ['tab' => 'pruebas'])
            ->with('success', 'Prueba de Control de Calidad Interno creada.');
    }

    public function updateTest(Request $request, int $test): RedirectResponse
    {
        $this->qa->updateTest($test, $this->testData($request));
        return redirect()->route('qa-demo.configuration.index', ['tab' => 'pruebas'])
            ->with('success', 'Prueba de Control de Calidad Interno actualizada.');
    }

    public function storeConfiguration(Request $request): RedirectResponse
    {
        $this->decodePayload($request, 'config_payload', ['levels', 'analytes']);
        $data = $request->validate([
            'equipment_id' => ['required', 'integer', 'min:1'],
            'label' => ['required', 'string', 'max:150'],
            'levels' => ['required', 'array', 'min:1', 'max:50'],
            'levels.*' => ['required', 'array:label,lot_id,lot,product,expires_on,opened_on,stability_days,use_until'],
            'levels.*.label' => ['sometimes', 'string', 'max:80'],
            'levels.*.lot_id' => ['nullable', 'integer', 'min:1'],
            'levels.*.lot' => ['sometimes', 'string', 'max:100'],
            'levels.*.product' => ['sometimes', 'string', 'max:160'],
            'levels.*.expires_on' => ['sometimes', 'date_format:Y-m-d'],
            'levels.*.opened_on' => ['required', 'date_format:Y-m-d'],
            'levels.*.stability_days' => ['required', 'integer', 'min:1', 'max:730'],
            'analytes' => ['required', 'array', 'min:1', 'max:50'],
            'analytes.*' => ['required', 'array:catalog_id,method,unit,targets'],
            'analytes.*.catalog_id' => ['required', 'integer', 'min:1', 'distinct'],
            'analytes.*.method' => ['required', 'string', 'max:120'],
            'analytes.*.unit' => ['required', 'string', 'max:40'],
            'analytes.*.targets' => ['required', 'array', 'min:1', 'max:50'],
            'analytes.*.targets.*' => ['required', 'array:target,min,max'],
            'analytes.*.targets.*.target' => ['required', $this->scalarNumberRule()],
            'analytes.*.targets.*.min' => ['required', $this->scalarNumberRule()],
            'analytes.*.targets.*.max' => ['required', $this->scalarNumberRule()],
        ]);
        $data['configured_by'] = $request->user()->name;
        $id = $this->qa->saveConfiguration($data);
        return redirect()->route('qa-demo.configuration.index', ['configuration_id' => $id, 'tab' => 'controles'])
            ->with('success', 'Configuración guardada. Las mediciones anteriores conservan su versión.');
    }

    public function runs(Request $request): View
    {
        $filters = $this->filters($request);

        return $this->page('qa-demo.runs.index', $this->common($filters) + [
            'runs' => $this->qa->runs($filters),
            'runsTotal' => $this->qa->countRuns($filters),
        ]);
    }

    public function createRun(Request $request): View
    {
        $parentId = $this->optionalId($request, 'parent_run_id');
        $parent = $parentId !== null ? $this->qa->run($parentId) : null;
        abort_if($parent !== null && ! empty($parent['deleted_at']), 409, 'Este registro está eliminado y no admite nuevas mediciones relacionadas.');
        $configuration = $parent !== null
            ? $this->qa->configuration((int) $parent['configuration_id'])
            : $this->selectedConfiguration($request);
        $kind = (string) $request->query('kind', $parent !== null ? 'repeat' : 'routine');
        abort_unless(in_array($kind, ['routine', 'additional', 'repeat', 'correction', 'before_event', 'after_event'], true), 422, 'Tipo de registro desconocido.');
        if (in_array($kind, ['repeat', 'correction'], true)) {
            abort_unless($parent !== null, 422, 'Seleccione el registro original para repetirlo o corregirlo.');
        }

        return $this->page('qa-demo.runs.form', $this->common() + [
            'configuration' => $configuration,
            'run' => null,
            'parentRun' => $parent,
            'kind' => $kind,
            'events' => $this->qa->events($configuration !== null ? ['equipment_id' => $configuration['equipment_id']] : []),
        ]);
    }

    public function storeRun(Request $request): RedirectResponse
    {
        $data = $this->runData($request);
        $draft = $request->input('action', 'submit') === 'draft';
        if ($draft) {
            abort_unless($request->user()->can(Permission::EDIT), 403);
        }
        $id = $this->qa->recordRun($data, $draft);
        return $this->afterRun($request, $id, $draft)
            ->with('success', $draft ? 'Borrador guardado.' : 'Control de Calidad Interno registrado.');
    }

    public function showRun(int $run): View
    {
        $record = $this->qa->run($run);
        $related = $record['related_runs'] ?? $this->qa->runs(['parent_run_id' => $run]);

        return $this->page('qa-demo.runs.show', $this->common() + [
            'run' => $record,
            'relatedRuns' => $related,
            'events' => $this->qa->events(['equipment_id' => $record['equipment']['id']]),
        ]);
    }

    public function editRun(int $run): View
    {
        $record = $this->qa->run($run);
        abort_if(! empty($record['deleted_at']), 409, 'Este registro está eliminado y se conserva en el historial.');

        return $this->page('qa-demo.runs.form', $this->common() + [
            'configuration' => $this->qa->configuration((int) $record['configuration_id']),
            'run' => $record,
            'parentRun' => ! empty($record['parent_run_id']) ? $this->qa->run((int) $record['parent_run_id']) : null,
            'kind' => $record['kind'],
            'events' => $this->qa->events(['equipment_id' => $record['equipment']['id']]),
        ]);
    }

    public function updateRun(Request $request, int $run): RedirectResponse
    {
        $record = $this->qa->run($run);
        $draft = $record['status'] === 'draft' && $request->input('action', 'draft') === 'draft';
        $this->qa->updateRun($run, $this->runData($request, $record), $draft);
        return $this->afterRun($request, $run, $draft)
            ->with('success', 'Registro actualizado. La versión anterior se conserva en el historial.');
    }

    public function destroyRun(Request $request, int $run): RedirectResponse
    {
        $request->merge(['reason' => $request->input('reason', $request->input('deletion_reason'))]);
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
            'expected_revision' => ['required', 'integer', 'min:1'],
        ]);
        $this->qa->deleteRun($run, $data + $this->actor($request));
        return redirect()->route($request->user()->can(Permission::HISTORY) ? 'qa-demo.runs.index' : 'qa-demo.index')
            ->with('success', 'Registro eliminado. Su antecedente se conserva en el historial.');
    }

    public function reviewRun(Request $request, int $run): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', 'in:reviewed,followup'],
            'notes' => ['required', 'string', 'max:4000'],
            'expected_revision' => ['required', 'integer', 'min:1'],
        ]);
        $this->qa->reviewRun($run, $data + ['reviewer' => $request->user()->name] + $this->actor($request));

        return redirect()->route('qa-demo.runs.show', ['run' => $run])
            ->with('success', 'Revisión registrada. Las lecturas originales se conservan.');
    }

    public function charts(Request $request): View
    {
        $filters = $this->filters($request);
        $configuration = $this->selectedConfiguration($request);
        $chart = null;
        if ($configuration !== null) {
            $filters['configuration_id'] = $configuration['id'];
            $catalogId = $this->optionalId($request, 'catalog_id') ?? ($configuration['analytes'][0]['catalog_id'] ?? null);
            if ($catalogId !== null) {
                $filters['catalog_id'] = (int) $catalogId;
                $chart = $this->qa->chartData($filters);
            }
        }

        return $this->page('qa-demo.charts', $this->common($filters) + [
            'chart' => $chart,
            'configuration' => $configuration,
        ]);
    }

    public function events(Request $request): RedirectResponse
    {
        return redirect()->route('qa-demo.configuration.index', array_merge($request->query(), ['tab' => 'eventos']))->withFragment('eventos');
    }

    public function storeEvent(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'equipment_id' => ['required', 'integer', 'min:1'],
            'type' => ['required', 'in:calibration,maintenance'],
            'occurred_at' => ['required', 'date'],
            'notes' => ['required', 'string', 'max:4000'],
        ]);
        $id = $this->qa->recordEvent($data + ['operator' => $request->user()->name] + $this->actor($request));

        return redirect()->route('qa-demo.configuration.index', ['equipment_id' => $data['equipment_id'], 'event_id' => $id, 'tab' => 'eventos'])->withFragment('eventos')
            ->with('success', 'Evento del equipo registrado. Puede relacionar controles anteriores y posteriores.');
    }

    public function csv(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);
        if ($this->qa->countRuns($filters) > 500) {
            abort(422, 'El archivo supera 500 registros. Seleccione un periodo o equipo más específico.');
        }
        $rows = $this->exportRows($filters);

        return response()->streamDownload(function () use ($rows): void {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            foreach ($rows as $row) {
                fputcsv($stream, array_map($this->safeCsvCell(...), $row), ';', '"', '');
            }
            fclose($stream);
        }, 'oncosavi-qa-controles.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function pdf(Request $request): Response
    {
        $filters = $this->filters($request);
        if ($this->qa->countRuns($filters) > 30) {
            abort(422, 'El informe admite hasta 30 registros. Seleccione un periodo o equipo más específico.');
        }
        $runs = $this->qa->runs($filters);
        $fullRuns = array_map(fn (array $run): array => $this->qa->run((int) $run['id']), $runs);
        if (isset($filters['catalog_id'])) {
            foreach ($fullRuns as &$run) {
                $run['measurements'] = array_values(array_filter($run['measurements'], fn (array $measurement): bool => (int) $measurement['catalog_id'] === (int) $filters['catalog_id']));
            }
            unset($run);
            $fullRuns = array_values(array_filter($fullRuns, fn (array $run): bool => $run['measurements'] !== []));
        }
        if (array_sum(array_map(fn (array $run): int => count($run['measurements']), $fullRuns)) > 1000) {
            abort(422, 'El informe contiene muchas lecturas. Reduzca el periodo o seleccione un equipo.');
        }
        $chart = null;
        if (isset($filters['configuration_id'], $filters['catalog_id'])) {
            $chart = $this->qa->chartData($filters);
        }

        return Pdf::setOptions([
            'isRemoteEnabled' => false,
            'isHtml5ParserEnabled' => true,
            'dpi' => 96,
            'defaultFont' => 'DejaVu Sans',
            'chroot' => base_path(),
        ])->loadView('qa-demo.pdf', QaDemoPresentation::data([
            'runs' => $fullRuns,
            'filters' => $filters,
            'chart' => $chart,
            'generatedAt' => now('America/El_Salvador'),
            'logoPath' => public_path('images/oncosavi.png'),
        ]))->setPaper('a4', 'landscape')->download('oncosavi-qa-controles.pdf');
    }

    private function page(string $view, array $data): View
    {
        return view($view, QaDemoPresentation::data($data));
    }

    private function common(array $filters = []): array
    {
        $user = request()->user();
        $abilities = [
            'view' => Permission::VIEW, 'maintain' => Permission::MAINTAIN, 'register' => Permission::REGISTER,
            'edit' => Permission::EDIT, 'delete' => Permission::DELETE, 'charts' => Permission::CHARTS,
            'history' => Permission::HISTORY, 'export' => Permission::EXPORT, 'review' => Permission::REVIEW,
            'assignOperator' => Permission::ASSIGN_OPERATOR,
        ];
        $permissions = array_map(fn (string $ability): bool => $user->can($ability), $abilities);
        $data = [
            'equipment' => $this->qa->equipment(), 'configurations' => $this->qa->configurations(),
            'filters' => $filters, 'qa' => $this->qa, 'permissions' => $permissions,
            'currentUser' => $user, 'currentOperator' => $user->name,
            'currentMeasuredAt' => now('America/El_Salvador')->format('Y-m-d\TH:i:s'),
            'operatorOptions' => $permissions['assignOperator'] ? User::query()->orderBy('name')->get()
                ->filter(fn (User $candidate): bool => $candidate->can('access_admin_panel') && $candidate->can(Permission::VIEW) && $candidate->can(Permission::REGISTER))
                ->map(fn (User $candidate): array => ['id' => $candidate->id, 'name' => $candidate->name])->values()->all() : [],
        ];
        foreach ($permissions as $name => $allowed) {
            $data['can'.ucfirst($name)] = $allowed;
        }
        $data['canViewRecord'] = $permissions['history'] || $permissions['edit'] || $permissions['review'];
        return $data;
    }

    private function selectedConfiguration(Request $request): ?array
    {
        $configurationId = $this->optionalId($request, 'configuration_id');
        if ($configurationId !== null) {
            return $this->qa->configuration($configurationId);
        }
        $list = $this->qa->configurations($this->optionalId($request, 'equipment_id'));

        return $list !== [] ? $this->qa->configuration((int) $list[0]['id']) : null;
    }

    private function optionalId(Request $request, string $key): ?int
    {
        $value = $request->query($key);
        if ($value === null || $value === '') {
            return null;
        }
        abort_unless(is_scalar($value) && ctype_digit((string) $value) && (int) $value > 0 && (int) $value <= 2147483647, 422, 'El identificador seleccionado no es válido.');

        return (int) $value;
    }

    private function filters(Request $request): array
    {
        $validator = Validator::make($request->query(), [
            'equipment_id' => ['nullable', 'integer', 'min:1'],
            'configuration_id' => ['nullable', 'integer', 'min:1'],
            'catalog_id' => ['nullable', 'integer', 'min:1'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => array_filter(['nullable', 'date_format:Y-m-d', $request->filled('from') ? 'after_or_equal:from' : null]),
            'comparison' => ['nullable', 'in:within,outside,incomplete'],
            'review_status' => ['nullable', 'in:pending,followup,reviewed'],
            'include_deleted' => ['nullable', 'boolean'],
        ]);
        if ($validator->fails()) {
            abort(422, $validator->errors()->first());
        }

        $filters = array_filter($validator->validated(), fn ($value): bool => $value !== null && $value !== '');
        if (! $request->routeIs('qa-demo.runs.index', 'qa-demo.export.*')) {
            unset($filters['include_deleted']);
        } elseif (! empty($filters['include_deleted'])) {
            abort_unless($request->user()->can(Permission::HISTORY), 403);
        }
        return $filters;
    }

    private function equipmentData(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'model' => ['required', 'string', 'max:160'],
            'code' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9_-]+$/D'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'area' => ['required', 'in:Química,Hematología'],
            'active' => ['sometimes', 'boolean'],
        ]);

        return $data + ['active' => true];
    }

    private function testData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'], 'exam_name' => ['nullable', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:40'], 'area' => ['required', 'in:Química,Hematología'],
            'active' => ['sometimes', 'boolean'],
        ]);
    }

    private function decodePayload(Request $request, string $field, array $keys): void
    {
        if (! $request->has($field)) {
            return;
        }
        $value = $request->input($field);
        if (! is_string($value) || strlen($value) > 500000) {
            throw ValidationException::withMessages([$field => 'El formulario supera el tamaño permitido.']);
        }
        try {
            $decoded = json_decode($value, true, 32, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw ValidationException::withMessages([$field => 'El formulario está incompleto. Recarga la página e inténtalo de nuevo.']);
        }
        if (! is_array($decoded) || array_diff(array_keys($decoded), $keys) !== []) {
            throw ValidationException::withMessages([$field => 'La estructura del formulario no es válida.']);
        }
        foreach ($keys as $key) {
            if (! array_key_exists($key, $decoded) || ! is_array($decoded[$key])) {
                throw ValidationException::withMessages([$field => 'El formulario está incompleto.']);
            }
        }
        $request->merge($decoded);
    }

    private function runData(Request $request, ?array $existing = null): array
    {
        if (! $request->has('run_payload') && $request->has('readings_payload')) {
            $value = $request->input('readings_payload');
            if (! is_string($value) || strlen($value) > 500000) {
                throw ValidationException::withMessages(['readings_payload' => 'El formulario supera el tamaño permitido.']);
            }
            try {
                $decoded = json_decode($value, true, 32, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                throw ValidationException::withMessages(['readings_payload' => 'Las lecturas están incompletas. Recarga la página e inténtalo de nuevo.']);
            }
            if (! is_array($decoded)) {
                throw ValidationException::withMessages(['readings_payload' => 'La estructura de las lecturas no es válida.']);
            }
            $request->merge(['run_payload' => json_encode(array_key_exists('values', $decoded) ? $decoded : ['values' => $decoded], JSON_THROW_ON_ERROR)]);
        }
        $this->decodePayload($request, 'run_payload', ['values']);
        $data = $request->validate([
            'configuration_id' => [$existing === null ? 'required' : 'sometimes', 'integer', 'min:1'],
            'kind' => [$existing === null ? 'required' : 'sometimes', 'in:routine,additional,repeat,correction,before_event,after_event'],
            'parent_run_id' => ['nullable', 'integer', 'min:1'], 'event_id' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:4000'], 'missing_reason' => ['nullable', 'string', 'max:2000'],
            'values' => ['required', 'array', 'min:1', 'max:50'],
            'values.*' => ['required', 'array', 'max:50'], 'values.*.*' => ['nullable', 'string', 'max:80'],
            'action' => ['sometimes', 'in:draft,submit'], 'expected_revision' => [$existing === null ? 'sometimes' : 'required', 'integer', 'min:1'],
            'edit_reason' => ['nullable', 'string', 'max:2000'],
            'operator_user_id' => ['nullable', 'integer', 'min:1'],
        ]);
        $selectedOperator = $request->filled('operator_user_id') ? (int) $request->input('operator_user_id') : null;
        if ($existing !== null && $selectedOperator !== null && $selectedOperator === (int) ($existing['operator_user_id'] ?? 0)) {
            // Conservar el autor histórico no es una nueva asignación, aunque
            // sus permisos o su cuenta hayan cambiado después de registrar.
            $data['operator'] = $existing['operator'];
            $data['operator_user_id'] = $existing['operator_user_id'];
        } elseif ($selectedOperator !== null) {
            abort_unless($request->user()->can(Permission::ASSIGN_OPERATOR) || $selectedOperator === (int) $request->user()->id, 403);
            // Cambiar el autor de un registro existente también requiere permiso de asignación.
            if ($existing !== null && $selectedOperator !== (int) ($existing['operator_user_id'] ?? 0)) {
                abort_unless($request->user()->can(Permission::ASSIGN_OPERATOR), 403);
            }
            $operator = User::query()->find($selectedOperator);
            if ($operator === null || ! $operator->can('access_admin_panel') || ! $operator->can(Permission::VIEW) || ! $operator->can(Permission::REGISTER)) {
                throw ValidationException::withMessages(['operator_user_id' => 'Selecciona un usuario autorizado para registrar controles.']);
            }
            $data['operator'] = $operator->name;
            $data['operator_user_id'] = $operator->id;
        } elseif ($existing !== null) {
            $data['operator'] = $existing['operator'];
            $data['operator_user_id'] = $existing['operator_user_id'];
        } else {
            $data['operator'] = $request->user()->name;
            $data['operator_user_id'] = $request->user()->id;
        }
        $data['measured_at'] = $existing['measured_at'] ?? now('America/El_Salvador')->format('Y-m-d\TH:i:s');
        if ($existing === null && ($data['kind'] ?? '') === 'correction' && ! empty($data['parent_run_id'])) {
            $data['measured_at'] = $this->qa->run((int) $data['parent_run_id'])['measured_at'];
        }
        return $data + $this->actor($request);
    }

    private function actor(Request $request): array
    {
        return ['actor_id' => $request->user()->id, 'actor_name' => $request->user()->name];
    }

    private function afterRun(Request $request, int $id, bool $draft): RedirectResponse
    {
        if ($draft && $request->user()->can(Permission::EDIT)) {
            return redirect()->route('qa-demo.runs.edit', ['run' => $id]);
        }
        if ($request->user()->canAny([Permission::HISTORY, Permission::EDIT, Permission::REVIEW])) {
            return redirect()->route('qa-demo.runs.show', ['run' => $id]);
        }
        return redirect()->route('qa-demo.runs.create');
    }

    private function scalarNumberRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if ((! is_string($value) && ! is_int($value) && ! is_float($value)) || strlen((string) $value) > 80) {
                $fail('Escriba un número con coma o punto decimal.');
            }
        };
    }

    private function exportRows(array $filters): array
    {
        $kindLabels = ['routine' => 'Control habitual', 'additional' => 'Control adicional', 'repeat' => 'Repetición', 'correction' => 'Corrección', 'before_event' => 'Antes de un evento', 'after_event' => 'Después de un evento'];
        $comparisonLabels = ['within' => 'Dentro de límites', 'outside' => 'Fuera de límites', 'missing' => 'No realizada', 'incomplete' => 'Incompleto'];
        $reviewLabels = ['pending' => 'Pendiente de revisión', 'followup' => 'En seguimiento', 'reviewed' => 'Revisado'];
        $rows = [[
            'Registro', 'Equipo', 'Configuración', 'Medido el', 'Guardado el', 'Registrado por', 'Tipo',
            'Prueba', 'Nivel', 'Lote', 'Unidad', 'Lectura original', 'Valor numérico', 'Valor esperado',
            'Mínimo', 'Máximo', 'Comparación', 'Revisión', 'Motivo de faltantes', 'Notas',
            'Último revisor', 'Fecha de revisión', 'Comentario de revisión', 'Estado del registro', 'Eliminado el', 'Motivo de eliminación',
        ]];
        foreach ($this->qa->runs($filters) as $summary) {
            $run = $this->qa->run((int) $summary['id']);
            $reviews = $run['reviews'] ?? [];
            $latestReview = $reviews !== [] ? $reviews[array_key_last($reviews)] : [];
            foreach ($run['measurements'] as $measurement) {
                if (isset($filters['catalog_id']) && (int) $measurement['catalog_id'] !== (int) $filters['catalog_id']) {
                    continue;
                }
                $level = $run['configuration_snapshot']['levels'][$measurement['level']] ?? [];
                $rows[] = [
                    $run['number'], $run['equipment']['name'], $run['configuration_id'], $run['measured_at'],
                    $run['captured_at'], $run['operator'], $kindLabels[$run['kind']] ?? $run['kind'], $measurement['analyte_name'],
                    $level['label'] ?? $measurement['level'], $level['lot'] ?? '', $measurement['unit'],
                    $measurement['raw_value'] ?? '', $this->spanishNumber($measurement['value'] ?? null), $this->spanishNumber($measurement['target'] ?? null),
                    $this->spanishNumber($measurement['min'] ?? null), $this->spanishNumber($measurement['max'] ?? null), $comparisonLabels[$measurement['status']] ?? $measurement['status'],
                    $reviewLabels[$run['review_status']] ?? $run['review_status'], $run['missing_reason'] ?? '', $run['notes'] ?? '',
                    $latestReview['reviewer'] ?? '', $latestReview['reviewed_at'] ?? '', $latestReview['notes'] ?? '',
                    ! empty($run['deleted_at']) ? 'Eliminado' : 'Vigente', $run['deleted_at'] ?? '', $run['deleted_reason'] ?? '',
                ];
                if (count($rows) > 10001) {
                    abort(422, 'El archivo supera 10.000 lecturas. Seleccione un periodo, equipo o prueba más específico.');
                }
            }
        }

        return $rows;
    }

    private function spanishNumber(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        $number = rtrim(rtrim(number_format((float) $value, 6, ',', ''), '0'), ',');

        return $number === '-0' ? '0' : $number;
    }

    private function safeCsvCell(mixed $value): string
    {
        $text = QaDemoPresentation::text((string) ($value ?? ''));
        if (preg_match('/^[+-]?\d+(?:[.,]\d+)?$/D', $text)) {
            return $text;
        }
        if (preg_match('/^[\s\x{FEFF}]*[=+\-@]/u', $text)) {
            return "'".$text;
        }

        return $text;
    }
}
