<?php

namespace App\Services;

use App\Support\ImagenPdf;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DocumentoPdf;

class ReporteResultadosPdf
{
    public static function generar(array $datos): DocumentoPdf
    {
        // Imágenes del reporte original de Lameri. El fondo del hosting se conserva
        // en storage para que las actualizaciones por Git no sustituyan ese archivo.
        $datos += [
            'fl_b64' => ImagenPdf::desdeDiscoPublico('fl.png', 380, 220)
                ?? ImagenPdf::desdeArchivo(public_path('images/lameri-original/fl.png'), 380, 220),
            'iconlab_b64' => ImagenPdf::desdeArchivo(public_path('images/lameri-original/iconlab.png'), 380, 380),
        ];

        return Pdf::setOptions([
            'isRemoteEnabled' => false,
            'isHtml5ParserEnabled' => true,
            'dpi' => 96,
            'defaultFont' => 'sans-serif',
            'chroot' => base_path(),
        ])->loadView('pdf.reporte_resultados', $datos)->setPaper('a4');
    }
}
