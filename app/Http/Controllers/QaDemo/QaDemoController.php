<?php

namespace App\Http\Controllers\QaDemo;

use App\Http\Controllers\Controller;
use App\QaDemo\QaDemoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QaDemoController extends Controller
{
    public function __construct(private readonly QaDemoService $qa) {}

    public function index(): View
    {
        return view('qa-demo.index', $this->common() + ['overview' => $this->qa->overview()]);
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

        return view('qa-demo.configuration', $this->common() + [
            'catalog' => $this->qa->catalog(),
            'selectedConfiguration' => $selected,
            'selectedEquipmentId' => $equipmentId ?? ($selected['equipment_id'] ?? null),
        ]);
    }

    public function storeEquipment(Request $request): RedirectResponse
    {
        $data = $this->equipmentData($request);
        $id = $this->qa->createEquipment($data);

        return redirect()->route('qa-demo.configuration.index', ['equipment_id' => $id])
            ->with('success', 'Equipo de prueba registrado. Ahora puede configurar sus controles.');
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

    public function storeConfiguration(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'equipment_id' => ['required', 'integer', 'min:1'],
            'configured_by' => ['required', 'string', 'min:2', 'max:120'],
            'label' => ['required', 'string', 'max:150'],
            'levels' => ['required', 'array:low,medium,high'],
            'levels.*' => ['required', 'array:lot,product,expires_on,opened_on,stability_days'],
            'levels.*.lot' => ['required', 'string', 'max:100'],
            'levels.*.product' => ['required', 'string', 'max:160'],
            'levels.*.expires_on' => ['required', 'date_format:Y-m-d'],
            'levels.*.opened_on' => ['required', 'date_format:Y-m-d'],
            'levels.*.stability_days' => ['required', 'integer', 'min:1', 'max:730'],
            'analytes' => ['required', 'array', 'min:1', 'max:50'],
            'analytes.*' => ['required', 'array:catalog_id,method,unit,targets'],
            'analytes.*.catalog_id' => ['required', 'integer', 'min:1', 'distinct'],
            'analytes.*.method' => ['required', 'string', 'max:120'],
            'analytes.*.unit' => ['required', 'string', 'max:40'],
            'analytes.*.targets' => ['required', 'array:low,medium,high'],
            'analytes.*.targets.*' => ['required', 'array:target,min,max'],
            'analytes.*.targets.*.target' => ['required', $this->scalarNumberRule()],
            'analytes.*.targets.*.min' => ['required', $this->scalarNumberRule()],
            'analytes.*.targets.*.max' => ['required', $this->scalarNumberRule()],
        ]);
        $id = $this->qa->saveConfiguration($data);

        return redirect()->route('qa-demo.configuration.index', ['configuration_id' => $id])
            ->with('success', 'Configuración guardada en una nueva versión. Las mediciones anteriores conservan sus valores y límites.');
    }

    public function runs(Request $request): View
    {
        $filters = $this->filters($request);

        return view('qa-demo.runs.index', $this->common($filters) + [
            'runs' => $this->qa->runs($filters),
            'runsTotal' => $this->qa->countRuns($filters),
        ]);
    }

    public function createRun(Request $request): View
    {
        $parentId = $this->optionalId($request, 'parent_run_id');
        $parent = $parentId !== null ? $this->qa->run($parentId) : null;
        $configuration = $parent !== null
            ? $this->qa->configuration((int) $parent['configuration_id'])
            : $this->selectedConfiguration($request);
        $kind = (string) $request->query('kind', $parent !== null ? 'repeat' : 'routine');
        abort_unless(in_array($kind, ['routine', 'additional', 'repeat', 'correction', 'before_event', 'after_event'], true), 422, 'Tipo de registro desconocido.');
        if (in_array($kind, ['repeat', 'correction'], true)) {
            abort_unless($parent !== null, 422, 'Seleccione el registro original para repetirlo o corregirlo.');
        }

        return view('qa-demo.runs.form', $this->common() + [
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
        $id = $this->qa->recordRun($data, $draft);

        return redirect()->route($draft ? 'qa-demo.runs.edit' : 'qa-demo.runs.show', ['run' => $id])
            ->with('success', $draft ? 'Borrador guardado. Puede completar las lecturas antes de presentarlo.' : 'Mediciones guardadas. El responsable puede revisar el registro.');
    }

    public function showRun(int $run): View
    {
        $record = $this->qa->run($run);
        $related = $record['related_runs'] ?? $this->qa->runs(['parent_run_id' => $run]);

        return view('qa-demo.runs.show', $this->common() + [
            'run' => $record,
            'relatedRuns' => $related,
            'events' => $this->qa->events(['equipment_id' => $record['equipment']['id']]),
        ]);
    }

    public function editRun(int $run): View
    {
        $record = $this->qa->run($run);
        abort_unless($record['status'] === 'draft', 409, 'Un registro presentado conserva sus lecturas. Use una corrección o repetición vinculada.');

        return view('qa-demo.runs.form', $this->common() + [
            'configuration' => $this->qa->configuration((int) $record['configuration_id']),
            'run' => $record,
            'parentRun' => ! empty($record['parent_run_id']) ? $this->qa->run((int) $record['parent_run_id']) : null,
            'kind' => $record['kind'],
            'events' => $this->qa->events(['equipment_id' => $record['equipment']['id']]),
        ]);
    }

    public function updateRun(Request $request, int $run): RedirectResponse
    {
        $draft = $request->input('action', 'submit') === 'draft';
        $this->qa->recordRun($this->runData($request), $draft, $run);

        return redirect()->route($draft ? 'qa-demo.runs.edit' : 'qa-demo.runs.show', ['run' => $run])
            ->with('success', $draft ? 'Borrador actualizado.' : 'Mediciones presentadas para revisión.');
    }

    public function reviewRun(Request $request, int $run): RedirectResponse
    {
        $data = $request->validate([
            'reviewer' => ['required', 'string', 'min:2', 'max:120'],
            'decision' => ['required', 'in:reviewed,followup'],
            'notes' => ['required', 'string', 'max:4000'],
        ]);
        $this->qa->reviewRun($run, $data);

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

        return view('qa-demo.charts', $this->common($filters) + [
            'chart' => $chart,
            'configuration' => $configuration,
        ]);
    }

    public function events(Request $request): View
    {
        $filters = $this->filters($request);
        $selectedId = $this->optionalId($request, 'event_id');

        return view('qa-demo.events', $this->common($filters) + [
            'events' => $this->qa->events($filters),
            'selectedEvent' => $selectedId !== null ? $this->qa->event($selectedId) : null,
        ]);
    }

    public function storeEvent(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'equipment_id' => ['required', 'integer', 'min:1'],
            'type' => ['required', 'in:calibration,maintenance'],
            'occurred_at' => ['required', 'date'],
            'operator' => ['required', 'string', 'min:2', 'max:120'],
            'notes' => ['required', 'string', 'max:4000'],
        ]);
        $id = $this->qa->recordEvent($data);

        return redirect()->route('qa-demo.events.index', ['equipment_id' => $data['equipment_id'], 'event_id' => $id])
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
        ])->loadView('qa-demo.pdf', [
            'runs' => $fullRuns,
            'filters' => $filters,
            'chart' => $chart,
            'generatedAt' => now('America/El_Salvador'),
            'logoPath' => public_path('images/oncosavi.png'),
        ])->setPaper('a4', 'landscape')->download('oncosavi-qa-controles.pdf');
    }

    private function common(array $filters = []): array
    {
        return [
            'equipment' => $this->qa->equipment(),
            'configurations' => $this->qa->configurations(),
            'filters' => $filters,
            'qa' => $this->qa,
        ];
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
        ]);
        if ($validator->fails()) {
            abort(422, $validator->errors()->first());
        }

        return array_filter($validator->validated(), fn ($value): bool => $value !== null && $value !== '');
    }

    private function equipmentData(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'model' => ['required', 'string', 'max:160'],
            'code' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9_-]+$/D'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'active' => ['sometimes', 'boolean'],
        ]);

        return $data + ['active' => true];
    }

    private function runData(Request $request): array
    {
        return $request->validate([
            'configuration_id' => ['required', 'integer', 'min:1'],
            'measured_at' => ['required', 'date'],
            'operator' => ['required', 'string', 'min:2', 'max:120'],
            'kind' => ['required', 'in:routine,additional,repeat,correction,before_event,after_event'],
            'parent_run_id' => ['nullable', 'integer', 'min:1'],
            'event_id' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:4000'],
            'missing_reason' => ['nullable', 'string', 'max:2000'],
            'values' => ['required', 'array', 'max:50'],
            'values.*' => ['required', 'array:low,medium,high'],
            'values.*.*' => ['nullable', 'string', 'max:80'],
            'action' => ['sometimes', 'in:draft,submit'],
        ]);
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
            'Último revisor declarado', 'Fecha de revisión', 'Comentario de revisión',
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
        $text = (string) ($value ?? '');
        if (preg_match('/^[+-]?\d+(?:[.,]\d+)?$/D', $text)) {
            return $text;
        }
        if (preg_match('/^[\s\x{FEFF}]*[=+\-@]/u', $text)) {
            return "'".$text;
        }

        return $text;
    }
}
