@php
    $textoObservacionExterna = (string) ($pruebaData['observaciones'] ?? '');
    $fragmentosObservacionExterna = [];

    if (trim($textoObservacionExterna) !== '') {
        // El resultado está centrado: la nota comienza donde empieza su texto
        // y aprovecha el espacio restante de la tabla hacia la derecha.
        $documentoObservacionExterna = app('dompdf');
        $fuentesObservacionExterna = $documentoObservacionExterna->getFontMetrics();
        $fuenteResultadoExterno = $fuentesObservacionExterna->getFont('DejaVu Sans', 'bold');
        $medioAnchoResultadoExterno = $fuentesObservacionExterna->getTextWidth(
            $formatearNumerosReferencia($pruebaData['resultado'] ?? ''),
            $fuenteResultadoExterno,
            9.5 * 72 / $documentoObservacionExterna->getOptions()->getDpi()
        ) / 2;

        // DomPDF no divide una celda alta entre páginas. Las líneas y sus
        // continuaciones van en filas pequeñas, sin modificar el texto.
        foreach (preg_split('/\r\n|\r|\n/', $textoObservacionExterna) as $lineaObservacionExterna) {
            while (mb_strlen($lineaObservacionExterna, 'UTF-8') > 300) {
                $inicioObservacionExterna = mb_substr($lineaObservacionExterna, 0, 300, 'UTF-8');
                $ultimoEspacioObservacionExterna = mb_strrpos($inicioObservacionExterna, ' ', 0, 'UTF-8');
                $corteObservacionExterna = $ultimoEspacioObservacionExterna !== false
                    ? $ultimoEspacioObservacionExterna + 1
                    : 300;
                $fragmentosObservacionExterna[] = mb_substr($lineaObservacionExterna, 0, $corteObservacionExterna, 'UTF-8');
                $lineaObservacionExterna = mb_substr($lineaObservacionExterna, $corteObservacionExterna, null, 'UTF-8');
            }
            $fragmentosObservacionExterna[] = $lineaObservacionExterna;
        }
    }
@endphp
@foreach ($fragmentosObservacionExterna as $indiceObservacionExterna => $fragmentoObservacionExterna)
    <tr class="observacion-externa-row" style="page-break-inside: avoid; page-break-after: auto;">
        <td style="border: 0; padding: 0;"></td>
        <td colspan="{{ $esTablaConRef ? 2 : 1 }}" style="border: 0; padding: {{ $indiceObservacionExterna === 0 ? '3px' : '0' }} 0 2px; font-size: 8.5px; line-height: 1.25; text-align: left;">
            <div style="margin-left: {{ $esTablaConRef ? '20.833333%' : '50%' }};">
                <div style="margin-left: -{{ $medioAnchoResultadoExterno }}pt; white-space: pre-wrap; overflow-wrap: break-word;">{!! $fragmentoObservacionExterna === '' ? '&nbsp;' : nl2br(e($fragmentoObservacionExterna)) !!}</div>
            </div>
        </td>
    </tr>
@endforeach
