<?php

namespace App\Support;

use Illuminate\Support\Collection;

class ReferenciaLaboratorio
{
    public static function sinDuplicados(iterable $referencias): Collection
    {
        // Ignorar IDs de filas y diferencias de presentación, conservando toda
        // diferencia clínica y la primera descripción registrada.
        return collect($referencias)->uniqueStrict(function ($referencia): string {
            $descripcion = trim(preg_replace('/\s+/u', ' ', (string) data_get($referencia, 'descriptivo')));

            return serialize([
                (string) data_get($referencia, 'prueba_id'),
                (string) data_get($referencia, 'grupo_etario_id'),
                data_get($referencia, 'genero'),
                data_get($referencia, 'operador'),
                self::numero(data_get($referencia, 'valor_min')),
                self::numero(data_get($referencia, 'valor_max')),
                data_get($referencia, 'unidades'),
                data_get($referencia, 'nota'),
                mb_strtolower($descripcion, 'UTF-8'),
            ]);
        })->values();
    }

    private static function numero($valor): ?string
    {
        return $valor === null ? null : number_format((float) $valor, 2, '.', '');
    }
}
