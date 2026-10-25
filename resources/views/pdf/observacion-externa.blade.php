@php
    $textoObservacionExterna = (string) ($pruebaData['observaciones'] ?? '');
    $fragmentosObservacionExterna = [];

    if (trim($textoObservacionExterna) !== '') {
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
        <td colspan="{{ $esTablaConRef ? 2 : 1 }}" style="border: 0; padding: {{ $indiceObservacionExterna === 0 ? '3px' : '0' }} 6px 2px; font-size: 8.5px; line-height: 1.25; text-align: left;">
            @if ($indiceObservacionExterna === 0)
                <div style="font-weight: bold; margin-bottom: 2px;">Observaciones:</div>
            @endif
            <div style="white-space: pre-wrap; overflow-wrap: break-word;">{!! $fragmentoObservacionExterna === '' ? '&nbsp;' : nl2br(e($fragmentoObservacionExterna)) !!}</div>
        </td>
    </tr>
@endforeach
