<?php

namespace App\Support;

class EstadoVisual
{
    public static function color(?string $estado): string
    {
        return match (mb_strtolower(trim(str_replace('_', ' ', $estado ?? '')))) {
            'finalizado', 'finalizada', 'completado', 'completada', 'activo', 'activa', 'aprobado', 'aprobada' => 'success',
            'pendiente', 'en espera', 'pausada', 'pausado' => 'warning',
            'cancelado', 'cancelada', 'rechazado', 'rechazada' => 'danger',
            'inactivo', 'inactiva' => 'gray',
            'en proceso', 'en progreso' => 'info',
            default => 'gray',
        };
    }

    public static function clase(?string $estado): string
    {
        return 'estado-' . self::color($estado);
    }

    public static function fondo(?string $estado): string
    {
        return config('estados.' . self::color($estado) . '.fondo');
    }
}
