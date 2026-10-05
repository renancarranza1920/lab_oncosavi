<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DocumentoPdf;
use Dompdf\Canvas;
use Dompdf\Frame;
use Dompdf\Image\Cache;

class ReporteResultadosPdf
{
    // 15 puntos más abajo: aproximadamente 5 mm, según el recuadro de referencia.
    private const POSICION_SELLOS_DESDE_PIE = -125;

    public static function generar(array $datos): DocumentoPdf
    {
        $pdf = Pdf::setOptions([
            'isRemoteEnabled' => false,
            'isHtml5ParserEnabled' => true,
            'dpi' => 96,
            'defaultFont' => 'sans-serif',
            'chroot' => base_path(),
        ]);

        $dompdf = $pdf->getDomPDF();
        $institucional = self::prepararImagen($dompdf, $datos['sello_registro_b64'] ?? null, 97.5, 67.5);
        $imagenesPorAutor = [];
        $margenInferior = 191.25;
        foreach ($datos['grupos_por_usuario'] as $indice => $grupo) {
            $sello = self::prepararImagen($dompdf, $grupo['sello_b64'] ?? null, 127.5, 67.5, true);
            // Conservar las medidas anteriores. Solo desplazar la imagen completa,
            // sin recortar sus márgenes transparentes ni reducir la firma.
            $firma = self::prepararImagen($dompdf, $grupo['firma_b64'] ?? null, 127.5, 78.75, true);
            $selloY = self::POSICION_SELLOS_DESDE_PIE + ($sello['centrar_y'] ?? 0);
            $firmaY = $selloY + ($sello['superior'] ?? 0) - 6 - ($firma['inferior'] ?? 0);
            if ($firma) {
                $margenInferior = max($margenInferior, -($firmaY + $firma['superior']) + 6);
            }
            $imagenesPorAutor[$indice] = compact('sello', 'firma', 'selloY', 'firmaY');
        }
        $datos['margen_inferior_px'] = (int) ceil($margenInferior / 0.75);
        $pdf->loadView('pdf.reporte_resultados', $datos)->setPaper('a4');

        $firmantesPorPagina = [];
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
                'f' => static function (int $pagina, int $total, Canvas $canvas) use (&$firmantesPorPagina, $imagenesPorAutor, $institucional): void {
                    if (!array_key_exists($pagina, $firmantesPorPagina)) {
                        return;
                    }
                    $imagenes = $imagenesPorAutor[$firmantesPorPagina[$pagina]];
                    $derecha = $canvas->get_width() - 36;
                    $abajo = $canvas->get_height();

                    // Coordenadas en puntos. Esta franja está reservada por el margen inferior.
                    self::dibujar($canvas, $institucional, $derecha - 248, $abajo + self::POSICION_SELLOS_DESDE_PIE + ($institucional['centrar_y'] ?? 0));
                    self::dibujar($canvas, $imagenes['sello'], $derecha - 127.5, $abajo + $imagenes['selloY']);
                    self::dibujar($canvas, $imagenes['firma'], $derecha - 127.5, $abajo + $imagenes['firmaY']);
                },
            ],
        ]);

        return $pdf;
    }

    private static function prepararImagen(\Dompdf\Dompdf $dompdf, ?string $imagen, float $ancho, float $alto, bool $medirOpacidad = false): ?array
    {
        if (!$imagen) {
            return null;
        }
        [$archivo, , $error] = Cache::resolve_url($imagen, $dompdf->getProtocol(), $dompdf->getBaseHost(), $dompdf->getBasePath(), $dompdf->getOptions());
        $tamano = $error ? false : @getimagesize($archivo);
        if (!$tamano) {
            return null;
        }

        $escala = min($ancho / $tamano[0], $alto / $tamano[1]);
        $anchoReal = $tamano[0] * $escala;
        $altoReal = $tamano[1] * $escala;
        [$superior, $inferior] = $medirOpacidad ? self::limitesOpacos($archivo, $tamano[0], $tamano[1]) : [0, $tamano[1]];

        return [
            'archivo' => $archivo, 'ancho' => $anchoReal, 'alto' => $altoReal,
            'centrar_x' => ($ancho - $anchoReal) / 2, 'centrar_y' => ($alto - $altoReal) / 2,
            'superior' => $superior * $escala, 'inferior' => $inferior * $escala,
        ];
    }

    private static function dibujar(Canvas $canvas, ?array $imagen, float $x, float $y): void
    {
        if ($imagen) {
            $canvas->image($imagen['archivo'], $x + $imagen['centrar_x'], $y, $imagen['ancho'], $imagen['alto']);
        }
    }

    private static function limitesOpacos(string $archivo, int $ancho, int $alto): array
    {
        $raster = extension_loaded('gd') ? @imagecreatefromstring(file_get_contents($archivo)) : false;
        if (!$raster) {
            return [0, $alto];
        }
        try {
            $esVisible = static function (int $x, int $y) use ($raster): bool {
                $color = imagecolorat($raster, $x, $y);
                $alpha = imageistruecolor($raster) ? ($color >> 24) & 127 : imagecolorsforindex($raster, $color)['alpha'];

                // El blanco opaco también ocupa espacio: no permitir que tape el sello.
                return $alpha < 127;
            };
            for ($arriba = 0; $arriba < $alto; $arriba++) {
                for ($x = 0; $x < $ancho; $x++) {
                    if ($esVisible($x, $arriba)) {
                        break 2;
                    }
                }
            }
            if ($arriba === $alto) {
                return [0, $alto];
            }
            for ($abajo = $alto - 1; $abajo >= $arriba; $abajo--) {
                for ($x = 0; $x < $ancho; $x++) {
                    if ($esVisible($x, $abajo)) {
                        break 2;
                    }
                }
            }

            return [$arriba, $abajo + 1];
        } finally {
            imagedestroy($raster);
        }
    }
}
