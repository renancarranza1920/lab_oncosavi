<?php

namespace App\Services;

use App\Models\Orden;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class CierreCajaService
{
    public const PERIODOS = ['diario', 'mensual', 'trimestral', 'anual'];

    public function generar(string $periodo, int $anio, int $mes = 1, int $trimestre = 1, int $dia = 1): array
    {
        [$desde, $hasta, $etiqueta] = $this->rango($periodo, $anio, $mes, $trimestre, $dia);

        $ordenes = Orden::query()
            ->with('cliente:id,nombre,apellido')
            ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->orderBy('fecha')
            ->orderBy('id')
            ->get();

        $vigentes = $ordenes->where('estado', '!=', 'cancelado')->values();
        $canceladas = $ordenes->where('estado', 'cancelado')->values();

        $resumen = [
            'ordenes' => $ordenes->count(),
            'ordenes_vigentes' => $vigentes->count(),
            'ordenes_canceladas' => $canceladas->count(),
            'ingreso_bruto' => $vigentes->sum(fn (Orden $orden) => (float) $orden->total + (float) $orden->descuento),
            'descuentos' => $vigentes->sum(fn (Orden $orden) => (float) $orden->descuento),
            'ingreso_neto' => $vigentes->sum(fn (Orden $orden) => (float) $orden->total),
            'valor_cancelado' => $canceladas->sum(fn (Orden $orden) => (float) $orden->total + (float) $orden->descuento),
        ];

        return [
            'periodo' => $periodo,
            'etiqueta' => $etiqueta,
            'desde' => $desde,
            'hasta' => $hasta,
            'resumen' => $resumen,
            'estados' => $this->resumenEstados($ordenes),
            'movimientos' => $this->resumenDiario($vigentes),
            'ordenes' => $ordenes->map(fn (Orden $orden) => [
                'id' => $orden->id,
                'fecha' => $orden->fecha?->format('d/m/Y'),
                'cliente' => trim(($orden->cliente?->nombre ?? '') . ' ' . ($orden->cliente?->apellido ?? '')) ?: 'Sin cliente',
                'estado' => $orden->estado,
                'bruto' => (float) $orden->total + (float) $orden->descuento,
                'descuento' => (float) $orden->descuento,
                'neto' => (float) $orden->total,
            ])->values(),
        ];
    }

    public function rango(string $periodo, int $anio, int $mes = 1, int $trimestre = 1, int $dia = 1): array
    {
        $periodo = in_array($periodo, self::PERIODOS, true) ? $periodo : 'mensual';
        $anio = max(2000, min(2100, $anio));

        if ($periodo === 'diario') {
            $mes = max(1, min(12, $mes));
            $dia = max(1, min(CarbonImmutable::create($anio, $mes, 1)->daysInMonth, $dia));
            $desde = CarbonImmutable::create($anio, $mes, $dia)->startOfDay();

            return [$desde, $desde->endOfDay(), ucfirst($desde->locale('es')->translatedFormat('d \d\e F \d\e Y'))];
        }

        if ($periodo === 'anual') {
            $desde = CarbonImmutable::create($anio, 1, 1)->startOfDay();
            $hasta = $desde->endOfYear();

            return [$desde, $hasta, "Año {$anio}"];
        }

        if ($periodo === 'trimestral') {
            $trimestre = max(1, min(4, $trimestre));
            $desde = CarbonImmutable::create($anio, (($trimestre - 1) * 3) + 1, 1)->startOfDay();
            $hasta = $desde->addMonths(2)->endOfMonth();

            return [$desde, $hasta, "Trimestre {$trimestre} de {$anio}"];
        }

        $mes = max(1, min(12, $mes));
        $desde = CarbonImmutable::create($anio, $mes, 1)->startOfDay();
        $hasta = $desde->endOfMonth();

        return [$desde, $hasta, ucfirst($desde->locale('es')->translatedFormat('F Y'))];
    }

    private function resumenEstados(Collection $ordenes): Collection
    {
        $etiquetas = [
            'pendiente' => 'Pendientes',
            'en proceso' => 'En proceso',
            'pausada' => 'Pausadas',
            'finalizado' => 'Finalizadas',
            'cancelado' => 'Canceladas',
        ];

        return collect($etiquetas)->map(fn (string $etiqueta, string $estado) => [
            'estado' => $estado,
            'etiqueta' => $etiqueta,
            'cantidad' => $ordenes->where('estado', $estado)->count(),
            'total' => $ordenes->where('estado', $estado)->sum(fn (Orden $orden) => (float) $orden->total),
        ])->values();
    }

    private function resumenDiario(Collection $ordenes): Collection
    {
        return $ordenes
            ->groupBy(fn (Orden $orden) => $orden->fecha?->format('Y-m-d'))
            ->map(fn (Collection $grupo, string $fecha) => [
                'fecha' => CarbonImmutable::parse($fecha)->format('d/m/Y'),
                'ordenes' => $grupo->count(),
                'bruto' => $grupo->sum(fn (Orden $orden) => (float) $orden->total + (float) $orden->descuento),
                'descuentos' => $grupo->sum(fn (Orden $orden) => (float) $orden->descuento),
                'neto' => $grupo->sum(fn (Orden $orden) => (float) $orden->total),
            ])
            ->values();
    }
}
