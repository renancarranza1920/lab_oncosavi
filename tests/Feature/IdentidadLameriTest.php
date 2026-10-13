<?php

namespace Tests\Feature;

use Barryvdh\DomPDF\Facade\Pdf;
use Tests\TestCase;

class IdentidadLameriTest extends TestCase
{
    public function test_login_exposes_the_brand_without_contact_strips(): void
    {
        $this->get(route('filament.admin.auth.login'))->assertOk()
            ->assertSee('LABORATORIO CLÍNICO MERINO')
            ->assertSee('images/merino-horizontal-claro.png')
            ->assertSee('images/merino-horizontal-oscuro.png')
            ->assertDontSee('oncosavi@gmail.com')
            ->assertDontSee('tel:+50326066596', false)
            ->assertDontSee('https://wa.me/50375954210', false);
    }

    public function test_documents_render_with_merino_identity_and_distinct_contact_numbers(): void
    {
        config(['laboratorio.correo' => '']);

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
        $this->assertStringContainsString('LABORATORIO CLÍNICO MERINO', $html);
        $this->assertStringContainsString('2606-6596', $html);
        $this->assertStringContainsString('7595-4210', $html);
        $this->assertStringContainsString('4ª Calle Oriente #6', $html);
        $this->assertStringNotContainsString('ONCOSAVI', $html);
        $this->assertStringNotContainsString('images/pdf-crab.svg', $html);
        $this->assertStringNotContainsString('mailto:', $html);
        $this->assertStringContainsString('Documento de demostración, sin datos clínicos reales.', $html);
        $pdf = Pdf::loadHTML($html)->setPaper('letter');
        $output = $pdf->output();
        $this->assertStringStartsWith('%PDF-', $output);
        $this->assertGreaterThan(1, $pdf->getDomPDF()->getCanvas()->get_page_count());
        $folder = storage_path('framework/testing/merino');
        if (!is_dir($folder)) mkdir($folder, 0755, true);
        file_put_contents($folder . '/resultados.pdf', $output);
        file_put_contents($folder . '/resultados.html', $html);

        $templates = [
            'reporte-examenes' => ['areas' => collect(), 'perfiles' => collect(), 'fecha' => now(), 'usuario' => 'DEMOSTRACIÓN', 'logoPath' => public_path(config('laboratorio.logo'))],
        ];
        foreach ($templates as $template => $values) {
            $html = view('pdf.' . $template, $values)->render();
            $this->assertStringContainsString('LABORATORIO CLÍNICO MERINO', $html);
            $this->assertStringContainsString('2606-6596', $html);
            $this->assertStringContainsString('7595-4210', $html);
            $this->assertStringNotContainsString('ONCOSAVI', $html);
            $this->assertStringNotContainsString('mailto:', $html);
            $output = Pdf::loadHTML($html)->setPaper('letter')->output();
            $this->assertStringStartsWith('%PDF-', $output);
            file_put_contents($folder . '/' . $template . '.pdf', $output);
        }
    }

    public function test_pdf_contact_uses_configured_email_and_distinct_phone_links(): void
    {
        config(['laboratorio.correo' => 'recepcion@merino.test']);

        $html = view('pdf.contacto')->render();

        $this->assertStringContainsString('mailto:recepcion@merino.test', $html);
        $this->assertStringContainsString('tel:+50326066596', $html);
        $this->assertStringContainsString('https://wa.me/50375954210', $html);
        $this->assertStringContainsString('WhatsApp: +503 7595-4210', $html);
    }
}
