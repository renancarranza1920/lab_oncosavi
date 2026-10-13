<?php

namespace Tests\Feature;

use Barryvdh\DomPDF\Facade\Pdf;
use Tests\TestCase;

class PdfOriginalesLameriTest extends TestCase
{
    public function test_cotizacion_conserva_encabezado_original_y_muestra_autor_perfiles_y_precios(): void
    {
        $html = view('pdf.cotizacion', $this->detalleComercial() + [
            'cliente_nombre' => 'Paciente de demostración',
            'usuario_nombre' => 'Recepción de demostración',
            'total' => 29.50,
        ])->render();

        $this->assertStringContainsString('<div class="header-left">', $html);
        $this->assertStringContainsString('<div class="header-right">', $html);
        $this->assertStringContainsString(public_path('images/lameri-original/logo.png'), $html);
        $this->assertStringContainsString('Cotización generada por: Recepción de demostración', $html);
        $this->assertStringContainsString('Paciente de demostración', $html);
        $this->assertStringContainsString('Perfil de demostración', $html);
        $this->assertStringContainsString('- Glucosa de demostración', $html);
        $this->assertStringContainsString('$20.00', $html);
        $this->assertStringContainsString('$9.50', $html);
        $this->assertStringContainsString('$29.50', $html);
        $this->assertStringContainsString('validez de 30 días', $html);
        $this->assertStringNotContainsString('DOCUMENTO COMERCIAL', $html);
        $this->assertStringNotContainsString('contact-bar', $html);

        $this->guardarPdf('cotizacion', $html);
    }

    public function test_comprobante_conserva_encabezado_original_y_desglose_actual_con_descuento_y_dui(): void
    {
        $html = view('pdf.comprobante', $this->detalleComercial() + [
            'cliente' => $this->cliente(),
            'usuario_nombre' => 'Recepción de demostración',
            'subtotal' => 29.50,
            'descuento' => 4.50,
            'codigo' => 'DEMO',
            'total' => 25,
        ])->render();

        $this->assertStringContainsString('<div class="header-left">', $html);
        $this->assertStringContainsString('<div class="header-right">', $html);
        $this->assertStringContainsString(public_path('images/lameri-original/logo.png'), $html);
        $this->assertStringContainsString('Atendido por: Recepción de demostración', $html);
        $this->assertStringContainsString('DEMO-001', $html);
        $this->assertStringContainsString('00000000-0', $html);
        $this->assertStringContainsString('Perfil de demostración', $html);
        $this->assertStringContainsString('- Glucosa de demostración', $html);
        $this->assertStringContainsString('$29.50', $html);
        $this->assertStringContainsString('- $4.50', $html);
        $this->assertStringContainsString('(DEMO)', $html);
        $this->assertStringContainsString('$25.00', $html);
        $this->assertStringContainsString('La orden aún no ha sido confirmada.', $html);
        $this->assertStringNotContainsString('WhatsApp:', $html);

        $this->guardarPdf('comprobante', $html);
    }

    public function test_boleta_conserva_dos_copias_sin_branding_agregado_y_cobra_perfiles_una_vez_en_cada_copia(): void
    {
        $orden = (object) [
            'id' => 42,
            'cliente' => $this->cliente(),
            'created_at' => now(),
            'descuento' => 4.50,
            'total' => 25,
            'detalleOrden' => collect([
                (object) ['nombre_perfil' => 'Perfil de demostración', 'precio_perfil' => 20, 'nombre_examen' => 'Glucosa de demostración', 'precio_examen' => 10],
                (object) ['nombre_perfil' => 'Perfil de demostración', 'precio_perfil' => 20, 'nombre_examen' => 'Colesterol de demostración', 'precio_examen' => 10],
                (object) ['nombre_perfil' => null, 'nombre_examen' => 'Examen individual de demostración', 'precio_examen' => 9.50],
            ]),
        ];
        $html = view('pdf.boleta-simple', ['orden' => $orden, 'usuario' => 'Recepción de demostración'])->render();

        $this->assertStringContainsString('(COPIA CLIENTE)', $html);
        $this->assertStringContainsString('(COPIA LABORATORIO)', $html);
        $this->assertStringContainsString('class="separator"', $html);
        $this->assertSame(2, substr_count($html, 'ORDEN #000042'));
        $this->assertSame(2, substr_count($html, 'DEMO-001'));
        $this->assertSame(2, substr_count($html, '00000000-0'));
        $this->assertSame(2, substr_count($html, 'Perfil de demostración'));
        $this->assertSame(2, substr_count($html, '$20.00'));
        $this->assertSame(2, substr_count($html, '$9.50'));
        $this->assertSame(2, substr_count($html, 'Desc: -$4.50'));
        $this->assertSame(2, substr_count($html, 'TOTAL: $25.00'));
        $this->assertStringNotContainsString('WhatsApp:', $html);
        $this->assertStringNotContainsString('<img', $html);

        $this->guardarPdf('boleta-simple', $html);
    }

    private function detalleComercial(): array
    {
        return [
            'perfiles' => [[
                'nombre' => 'Perfil de demostración',
                'precio' => 20,
                'examenes' => collect([(object) ['nombre' => 'Glucosa de demostración']]),
            ]],
            'examenes' => [['nombre' => 'Examen individual de demostración', 'precio' => 9.50]],
        ];
    }

    private function cliente(): object
    {
        return (object) [
            'nombre' => 'Paciente', 'apellido' => 'de demostración',
            'NumeroExp' => 'DEMO-001', 'dui' => '00000000-0',
        ];
    }

    private function guardarPdf(string $nombre, string $html): void
    {
        $pdf = Pdf::loadHTML($html)->setPaper('letter', 'portrait');
        $contenido = $pdf->output();
        $this->assertStringStartsWith('%PDF-', $contenido);
        $this->assertSame(1, $pdf->getDomPDF()->getCanvas()->get_page_count());

        $carpeta = storage_path('framework/testing/lameri-originales');
        if (!is_dir($carpeta)) {
            mkdir($carpeta, 0755, true);
        }
        file_put_contents($carpeta.'/'.$nombre.'.pdf', $contenido);
        file_put_contents($carpeta.'/'.$nombre.'.html', $html);
    }
}
