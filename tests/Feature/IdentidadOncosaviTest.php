<?php

namespace Tests\Feature;

use Barryvdh\DomPDF\Facade\Pdf;
use Tests\TestCase;

class IdentidadOncosaviTest extends TestCase
{
    public function test_login_exposes_the_brand_without_contact_strips(): void
    {
        $this->get(route('filament.admin.auth.login'))->assertOk()
            ->assertSee('ONCOSAVI')
            ->assertSee('images/oncosavi.png')
            ->assertDontSee('oncosavi@gmail.com')
            ->assertDontSee('tel:+50323930239', false)
            ->assertDontSee('https://wa.me/50323930239', false);
    }

    public function test_documents_render_with_contact_information_and_a_text_watermark(): void
    {
        $cliente = (object) [
            'nombre' => 'PACIENTE', 'apellido' => 'DE DEMOSTRACIÓN',
            'edad' => 35, 'edad_legible' => '35 años', 'fecha_nacimiento' => null,
            'genero' => 'Femenino', 'NumeroExp' => 'DEMO-001', 'dui' => null,
        ];
        $orden = (object) [
            'id' => 1, 'cliente' => $cliente, 'created_at' => now(), 'medico' => null,
            'observaciones' => 'Documento de demostración, sin datos clínicos reales.',
            'observaciones_por_area' => [], 'detalleOrden' => collect(),
            'total' => 0, 'subtotal' => 0, 'descuento' => 0, 'total_final' => 0,
        ];
        $pruebas = array_fill(0, 65, [
            'nombre' => 'Prueba de demostración', 'resultado' => '12.5',
            'referencia' => '10 - 20', 'unidades' => 'mg/dL', 'alertar' => false,
            'grupo' => null,
        ]);
        $data = [
            'orden' => $orden,
            'logo_b64' => 'data:image/png;base64,' . base64_encode(file_get_contents(public_path(config('laboratorio.logo')))),
            'sello_registro_b64' => null,
            'grupos_por_usuario' => [[
                'firma_b64' => null, 'sello_b64' => null,
                'datos' => ['QUÍMICA SANGUÍNEA' => [[
                    'nombre' => 'Examen de demostración', 'pruebas_unitarias' => $pruebas, 'matrices' => [],
                ]]],
            ]],
        ];
        $html = view('pdf.reporte_resultados', $data)->render();
        $this->assertStringContainsString('oncosavi@gmail.com', $html);
        $this->assertStringContainsString('+503 2393 0239', $html);
        $this->assertStringContainsString('Calle 1 de Julio #23', $html);
        preg_match('/<div class="watermark">(.*?)<\/div>/s', $html, $watermark);
        $this->assertStringContainsString('ONCOSAVI', $watermark[1]);
        $this->assertStringNotContainsString('<img', $watermark[1]);
        $pdf = Pdf::loadHTML($html)->setPaper('letter');
        $output = $pdf->output();
        $this->assertStringStartsWith('%PDF-', $output);
        $this->assertGreaterThan(1, $pdf->getDomPDF()->getCanvas()->get_page_count());
        $folder = storage_path('framework/testing/oncosavi');
        if (!is_dir($folder)) mkdir($folder, 0755, true);
        file_put_contents($folder . '/resultados.pdf', $output);
        file_put_contents($folder . '/resultados.html', $html);

        $templates = [
            'cotizacion' => ['usuario_nombre' => 'DEMOSTRACIÓN', 'cliente_nombre' => 'DEMOSTRACIÓN', 'perfiles' => [], 'examenes' => [], 'subtotal' => 0, 'total' => 0],
            'comprobante' => ['usuario_nombre' => 'DEMOSTRACIÓN', 'cliente' => $cliente, 'orden' => $orden, 'perfiles' => [], 'examenes' => [], 'subtotal' => 0, 'total' => 0],
            'boleta-simple' => ['orden' => $orden, 'usuario' => 'DEMOSTRACIÓN'],
            'reporte-examenes' => ['areas' => collect(), 'perfiles' => collect(), 'fecha' => now(), 'usuario' => 'DEMOSTRACIÓN', 'logoPath' => public_path(config('laboratorio.logo'))],
        ];
        foreach ($templates as $template => $values) {
            $html = view('pdf.' . $template, $values)->render();
            $this->assertStringContainsString('ONCOSAVI', $html);
            $this->assertStringContainsString('oncosavi@gmail.com', $html);
            $output = Pdf::loadHTML($html)->setPaper('letter')->output();
            $this->assertStringStartsWith('%PDF-', $output);
            file_put_contents($folder . '/' . $template . '.pdf', $output);
        }
    }
}
