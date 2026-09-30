<?php

namespace Tests\Unit;

use App\Support\NumeroLaboratorio;
use App\Models\Resultado;
use PHPUnit\Framework\TestCase;

class NumeroLaboratorioTest extends TestCase
{
    public function test_formats_input_and_print_without_changing_quantity(): void
    {
        foreach ([
            ['8500', '8,500', '8,500'],
            ['999999', '999,999', '999,999'],
            ['1000000', '1,000,000', '1.000.000'],
            ['3800000.00', '3,800,000.00', '3.800.000.00'],
            ['40000000', '40,000,000', '40.000.000'],
            ['12.5', '12.5', '12.5'],
            ['0', '0', '0'],
            ['-8500.25', '-8,500.25', '-8,500.25'],
            ['NEGATIVO', 'NEGATIVO', 'NEGATIVO'],
            ['5 MIN 30 SEG', '5 MIN 30 SEG', '5 MIN 30 SEG'],
        ] as [$raw, $input, $print]) {
            self::assertSame($input, NumeroLaboratorio::formatear($raw));
            self::assertSame($print, NumeroLaboratorio::formatear($input, true));
            $resultado = new Resultado();
            $resultado->resultado = $input;
            self::assertSame($raw, $resultado->resultado);
        }
    }

    public function test_prints_ranges_and_previously_grouped_values(): void
    {
        self::assertSame('3.800.000 - 5.800.000', NumeroLaboratorio::imprimirTexto('3,800,000 - 5800000'));
        self::assertSame('3.800.000 - 5.800.000', NumeroLaboratorio::imprimirTexto('3.800.000 - 5.800.000'));
        self::assertSame('5,000 - 10,000', NumeroLaboratorio::imprimirTexto('5000 - 10000'));
        self::assertSame('12.5 - 17.5', NumeroLaboratorio::imprimirTexto('12.5 - 17.5'));
        self::assertNull(NumeroLaboratorio::normalizar(null));
    }
}
