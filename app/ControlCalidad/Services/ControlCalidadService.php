<?php

namespace App\ControlCalidad\Services;

use App\ControlCalidad\Models\Area;
use App\ControlCalidad\Models\Lote;
use App\ControlCalidad\Models\Nivel;
use App\ControlCalidad\Models\Prueba;
use App\ControlCalidad\Models\Registro;
use App\ControlCalidad\Models\ValorControl;
use App\Models\User;
use App\Support\ControlCalidadPermissions as Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ControlCalidadService
{
    public function guardarPrueba(array $data, User $actor, ?Prueba $prueba = null): Prueba
    {
        $this->autorizar($actor, Permission::MAINTAIN);
        foreach (['nombre', 'unidad'] as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = trim($data[$field]);
            }
        }
        $valid = Validator::make($data, [
            'area_id' => ['required', 'integer', 'exists:cci_areas,id'],
            'nombre' => ['required', 'string', 'max:160', Rule::unique('cci_pruebas', 'nombre')->where('area_id', $data['area_id'] ?? null)->ignore($prueba?->id)],
            'unidad' => ['required', 'string', 'max:40'],
            'niveles' => ['required', 'array', 'min:1', 'max:1000'],
            'niveles.*' => ['required', 'array'],
            'niveles.*.id' => ['nullable', 'integer', 'min:1', 'distinct'],
            'niveles.*.nombre' => ['required', 'string', 'max:80'],
            'niveles.*.lote_id' => ['required', 'integer', 'exists:cci_lotes,id'],
            'niveles.*.esperado' => ['required'],
            'niveles.*.minimo' => ['required'],
            'niveles.*.maximo' => ['required'],
        ])->validate();

        $names = [];
        foreach ($valid['niveles'] as $index => &$level) {
            $level['nombre'] = trim($level['nombre']);
            $name = mb_strtolower($level['nombre']);
            if ($name === '' || isset($names[$name])) {
                $this->invalid("niveles.$index.nombre", 'Cada nivel debe tener un nombre diferente.');
            }
            $names[$name] = true;
            foreach (['esperado', 'minimo', 'maximo'] as $field) {
                $level[$field] = $this->numero($level[$field], "niveles.$index.$field");
            }
            if ((float) $level['minimo'] > (float) $level['maximo']
                || (float) $level['esperado'] < (float) $level['minimo']
                || (float) $level['esperado'] > (float) $level['maximo']) {
                $this->invalid("niveles.$index.esperado", 'El valor esperado debe estar entre el mínimo y el máximo.');
            }
        }
        unset($level);

        return DB::transaction(function () use ($valid, $prueba): Prueba {
            $current = $prueba !== null ? Prueba::query()->lockForUpdate()->findOrFail($prueba->id) : new Prueba;
            if ($current->exists && $current->area_id !== (int) $valid['area_id'] && $current->resultados()->exists()) {
                $this->invalid('area_id', 'Esta prueba ya tiene registros. Conserva su área y crea otra prueba para un equipo diferente.');
            }
            $known = $current->exists ? $current->niveles()->get()->keyBy('id') : collect();
            $keep = [];
            foreach ($valid['niveles'] as $index => $level) {
                if (! empty($level['id'])) {
                    if (! $known->has((int) $level['id'])) {
                        $this->invalid("niveles.$index.id", 'El nivel no pertenece a esta prueba.');
                    }
                    $keep[] = (int) $level['id'];
                }
            }
            $removed = $known->except($keep);
            foreach ($removed as $level) {
                if ($level->resultados()->exists()) {
                    $this->invalid('niveles', 'No se puede quitar un nivel que ya tiene registros. Sus datos se conservan en el historial.');
                }
            }

            $current->fill(['area_id' => (int) $valid['area_id'], 'nombre' => trim($valid['nombre']), 'unidad' => trim($valid['unidad'])])->save();
            foreach ($removed as $level) {
                $level->valorControlActual()->delete();
                $level->delete();
            }
            foreach (array_values($valid['niveles']) as $index => $level) {
                $model = ! empty($level['id']) ? $known->get((int) $level['id']) : new Nivel(['prueba_id' => $current->id]);
                $model->fill(['nombre' => $level['nombre'], 'orden' => $index + 1])->save();
                ValorControl::query()->updateOrCreate(['nivel_id' => $model->id], [
                    'lote_id' => (int) $level['lote_id'], 'esperado' => $level['esperado'],
                    'minimo' => $level['minimo'], 'maximo' => $level['maximo'],
                ]);
            }

            return $current->fresh()->load('area', 'niveles.valorControlActual.lote.producto');
        });
    }

    public function guardar(array $data, User $actor, ?Registro $registro = null): Registro
    {
        $this->autorizar($actor, $registro === null ? Permission::REGISTER : Permission::EDIT);
        $valid = Validator::make($data, [
            'area_id' => ['required', 'integer', 'exists:cci_areas,id'],
            'observaciones' => ['nullable', 'string', 'max:4000'],
            'resultados' => ['required', 'array', 'min:1', 'max:10000'],
            'resultados.*' => ['required', 'array'],
            'resultados.*.nivel_id' => ['required', 'integer', 'min:1', 'distinct'],
            'resultados.*.valor' => ['required'],
        ])->validate();
        $inputs = [];
        foreach ($valid['resultados'] as $index => $row) {
            $inputs[(int) $row['nivel_id']] = [
                'valor' => $this->numero($row['valor'], "resultados.$index.valor"),
                'valor_original' => trim((string) $row['valor']),
            ];
        }

        return DB::transaction(function () use ($valid, $inputs, $actor, $registro): Registro {
            $current = $registro !== null ? Registro::query()->lockForUpdate()->findOrFail($registro->id) : new Registro;
            if ($current->exists && $current->area_id !== (int) $valid['area_id']) {
                $this->invalid('area_id', 'La edición conserva el área de la medición original.');
            }
            $operatorId = $current->exists ? $current->laboratorista_id : $actor->id;
            if ($current->exists) {
                $results = $current->resultados()->get()->keyBy('nivel_id');
                $this->validarNiveles(array_keys($inputs), $results->keys()->all());
                $current->fill(['laboratorista_id' => $operatorId, 'actualizado_por_id' => $actor->id, 'observaciones' => $valid['observaciones'] ?? null])->save();
                foreach ($inputs as $levelId => $value) {
                    $results->get($levelId)->fill($value)->save();
                }
            } else {
                $levels = Nivel::query()->whereHas('prueba', fn ($query) => $query->where('area_id', $valid['area_id']))
                    ->with(['prueba', 'valorControlActual.lote'])->get()->keyBy('id');
                if ($levels->isEmpty()) {
                    $this->invalid('area_id', 'Configura las pruebas y sus niveles antes de registrar las lecturas.');
                }
                $this->validarNiveles(array_keys($inputs), $levels->keys()->all());
                foreach ($levels as $level) {
                    if ($level->valorControlActual === null || $level->valorControlActual->lote === null) {
                        $this->invalid('resultados', 'Todos los niveles deben tener un lote y sus valores de control configurados.');
                    }
                    if ($level->valorControlActual->lote->caduca_el->lt(now()->startOfDay())) {
                        $this->invalid('resultados', 'El lote '.$level->valorControlActual->lote->numero.' está vencido. Actualízalo en Mantenimiento.');
                    }
                }
                $current->fill([
                    'area_id' => (int) $valid['area_id'], 'laboratorista_id' => $operatorId,
                    'registrado_por_id' => $actor->id, 'fecha_hora' => now(),
                    'observaciones' => $valid['observaciones'] ?? null,
                ])->save();
                foreach ($inputs as $levelId => $value) {
                    $level = $levels->get($levelId);
                    $control = $level->valorControlActual;
                    $current->resultados()->create($value + [
                        'prueba_id' => $level->prueba_id, 'nivel_id' => $levelId, 'valor_control_id' => $control->id,
                        'prueba_nombre' => $level->prueba->nombre, 'nivel_nombre' => $level->nombre,
                        'unidad' => $level->prueba->unidad, 'lote_nombre' => $control->lote->nombre, 'lote_numero' => $control->lote->numero,
                        'esperado' => $control->esperado, 'minimo' => $control->minimo, 'maximo' => $control->maximo,
                    ]);
                }
            }

            return $current->fresh()->load(['area', 'laboratorista', 'registradoPor', 'resultados']);
        });
    }

    public function eliminar(Registro $registro, User $actor): void
    {
        $this->autorizar($actor, Permission::DELETE);
        DB::transaction(function () use ($registro, $actor): void {
            $current = Registro::query()->lockForUpdate()->findOrFail($registro->id);
            $current->forceFill(['actualizado_por_id' => $actor->id])->save();
            $current->delete();
        });
    }

    private function autorizar(User $actor, string $permission): void
    {
        abort_unless($actor->can('access_admin_panel') && $actor->can(Permission::VIEW) && $actor->can($permission), 403);
    }

    private function validarNiveles(array $received, array $expected): void
    {
        sort($received);
        sort($expected);
        if ($received !== $expected) {
            $this->invalid('resultados', 'Registra una lectura para cada nivel de las pruebas del área seleccionada.');
        }
    }

    private function numero(mixed $raw, string $field): string
    {
        if ((! is_string($raw) && ! is_int($raw) && ! is_float($raw))
            || strlen(trim((string) $raw)) > 40
            || ! preg_match('/^[+-]?\d+(?:[.,]\d{1,6})?$/D', trim((string) $raw))) {
            $this->invalid($field, 'Escribe un número con coma o punto decimal.');
        }
        $value = str_replace(',', '.', trim((string) $raw));
        if (! is_finite((float) $value) || abs((float) $value) > 1000000000000) {
            $this->invalid($field, 'El número supera el valor permitido.');
        }
        return $value;
    }

    private function invalid(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
