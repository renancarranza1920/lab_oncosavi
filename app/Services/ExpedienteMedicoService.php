<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Medico;
use App\Models\Orden;
use Illuminate\Database\Eloquent\Builder;

class ExpedienteMedicoService
{
    public function limitarOrdenes(Builder $query, Medico $medico): Builder
    {
        return $query->when(! $medico->portal_todos_pacientes,
            fn (Builder $q) => $q->where('medico_id', $medico->id));
    }

    public function pacientes(Medico $medico): Builder
    {
        return Cliente::query()->when(! $medico->portal_todos_pacientes,
            fn (Builder $q) => $q->whereHas('ordenes', fn (Builder $orders) => $this->limitarOrdenes($orders, $medico)));
    }

    public function buscar(Medico $medico, array $filtros): Builder
    {
        $query = $this->pacientes($medico)->with('telefonos');
        $terminos = preg_split('/\s+/u', trim($filtros['q'] ?? ''), -1, PREG_SPLIT_NO_EMPTY);
        foreach ($terminos as $termino) {
            $query->where(function (Builder $q) use ($termino, $medico): void {
                foreach (['NumeroExp', 'nombre', 'apellido', 'dui', 'telefono', 'correo', 'direccion', 'genero', 'estado'] as $campo) {
                    $q->orWhere($campo, 'like', '%'.$termino.'%');
                }
                $digitos = preg_replace('/\D/', '', $termino);
                if ($digitos !== '' && preg_match('/^[\d()+\-]+$/', $termino)) {
                    $q->orWhereHas('telefonos', fn (Builder $telefonos) => $telefonos->where('numero', 'like', '%'.$digitos.'%'));
                    $q->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(telefono, '+', ''), '-', ''), ' ', ''), '(', ''), ')', '') LIKE ?", ['%'.$digitos.'%'])
                        ->orWhereRaw("REPLACE(dui, '-', '') LIKE ?", ['%'.$digitos.'%']);
                }
                if (preg_match('/^#?([1-9][0-9]*)$/', $termino, $id)) {
                    $q->orWhereHas('ordenes', fn (Builder $orders) => $this->limitarOrdenes($orders, $medico)->whereKey($id[1]));
                }
            });
        }
        foreach (['genero', 'estado', 'fecha_nacimiento'] as $campo) {
            if (! empty($filtros[$campo])) {
                $query->where($campo, $filtros[$campo]);
            }
        }
        if (collect($filtros)->only(['desde', 'hasta', 'hora_desde', 'hora_hasta'])->filter()->isNotEmpty()) {
            $query->whereHas('ordenes', function (Builder $orders) use ($medico, $filtros): void {
                $this->limitarOrdenes($orders, $medico);
                if (! empty($filtros['desde'])) {
                    $orders->where('fecha', '>=', $filtros['desde']);
                }
                if (! empty($filtros['hasta'])) {
                    $orders->where('fecha', '<', \Carbon\CarbonImmutable::parse($filtros['hasta'])->addDay()->toDateString());
                }
                if (! empty($filtros['hora_desde'])) {
                    $orders->whereTime('created_at', '>=', $filtros['hora_desde'].':00');
                }
                if (! empty($filtros['hora_hasta'])) {
                    $orders->whereTime('created_at', '<=', $filtros['hora_hasta'].':59');
                }
            });
        }

        return $query
            ->withCount(['ordenes as ordenes_portal_count' => fn (Builder $orders) => $this->limitarOrdenes($orders, $medico)])
            ->withMax(['ordenes as ultima_orden' => fn (Builder $orders) => $this->limitarOrdenes($orders, $medico)], 'fecha')
            ->orderByDesc('ultima_orden')->orderByDesc('id');
    }

    public function ordenes(Medico $medico): Builder
    {
        return $this->limitarOrdenes(Orden::query(), $medico);
    }
}
