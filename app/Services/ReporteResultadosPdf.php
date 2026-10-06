<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DocumentoPdf;

class ReporteResultadosPdf
{
    public static function generar(array $datos): DocumentoPdf
    {
        return Pdf::setOptions([
            'isRemoteEnabled' => false,
            'isHtml5ParserEnabled' => true,
            'dpi' => 96,
            'defaultFont' => 'sans-serif',
            'chroot' => base_path(),
        ])->loadView('pdf.reporte_resultados', $datos)->setPaper('a4');
    }
}
