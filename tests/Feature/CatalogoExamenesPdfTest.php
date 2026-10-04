<?php

namespace Tests\Feature;

use App\Services\CatalogoExamenesPdf;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CatalogoExamenesPdfTest extends TestCase
{
    public function test_complete_catalogue_fits_two_a4_pages_with_blank_patient_fields_and_no_automatic_date(): void
    {
        // Catálogo sin datos personales: reproduce el volumen y nombres del PDF reportado.
        $catalogue = json_decode(file_get_contents(base_path('tests/Fixtures/catalogo-examenes.json')));
        $service = app(CatalogoExamenesPdf::class);
        $layout = $service->organizar($catalogue->areas, $catalogue->perfiles);
        $this->assertCount(2, $layout['paginas']);
        $actual = [];
        $profiles = [];
        foreach ($layout['paginas'] as $page) {
            $this->assertLessThanOrEqual(3, count($page));
            foreach ($page as $column) {
                $bottom = 0;
                foreach ($column as $section) {
                    $this->assertGreaterThanOrEqual($bottom, $section['y']);
                    $bottom = $section['y'] + $section['alto_titulo'];
                    if ($section['perfil']) {
                        $this->assertFalse($section['continuacion']);
                        $profiles[] = $section['titulo'];
                    }
                    foreach ($section['filas'] as $row) {
                        $this->assertEqualsWithDelta($bottom, $section['y'] + $row['y'], 0.01);
                        $bottom += $row['alto'];
                        $this->assertLessThanOrEqual(CatalogoExamenesPdf::COLUMN_HEIGHT, $bottom);
                        $actual[] = [$row['nombre'], $row['precio']];
                    }
                }
            }
        }
        $expected = [];
        foreach ($catalogue->areas as $area) {
            foreach ($area->examenes as $exam) $expected[] = [$exam->nombre, (float) $exam->precio];
        }
        foreach ($catalogue->perfiles as $profile) {
            foreach ($profile->examenes as $exam) $expected[] = [$exam->nombre, null];
        }
        $this->assertSame($expected, $actual);
        $this->assertCount(210, $actual);
        $this->assertCount(7, $profiles);
        $html = view('pdf.reporte-examenes', $layout + [
            'logoPath' => public_path(config('laboratorio.logo')),
        ])->render();
        $this->assertSame(217, substr_count($html, '<span class="circle"></span>'));
        $this->assertStringContainsString('Fecha: <span class="write-line"', $html);
        $this->assertStringContainsString('Firma y sello del médico', $html);
        $this->assertStringContainsString('INDICACIONES GENERALES', $html);
        $this->assertStringNotContainsString('Página', $html);
        $this->assertStringNotContainsString(now()->format('d/m/Y'), $html);
        $pdf = $service->generar($catalogue->areas, $catalogue->perfiles);
        $canvas = $pdf->getDomPDF()->getCanvas();
        $this->assertSame(2, $canvas->get_page_count());
        $this->assertEqualsWithDelta(595.28, $canvas->get_width(), 0.1);
        $this->assertEqualsWithDelta(841.89, $canvas->get_height(), 0.1);
        $this->assertStringStartsWith('%PDF-', $pdf->output());
    }

    public function test_oversized_catalogue_is_rejected_instead_of_truncated_or_printed_on_extra_pages(): void
    {
        $area = (object) [
            'nombre' => 'Área de prueba',
            'examenes' => array_fill(0, 750, (object) ['nombre' => 'Examen de prueba', 'precio' => 10]),
        ];
        $this->expectException(ValidationException::class);
        app(CatalogoExamenesPdf::class)->organizar([$area], []);
    }

    public function test_optional_price_list_keeps_all_exams_and_profiles_in_two_pages(): void
    {
        $catalogue = json_decode(file_get_contents(base_path('tests/Fixtures/catalogo-examenes.json')));
        $service = app(CatalogoExamenesPdf::class);
        $layout = $service->organizar($catalogue->areas, $catalogue->perfiles, true);
        $this->assertTrue($layout['mostrarPrecios']);
        $rows = collect($layout['paginas'])->flatten(1)->flatMap(fn ($column) => $column)->sum(fn ($section) => count($section['filas']));
        $this->assertSame(210, $rows);
        $pdf = $service->generar($catalogue->areas, $catalogue->perfiles, true);
        $this->assertSame(2, $pdf->getDomPDF()->getCanvas()->get_page_count());
        $this->assertStringStartsWith('%PDF-', $pdf->output());
    }
}
