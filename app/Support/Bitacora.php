<?php

namespace App\Support;

class Bitacora
{
    public static function datosParaMostrar(array $datos): array
    {
        return array_map(static fn ($valor) => is_array($valor)
            ? json_encode($valor, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : (is_bool($valor) ? ($valor ? 'Sí' : 'No') : $valor), self::datosVisibles($datos));
    }

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
