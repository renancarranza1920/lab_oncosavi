<?php

namespace Tests\Unit;

use App\Support\ReferenciaLaboratorio;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class ReferenciaLaboratorioTest extends TestCase
{
    public function test_deduplica_psa_conservando_las_primeras_filas_y_su_orden(): void
    {
        $primeras = [
            $this->referencia(['id' => 30, 'descriptivo' => 'NORMAL O BAJO RIESGO']),
            (object) $this->referencia(['id' => 31, 'descriptivo' => 'MEDIANO RIESGO', 'valor_min' => '4.00', 'valor_max' => '10.00']),
            $this->referencia(['id' => 32, 'descriptivo' => 'ALTO RIESGO', 'operador' => '>', 'valor_min' => '10.00', 'valor_max' => null]),
        ];
        $descripciones = ["\u{00A0}Normal   o Bajo Riesgo\u{00A0}", "mediano\tRiesgo", "\nAlto Riesgo\t"];
        $referencias = (function () use ($primeras, $descripciones) {
            foreach ($primeras as $indice => $referencia) {
                yield 30 + $indice => $referencia;
            }

            foreach ([246, 289, 298] as $primerId) {
                foreach ($primeras as $indice => $referencia) {
                    $duplicada = (array) $referencia;
                    $duplicada['id'] = $primerId + $indice;
                    $duplicada['descriptivo'] = $descripciones[$indice];
                    $duplicada['valor_min'] = (float) $duplicada['valor_min'];
                    $duplicada['valor_max'] = $duplicada['valor_max'] === null ? null : (int) $duplicada['valor_max'];
                    yield $duplicada['id'] => $indice % 2 === 0 ? (object) $duplicada : $duplicada;
                }
            }
        })();

        $resultado = ReferenciaLaboratorio::sinDuplicados($referencias);

        self::assertInstanceOf(Collection::class, $resultado);
        self::assertSame($primeras, $resultado->all());
    }

    public function test_preserva_referencias_con_diferencias_clinicas_o_de_contexto(): void
    {
        $base = $this->referencia(['nota' => 'Ayuno']);
        foreach ([
            'otra prueba' => ['prueba_id' => 243],
            'otro grupo etario' => ['grupo_etario_id' => 8],
            'otro genero' => ['genero' => 'Masculino'],
            'otro operador' => ['operador' => '<='],
            'otro minimo' => ['valor_min' => '1.00'],
            'otro maximo' => ['valor_max' => '5.00'],
            'otra precision decimal' => ['valor_max' => '4.01'],
            'otras unidades' => ['unidades' => 'ng/L'],
            'otro caso en unidades' => ['unidades' => 'NG/mL'],
            'otra nota' => ['nota' => 'Sin ayuno'],
            'otro caso en nota' => ['nota' => 'ayuno'],
            'otro descriptivo' => ['descriptivo' => 'NO FUMADORES'],
        ] as $caso => $cambios) {
            $distinta = array_replace($base, $cambios);

            self::assertSame([$base, $distinta], ReferenciaLaboratorio::sinDuplicados([$base, $distinta])->all(), $caso);
        }
    }

    public function test_un_limite_ausente_no_es_equivalente_a_cero(): void
    {
        $sinLimite = $this->referencia(['operador' => '>', 'valor_min' => '10.00', 'valor_max' => null]);
        $conCero = array_replace($sinLimite, ['valor_max' => '0.00']);

        self::assertSame([$sinLimite, $conCero], ReferenciaLaboratorio::sinDuplicados([$sinLimite, $conCero])->all());
    }

    public function test_una_lista_vacia_devuelve_una_coleccion_vacia(): void
    {
        $resultado = ReferenciaLaboratorio::sinDuplicados([]);

        self::assertInstanceOf(Collection::class, $resultado);
        self::assertSame([], $resultado->all());
    }

    private function referencia(array $cambios = []): array
    {
        return array_replace([
            'prueba_id' => 182,
            'grupo_etario_id' => 10,
            'genero' => 'Ambos',
            'operador' => 'rango',
            'valor_min' => '0.00',
            'valor_max' => '4.00',
            'descriptivo' => 'NORMAL O BAJO RIESGO',
            'unidades' => 'ng/mL',
            'nota' => null,
        ], $cambios);
    }
}
