<?php

namespace App\Services;

use App\Models\Orden;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecepcionMuestras
{
    public function registrar(Orden $orden, array $seleccionadas, int $usuarioId): array
    {
        $resumen = DB::transaction(function () use ($orden, $seleccionadas, $usuarioId) {
            $registro = Orden::query()->lockForUpdate()->findOrFail($orden->id);

            if (! in_array($registro->estado, ['pendiente', 'en proceso', 'pausada', 'finalizado'], true)) {
                throw ValidationException::withMessages([
                    'muestras_recibidas_list' => 'No se pueden modificar las muestras de una orden cancelada.',
                ]);
            }

            $detalles = $registro->detalleOrden()->with('examen.muestras')->lockForUpdate()->get();
            $permitidas = [];
            $recibidas = [];
            $faltantes = [];

            foreach ($detalles as $detalle) {
                $opciones = $detalle->examen?->muestras ?? collect();
                $recibidas[$detalle->id] = [];

                foreach ($opciones as $muestra) {
                    $clave = "d{$detalle->id}_m{$muestra->id}";
                    $permitidas[] = $clave;

                    if (in_array($clave, $seleccionadas, true)) {
                        $recibidas[$detalle->id][] = $muestra->id;
                    }
                }

                // Las muestras del catálogo son alternativas compatibles, no requisitos acumulativos.
                if ($opciones->isNotEmpty() && empty($recibidas[$detalle->id])) {
                    $faltantes[] = $detalle->nombre_examen ?? $detalle->examen->nombre;
                }
            }

            if (array_diff($seleccionadas, $permitidas)) {
                throw ValidationException::withMessages([
                    'muestras_recibidas_list' => 'Seleccione únicamente muestras compatibles con los exámenes de esta orden.',
                ]);
            }

            if ($registro->estado !== 'pendiente' && $faltantes) {
                throw ValidationException::withMessages([
                    'muestras_recibidas_list' => 'Registre al menos una muestra para: '.implode(', ', $faltantes).'.',
                ]);
            }

            $anteriores = [];
            $actuales = [];
            foreach ($detalles as $detalle) {
                if (($detalle->muestras_recibidas ?? []) !== $recibidas[$detalle->id]) {
                    $anteriores[$detalle->id] = $detalle->muestras_recibidas ?? [];
                    $actuales[$detalle->id] = $recibidas[$detalle->id];
                    $detalle->muestras_recibidas = $recibidas[$detalle->id];
                    $detalle->save();
                }
            }

            $cantidad = array_sum(array_map('count', $recibidas));
            if ($registro->estado === 'pendiente' && ! $faltantes) {
                $registro->estado = 'en proceso';
                if ($cantidad > 0) {
                    $registro->fecha_toma_muestra ??= now();
                    $registro->toma_muestra_user_id ??= $usuarioId;
                }
                $registro->save();
            }

            if ($actuales) {
                activity('Órdenes')->performedOn($registro)->causedBy($usuarioId)
                    ->withProperties(['anteriores' => $anteriores, 'recibidas' => $actuales])
                    ->log('Muestras recibidas actualizadas');
            }

            return ['faltantes' => $faltantes, 'cantidad' => $cantidad, 'modificadas' => (bool) $actuales];
        });

        $orden->refresh();

        return $resumen;
    }
}
