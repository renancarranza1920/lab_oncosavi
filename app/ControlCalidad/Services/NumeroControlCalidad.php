<?php

namespace App\ControlCalidad\Services;

final class NumeroControlCalidad
{
    public static function normalizar(mixed $raw): ?string
    {
        if ((! is_string($raw) && ! is_int($raw) && ! is_float($raw))
            || strlen(trim((string) $raw)) > 40
            || ! preg_match('/^[+-]?\d+(?:[.,]\d{1,6})?$/D', trim((string) $raw))) {
            return null;
        }
        $value = str_replace(',', '.', trim((string) $raw));
        if (bccomp($value, '1000000000000', 6) > 0 || bccomp($value, '-1000000000000', 6) < 0) {
            return null;
        }
        $rounding = bccomp($value, '0', 6) < 0 ? '-0.005' : '0.005';
        $rounded = bcadd(bcadd($value, $rounding, 6), '0', 2);

        return bccomp($rounded, '0', 2) === 0 ? '0.00' : $rounded;
    }

    public static function mostrar(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }
        $number = (float) str_replace(',', '.', (string) $value);

        return number_format(round($number, 2) == 0 ? 0 : $number, 2, ',', '');
    }
}
