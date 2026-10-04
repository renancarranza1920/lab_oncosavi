<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Orden;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Tests\TestCase;

class ReporteResultadosDisenoTest extends TestCase
{
    public function test_muestra_fecha_y_hora_de_registro_e_impresion_en_hora_del_laboratorio(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-03 14:45:00', 'America/El_Salvador'));
        try {
            $cliente = new Cliente(['nombre' => 'Paciente', 'apellido' => 'Ejemplo', 'fecha_nacimiento' => '1990-04-15', 'genero' => 'Femenino']);
            $orden = new Orden();
            $orden->id = 1;
            $orden->created_at = Carbon::parse('2026-10-01 09:27:00', 'America/El_Salvador');
            $orden->setRelation('cliente', $cliente);
            $orden->setRelation('medico', null);
            $orden->setRelation('detalleOrden', collect());
            $data = ['orden' => $orden, 'logo_b64' => null, 'sello_registro_b64' => null, 'grupos_por_usuario' => []];
            $html = view('pdf.reporte_resultados', $data)->render();
            $this->assertStringContainsString('Fecha de registro:', $html);
            $this->assertStringContainsString('01/10/2026 · 09:27', $html);
            $this->assertStringContainsString('Fecha de impresión:', $html);
            $this->assertStringContainsString('03/10/2026 · 14:45', $html);
            $this->assertStringStartsWith('%PDF-', Pdf::loadHTML($html)->setPaper('letter')->output());
        } finally {
            Carbon::setTestNow();
        }
    }
}
