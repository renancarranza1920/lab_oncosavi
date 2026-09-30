<?php

namespace App\Support;

class NumeroLaboratorio
{
    public static function normalizar(?string $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $numero = trim($valor);
        if (preg_match('/^[+-]?\d{1,3}(?:,\d{3})+(?:\.\d+)?$/D', $numero)) {
            return str_replace(',', '', $numero);
        }
        // Dos o más puntos identifican inequívocamente millones ya impresos.
        if (preg_match('/^[+-]?\d{1,3}(?:\.\d{3}){2,}$/D', $numero)) {
            return str_replace('.', '', $numero);
        }

        return $valor;
    }

    public static function formatear(?string $valor, bool $impresion = false): ?string
    {
        $numero = self::normalizar($valor);
        if ($numero === null || !preg_match('/^([+-]?)(\d+)(\.\d+)?$/D', $numero, $partes)) {
            return $valor;
        }

        $entero = ltrim($partes[2], '0') ?: '0';
        $separador = $impresion && strlen($entero) > 6 ? '.' : ',';
        return $partes[1] . preg_replace('/\B(?=(\d{3})+(?!\d))/', $separador, $entero) . ($partes[3] ?? '');
    }

    public static function imprimirTexto(?string $texto): ?string
    {
        return self::formatearTexto($texto, true);
    }

    public static function formatearTexto(?string $texto, bool $impresion = false): ?string
    {
        return $texto === null ? null : preg_replace_callback(
            '/\d{1,3}(?:,\d{3})+(?:\.\d+)?|\d{1,3}(?:\.\d{3}){2,}|\d+(?:\.\d+)?/',
            fn (array $match) => self::formatear($match[0], $impresion),
            $texto,
        );
    }
}
