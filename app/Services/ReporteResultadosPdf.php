<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DocumentoPdf;
use Dompdf\Canvas;
use Dompdf\Frame;
use Dompdf\Image\Cache;

class ReporteResultadosPdf
{
    public static function generar(array $datos): DocumentoPdf
    {
        $pdf = Pdf::setOptions([
            'isRemoteEnabled' => false,
            'isHtml5ParserEnabled' => true,
            'dpi' => 96,
            'defaultFont' => 'sans-serif',
            'chroot' => base_path(),
        ])->loadView('pdf.reporte_resultados', $datos)->setPaper('a4');

        $firmantesPorPagina = [];
        $dompdf = $pdf->getDomPDF();
        $pdf->setCallbacks([
            [
                'event' => 'end_frame',
                'f' => static function (Frame $frame, Canvas $canvas) use (&$firmantesPorPagina): void {
                    $nodo = $frame->get_node();
                    if ($nodo instanceof \DOMElement && $nodo->hasAttribute('data-firmante')) {
                        // Dompdf repite la tabla al paginar; cada fragmento conserva su autor.
                        $firmantesPorPagina[$canvas->get_page_number()] = (int) $nodo->getAttribute('data-firmante');
                    }
                },
            ],
            [
                'event' => 'end_document',
                'f' => static function (int $pagina, int $total, Canvas $canvas) use (&$firmantesPorPagina, $datos, $dompdf): void {
                    if (!array_key_exists($pagina, $firmantesPorPagina)) {
                        return;
                    }
                    $grupo = $datos['grupos_por_usuario'][$firmantesPorPagina[$pagina]];
                    $derecha = $canvas->get_width() - 36;
                    $abajo = $canvas->get_height();

                    // Coordenadas en puntos. Esta franja está reservada por el margen inferior.
                    self::dibujar($canvas, $dompdf, $datos['sello_registro_b64'] ?? null, $derecha - 248, $abajo - 140, 97.5, 67.5);
                    self::dibujar($canvas, $dompdf, $grupo['sello_b64'] ?? null, $derecha - 127.5, $abajo - 140, 127.5, 67.5);
                    self::dibujar($canvas, $dompdf, $grupo['firma_b64'] ?? null, $derecha - 127.5, $abajo - 160, 127.5, 78.75);
                },
            ],
        ]);

        return $pdf;
    }

    private static function dibujar(Canvas $canvas, \Dompdf\Dompdf $dompdf, ?string $imagen, float $x, float $y, float $ancho, float $alto): void
    {
        if (!$imagen) {
            return;
        }
        [$archivo, , $error] = Cache::resolve_url($imagen, $dompdf->getProtocol(), $dompdf->getBaseHost(), $dompdf->getBasePath(), $dompdf->getOptions());
        $tamano = $error ? false : @getimagesize($archivo);
        if (!$tamano) {
            return;
        }

        $escala = min($ancho / $tamano[0], $alto / $tamano[1]);
        $anchoReal = $tamano[0] * $escala;
        $altoReal = $tamano[1] * $escala;
        $canvas->image($archivo, $x + ($ancho - $anchoReal) / 2, $y + ($alto - $altoReal) / 2, $anchoReal, $altoReal);
    }
}
