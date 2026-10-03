<?php

namespace App\Support;

class Bitacora
{
    public static function datosVisibles(array $datos): array
    {
        foreach ($datos as $campo => $valor) {
            if (preg_match('/password|token|secret|private.?key/i', (string) $campo)) {
                unset($datos[$campo]);
            } elseif (is_array($valor)) {
                $datos[$campo] = self::datosVisibles($valor);
            }
        }

        return $datos;
    }
}
