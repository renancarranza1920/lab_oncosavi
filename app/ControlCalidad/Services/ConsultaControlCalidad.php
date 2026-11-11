<?php

namespace App\ControlCalidad\Services;

use App\ControlCalidad\Models\Registro;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;

class ConsultaControlCalidad
{
    public function filtros(array $data): array
    {
        return Validator::make($data, [
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', ...(! empty($data['desde']) ? ['after_or_equal:desde'] : [])],
            'q' => ['nullable', 'string', 'max:180'],
            'area_id' => ['nullable', 'integer', 'exists:cci_areas,id'],
            'laboratorista_id' => ['nullable', 'integer', 'exists:users,id'],
        ])->validate();
    }

    public function registros(array $filtros, bool $supervision = false): Builder
    {
        $query = Registro::query()->with(['area', 'laboratorista', 'resultados'])
            ->when($filtros['desde'] ?? null, fn ($q, $date) => $q->where('fecha_hora', '>=', $date.' 00:00:00'))
            ->when($filtros['hasta'] ?? null, fn ($q, $date) => $q->where('fecha_hora', '<=', $date.' 23:59:59'))
            ->when($filtros['area_id'] ?? null, fn ($q, $id) => $q->where('area_id', $id))
            ->when($filtros['laboratorista_id'] ?? null, fn ($q, $id) => $q->where('laboratorista_id', $id));
        if (filled($filtros['q'] ?? null)) {
            $text = '%'.trim($filtros['q']).'%';
            $query->where(function (Builder $q) use ($text, $supervision): void {
                $q->whereHas('area', fn ($area) => $area->where('nombre', 'like', $text)->orWhere('equipo', 'like', $text));
                if ($supervision) {
                    $q->orWhereHas('resultados', fn ($r) => $r->where('prueba_nombre', 'like', $text)
                        ->orWhere('nivel_nombre', 'like', $text)->orWhere('lote_numero', 'like', $text));
                } else {
                    $q->orWhereHas('laboratorista', fn ($person) => $person->where('name', 'like', $text));
                }
            });
        }

        return $query->orderByDesc('fecha_hora')->orderByDesc('id');
    }

    public function tokenValido(string $token): bool
    {
        $hash = (string) config('control_calidad.supervision_token_hash');

        return strlen($token) === 43 && strlen($hash) === 64 && hash_equals($hash, hash('sha256', $token));
    }
}
