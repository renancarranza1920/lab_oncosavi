<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Orden;
use App\Models\DetalleOrden;
use App\Models\Examen;
use App\Models\Muestra;
use App\Services\ReporteResultadosPdf;
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
            $this->assertStringStartsWith('%PDF-', ReporteResultadosPdf::generar($data)->output());
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_reporte_corto_y_uroanalisis_comparten_pagina_con_sus_firmas_sin_hojas_de_sellos(): void
    {
        $datos = $this->datosReporte();
        $pdf = ReporteResultadosPdf::generar($datos);
        $this->assertStringStartsWith('%PDF-', $pdf->output());
        $this->assertSame(4, $pdf->getDomPDF()->getCanvas()->get_page_count());

        $paginas = $this->contenidoPaginas($pdf);
        $this->assertCount(4, $paginas);
        foreach ($paginas as $contenido) {
            // Tres imágenes: sello institucional, sello del autor y firma del autor.
            $this->assertSame(3, preg_match_all('/\/I\d+ Do/', $contenido));
            preg_match_all('/([\d.]+) 0 0 ([\d.]+) ([\d.]+) ([\d.]+) cm \/I\d+ Do/', $contenido, $imagenes, PREG_SET_ORDER);
            foreach ($imagenes as $imagen) {
                // El origen del PDF está abajo: las imágenes quedan sobre el pie.
                $this->assertGreaterThanOrEqual(54, (float) $imagen[4]);
            }
            $this->assertCount(3, $imagenes);
            [$institucional, $sello, $firma] = $imagenes;
            // Ampliar el institucional conservando el tamaño de firma y sello personal.
            $this->assertEqualsWithDelta(110, (float) $institucional[1], 0.001);
            $this->assertEqualsWithDelta(299.28, (float) $institucional[3], 0.001);
            $institucionalArriba = $pdf->getDomPDF()->getCanvas()->get_height() - (float) $institucional[4] - (float) $institucional[2];
            $this->assertLessThan(720, $institucionalArriba);
            $this->assertEqualsWithDelta(127.5, (float) $sello[1], 0.001);
            $this->assertEqualsWithDelta(127.5 * 190 / 420, (float) $sello[2], 0.001);
            $this->assertEqualsWithDelta(78.75, (float) $firma[1], 0.001);
            $this->assertEqualsWithDelta(78.75, (float) $firma[2], 0.001);

            // Comprobar la tinta visible: los márgenes transparentes no deben
            // achicar la firma ni obligar a separarla excesivamente del sello.
            $firmaVisibleAbajo = (float) $firma[4] + (float) $firma[2] * (1 - 235 / 260);
            $firmaVisibleArriba = (float) $firma[4] + (float) $firma[2] * (1 - 69 / 260);
            $selloVisibleArriba = (float) $sello[4] + (float) $sello[2] * (1 - 28 / 190);
            $this->assertEqualsWithDelta(0, $firmaVisibleAbajo - $selloVisibleArriba, 0.001);
            $this->assertLessThanOrEqual(191.25 - 6, $firmaVisibleArriba);
            $this->assertLessThanOrEqual(191.25, (float) $institucional[4] + (float) $institucional[2]);
            $this->assertLessThanOrEqual(191.25, (float) $sello[4] + (float) $sello[2]);
        }
    }

    public function test_firma_horizontal_conserva_tamano_y_toca_el_borde_superior_del_sello(): void
    {
        $datos = $this->datosReporte();
        $datos['grupos_por_usuario'][0]['datos'] = ['ELECTROLITOS' => $this->examen('Potasio', 2)];
        // Dimensiones y márgenes del ejemplo; imágenes sintéticas sin datos personales.
        $datos['grupos_por_usuario'][0]['firma_b64'] = self::imagenTransparente(420, 242, [21, 12, 408, 234], [0, 0, 200]);
        $datos['grupos_por_usuario'][0]['sello_b64'] = self::imagenTransparente(420, 165, [10, 10, 410, 148], [0, 0, 200]);
        $pdf = ReporteResultadosPdf::generar($datos);
        $pdf->output();
        $paginas = $this->contenidoPaginas($pdf);
        $this->assertCount(1, $paginas);
        preg_match_all('/([\d.]+) 0 0 ([\d.]+) ([\d.]+) ([\d.]+) cm \/I\d+ Do/', $paginas[0], $imagenes, PREG_SET_ORDER);
        $this->assertCount(3, $imagenes);
        [, $sello, $firma] = $imagenes;
        $this->assertEqualsWithDelta(127.5, (float) $firma[1], 0.001);
        $this->assertEqualsWithDelta(127.5 * 242 / 420, (float) $firma[2], 0.001);
        $abajoFirma = (float) $firma[4] + (float) $firma[2] * (1 - 235 / 242);
        $arribaFirma = (float) $firma[4] + (float) $firma[2] * (1 - 12 / 242);
        $arribaSello = (float) $sello[4] + (float) $sello[2] * (1 - 10 / 165);
        $this->assertEqualsWithDelta(0, $abajoFirma - $arribaSello, 0.002);
        $centroFirma = $pdf->getDomPDF()->getCanvas()->get_height() - ($arribaFirma + $abajoFirma) / 2;
        $this->assertEqualsWithDelta(697.78, $centroFirma, 0.5);
        $this->assertGreaterThanOrEqual(54, (float) $sello[4]);
    }

    public function test_firma_con_fondo_opaco_conserva_tamano_sin_tapar_sello_ni_resultados(): void
    {
        $datos = $this->datosReporte();
        $datos['grupos_por_usuario'][0]['datos'] = ['ELECTROLITOS' => $this->examen('Potasio', 2)];
        $datos['grupos_por_usuario'][0]['firma_b64'] = self::imagen(100, 0, 150, 260, 260);
        $datos['grupos_por_usuario'][0]['sello_b64'] = self::imagen(0, 140, 0, 420, 190);
        $margen = null;
        view()->composer('pdf.reporte_resultados', function ($vista) use (&$margen) {
            $margen = $vista->getData()['margen_inferior_px'];
        });
        $pdf = ReporteResultadosPdf::generar($datos);
        $pdf->output();
        $paginas = $this->contenidoPaginas($pdf);
        $this->assertCount(1, $paginas);
        preg_match_all('/([\d.]+) 0 0 ([\d.]+) ([\d.]+) ([\d.]+) cm \/I\d+ Do/', $paginas[0], $imagenes, PREG_SET_ORDER);
        $this->assertCount(3, $imagenes);
        [, $sello, $firma] = $imagenes;
        $this->assertEqualsWithDelta(78.75, (float) $firma[1], 0.001);
        $this->assertEqualsWithDelta(78.75, (float) $firma[2], 0.001);
        $this->assertEqualsWithDelta(0, (float) $firma[4] - ((float) $sello[4] + (float) $sello[2]), 0.002);
        $this->assertLessThanOrEqual($margen * 0.75 - 6, (float) $firma[4] + (float) $firma[2]);
    }

    public function test_tabla_extensa_repite_solo_las_firmas_de_su_autor_en_cada_pagina(): void
    {
        $datos = $this->datosReporte(70);
        $datos['grupos_por_usuario'][0]['datos'] = [
            'UROANALISIS' => $datos['grupos_por_usuario'][0]['datos']['UROANALISIS'],
        ];
        $otro = $datos['grupos_por_usuario'][0];
        $otro['firma_b64'] = null;
        $otro['sello_b64'] = null;
        $otro['datos'] = ['ELECTROLITOS' => $this->examen('Potasio', 2)];
        $datos['grupos_por_usuario'][] = $otro;
        $pdf = ReporteResultadosPdf::generar($datos);
        $pdf->output();
        $paginas = $this->contenidoPaginas($pdf);
        $this->assertGreaterThan(2, count($paginas));
        $ultima = array_pop($paginas);
        $this->assertSame(1, preg_match_all('/\/I\d+ Do/', $ultima));
        foreach ($paginas as $contenido) {
            $this->assertSame(3, preg_match_all('/\/I\d+ Do/', $contenido));
        }
    }

    private function contenidoPaginas($pdf): array
    {
        $objetos = $pdf->getDomPDF()->getCanvas()->get_cpdf()->objects;

        return array_values(array_map(
            fn ($pagina) => implode('', array_map(fn ($id) => $objetos[$id]['c'], $pagina['info']['contents'])),
            array_filter($objetos, fn ($objeto) => $objeto['t'] === 'page'),
        ));
    }

    private function examen(string $nombre, int $filas): array
    {
        $pruebas = [];
        for ($i = 0; $i < $filas; $i++) {
            $pruebas[] = [
                'nombre' => 'Prueba de ejemplo '.($i + 1), 'resultado' => 'NO SE OBSERVAN',
                'referencia' => '', 'unidades' => '', 'tipo_prueba' => $nombre === 'General de orina' ? ($i < 13 ? 'FISICO - QUIMICO' : 'MICROSCOPICO') : '',
            ];
        }

        return [['nombre' => $nombre, 'pruebas_unitarias' => $pruebas, 'matrices' => []]];
    }

    private function datosReporte(int $filasOrina = 24): array
    {
        $cliente = new Cliente(['nombre' => 'Paciente', 'apellido' => 'Ejemplo', 'fecha_nacimiento' => '1990-04-15', 'genero' => 'Femenino']);
        $orden = new Orden();
        $orden->id = 1;
        $orden->created_at = now();
        $orden->setRelation('cliente', $cliente);
        $orden->setRelation('medico', null);
        $areas = [
            'ELECTROLITOS' => $this->examen('Potasio', 2),
            'HEMATOLOGÍA' => $this->examen('Hemograma', 18),
            'QUÍMICA SANGUÍNEA' => $this->examen('Glucosa', 9),
            'UROANALISIS' => $this->examen('General de orina', $filasOrina),
        ];
        $detalles = collect();
        foreach ($areas as $examenes) {
            $examen = new Examen(['nombre' => $examenes[0]['nombre']]);
            $examen->setRelation('muestras', collect([new Muestra(['nombre' => 'Muestra de ejemplo'])]));
            $detalle = new DetalleOrden(['nombre_examen' => $examenes[0]['nombre']]);
            $detalle->setRelation('examen', $examen);
            $detalles->push($detalle);
        }
        $orden->setRelation('detalleOrden', $detalles);

        return [
            'orden' => $orden, 'logo_b64' => null, 'sello_registro_b64' => self::imagen(0, 0, 200),
            'grupos_por_usuario' => [[
                'laboratorista' => 'Laboratorista de ejemplo',
                'firma_b64' => self::imagenTransparente(260, 260, [43, 69, 216, 234], [100, 0, 150]),
                'sello_b64' => self::imagenTransparente(420, 190, [36, 28, 380, 159], [0, 140, 0]), 'datos' => $areas,
            ]],
        ];
    }

    private static function imagenTransparente(int $ancho, int $alto, array $limites, array $rgb): string
    {
        $imagen = imagecreatetruecolor($ancho, $alto);
        imagealphablending($imagen, false);
        imagesavealpha($imagen, true);
        imagefill($imagen, 0, 0, imagecolorallocatealpha($imagen, 255, 255, 255, 127));
        imagefilledrectangle($imagen, ...[...$limites, imagecolorallocate($imagen, ...$rgb)]);
        ob_start();
        imagepng($imagen);
        $contenido = ob_get_clean();
        imagedestroy($imagen);

        return 'data:image/png;base64,'.base64_encode($contenido);
    }

    public static function imagen(int $rojo, int $verde, int $azul, int $ancho = 160, int $alto = 85): string
    {
        $imagen = imagecreatetruecolor($ancho, $alto);
        $blanco = imagecolorallocate($imagen, 255, 255, 255);
        imagefill($imagen, 0, 0, $blanco);
        $color = imagecolorallocate($imagen, $rojo, $verde, $azul);
        imagerectangle($imagen, 1, 1, $ancho - 2, $alto - 2, $color);
        imagestring($imagen, 4, 14, 34, 'EJEMPLO', $color);
        ob_start();
        imagepng($imagen);
        $contenido = ob_get_clean();
        imagedestroy($imagen);

        return 'data:image/png;base64,'.base64_encode($contenido);
    }
}
