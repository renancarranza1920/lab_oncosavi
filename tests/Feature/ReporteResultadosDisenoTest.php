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
                // El origen del PDF está abajo: todas las firmas deben quedar entre
                // el membrete inferior y el límite reservado para los resultados.
                $this->assertGreaterThanOrEqual(72, (float) $imagen[4]);
                $this->assertLessThanOrEqual(168.75, (float) $imagen[4] + (float) $imagen[2]);
            }
        }
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
                'laboratorista' => 'Laboratorista de ejemplo', 'firma_b64' => self::imagen(100, 0, 150),
                'sello_b64' => self::imagen(0, 140, 0), 'datos' => $areas,
            ]],
        ];
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
