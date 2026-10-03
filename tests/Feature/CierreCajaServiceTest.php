<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Orden;
use App\Services\CierreCajaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CierreCajaServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_calcula_el_cierre_sin_sumar_ordenes_canceladas(): void
    {
        $cliente = Cliente::create([
            'nombre' => 'Paciente',
            'apellido' => 'Prueba',
            'genero' => 'Femenino',
            'estado' => 'Activo',
        ]);

        Orden::create(['cliente_id' => $cliente->id, 'total' => 100, 'descuento' => 10, 'fecha' => '2026-10-01', 'estado' => 'finalizado']);
        Orden::create(['cliente_id' => $cliente->id, 'total' => 50, 'descuento' => 0, 'fecha' => '2026-10-15', 'estado' => 'pendiente']);
        Orden::create(['cliente_id' => $cliente->id, 'total' => 70, 'descuento' => 5, 'fecha' => '2026-10-20', 'estado' => 'cancelado']);
        Orden::create(['cliente_id' => $cliente->id, 'total' => 999, 'descuento' => 0, 'fecha' => '2026-09-30', 'estado' => 'finalizado']);

        $datos = app(CierreCajaService::class)->generar('mensual', 2026, 10);

        $this->assertSame(3, $datos['resumen']['ordenes']);
        $this->assertSame(2, $datos['resumen']['ordenes_vigentes']);
        $this->assertSame(1, $datos['resumen']['ordenes_canceladas']);
        $this->assertSame(160.0, $datos['resumen']['ingreso_bruto']);
        $this->assertSame(10.0, $datos['resumen']['descuentos']);
        $this->assertSame(150.0, $datos['resumen']['ingreso_neto']);
        $this->assertSame(75.0, $datos['resumen']['valor_cancelado']);
        $this->assertArrayNotHasKey('ticket_promedio', $datos['resumen']);
        $this->assertCount(2, $datos['movimientos']);
    }

    public function test_genera_los_rangos_diario_mensual_trimestral_y_anual(): void
    {
        $servicio = app(CierreCajaService::class);

        [$desdeDia, $hastaDia, $etiquetaDia] = $servicio->rango('diario', 2026, 10, 1, 15);
        [$desdeMes, $hastaMes, $etiquetaMes] = $servicio->rango('mensual', 2026, 2);
        [$desdeTrimestre, $hastaTrimestre, $etiquetaTrimestre] = $servicio->rango('trimestral', 2026, 1, 3);
        [$desdeAnio, $hastaAnio, $etiquetaAnio] = $servicio->rango('anual', 2026);

        $this->assertSame('2026-10-15', $desdeDia->toDateString());
        $this->assertSame('2026-10-15', $hastaDia->toDateString());
        $this->assertSame('2026-02-01', $desdeMes->toDateString());
        $this->assertSame('2026-02-28', $hastaMes->toDateString());
        $this->assertSame('2026-07-01', $desdeTrimestre->toDateString());
        $this->assertSame('2026-09-30', $hastaTrimestre->toDateString());
        $this->assertSame('2026-01-01', $desdeAnio->toDateString());
        $this->assertSame('2026-12-31', $hastaAnio->toDateString());
        $this->assertSame('15 de octubre de 2026', $etiquetaDia);
        $this->assertSame('Febrero 2026', $etiquetaMes);
        $this->assertSame('Trimestre 3 de 2026', $etiquetaTrimestre);
        $this->assertSame('Año 2026', $etiquetaAnio);
    }

    public function test_el_cierre_diario_no_incluye_otros_dias_y_excluye_cancelaciones_del_neto(): void
    {
        $cliente = Cliente::create(['nombre' => 'Paciente', 'apellido' => 'Diario', 'genero' => 'Femenino']);
        foreach ([['2026-10-01', 'finalizado', 999], ['2026-10-02', 'finalizado', 25], ['2026-10-02', 'cancelado', 75], ['2026-10-03', 'pendiente', 999]] as [$fecha, $estado, $total]) {
            Orden::create(['cliente_id' => $cliente->id, 'fecha' => $fecha, 'estado' => $estado, 'total' => $total]);
        }
        $datos = app(CierreCajaService::class)->generar('diario', 2026, 10, 1, 2);
        $this->assertSame(2, $datos['resumen']['ordenes']);
        $this->assertSame(25.0, $datos['resumen']['ingreso_neto']);
        $this->assertSame(75.0, $datos['resumen']['valor_cancelado']);
        $this->assertCount(1, $datos['movimientos']);
        [$desde, $hasta] = app(CierreCajaService::class)->rango('diario', 2024, 2, 1, 31);
        $this->assertSame('2024-02-29', $desde->toDateString());
        $this->assertSame('2024-02-29', $hasta->toDateString());
    }
}
