<?php

namespace App\Services;

use App\Models\DetalleOrden;
use Log;

class ZebraLabelService
{
    public function generarZplMultiple($detalles): string
    {
        Log::info('Generando etiquetas ZPL agrupadas por color.');

        // Agrupar por color (recipiente)
        $agrupados = $detalles->groupBy(function ($detalle) {
            $recipiente = $detalle->status ?? 'Sin color';
            if (enum_exists(\App\Enums\RecipienteEnum::class) && \App\Enums\RecipienteEnum::tryFrom($recipiente)) {
                $recipiente = \App\Enums\RecipienteEnum::tryFrom($recipiente)?->getTitle();
            }
            return $recipiente;
        });

        Log::info('Agrupados por color:', $agrupados->toArray());

        // Generar etiquetas por cada color y sus detalles
        return $agrupados->map(function ($items, $color) {
            return $this->generarZplPorColor($color, $items);
        })->implode("\n\n");
    }

    public function generarZpl(DetalleOrden $detalle): string
    {
        $labNombre = str_replace(['^', '~'], '', config('laboratorio.nombre'));
        date_default_timezone_set('America/El_Salvador');
        
        $examen = strtoupper(substr($detalle->nombre_examen, 0, 30));
        $paciente = strtoupper(trim(
    ($detalle->orden->cliente->nombre ?? '') . ' ' .
    ($detalle->orden->cliente->apellido ?? '')
));
        // Fecha actual en formato dd/mm/yyyy
        $fecha = date('d/m/Y');
        $hora = date('h:i A');
        // Usar el título del enum si es válido
        $recipiente = $detalle->status;
        if (enum_exists(\App\Enums\RecipienteEnum::class) && \App\Enums\RecipienteEnum::tryFrom($detalle->status)) {
            $recipiente = \App\Enums\RecipienteEnum::tryFrom($detalle->status)?->getTitle();
        }
        $ordenId = $detalle->orden->id;

      return "
^XA
^PW406
^LL203
^CI28

^FO0,15^FB406,1,0,C,0^A0N,26,26^FD{$labNombre}^FS
^FO10,45^GB386,1,1^FS

^FO15,55^FB376,2,0,L,0^A0N,18,18^FDPaciente: {$paciente}^FS

^FO15,90^A0N,18,18^FDRecip.: {$recipiente}^FS
^FO270,90^A0N,18,18^FD{$fecha}^FS

^FO10,115^GB386,1,1^FS

^FO0,130^FB406,2,0,C,0^A0N,22,22^FD{$examen}^FS

^FO15,180^A0N,15,15^FDHora: {$hora}^FS
^FO280,180^A0N,15,15^FDOrd: #{$ordenId}^FS

^XZ
\n\n";
    }

    private function generarZplPorColor($color, $items): string
    {
        $labNombre = str_replace(['^', '~'], '', config('laboratorio.nombre'));
        date_default_timezone_set('America/El_Salvador');
        // === Paciente (primer nombre + primer apellido) ===
        $nombre = $items->first()->orden->cliente->nombre ?? 'PACIENTE';
        $apellido = $items->first()->orden->cliente->apellido ?? '';

        $tokensNombre = preg_split('/\s+/', trim($nombre));
        $primerNombre = $tokensNombre[0] ?? '';

        $tokensApellido = preg_split('/\s+/', trim($apellido));
        $primerApellido = $tokensApellido[0] ?? '';

       $paciente = strtoupper(trim($nombre . ' ' . $apellido));

        // Recipiente (color)
        $recipiente = $color;
        if (enum_exists(\App\Enums\RecipienteEnum::class) && \App\Enums\RecipienteEnum::tryFrom($color)) {
            $recipiente = \App\Enums\RecipienteEnum::tryFrom($color)?->getTitle();
        }

        Log::info("Generando etiquetas ZPL para recipiente: {$recipiente}, con " . count($items) . " exámenes.");
        // Fecha actual en formato dd/mm/yyyy
        $fecha = date('d/m/Y');
        $hora = date('h:i A');

        $examenesUnicos = collect();
        $examenesSecereciones = collect();
        foreach ($items as $detalle) {
           
            if($detalle->status == "cultivo_secreciones"){
                $examenesSecereciones->push($detalle->examen->nombre);
            }else{
                if ($detalle->examen && $detalle->examen->tipoExamen) {
                $examenesUnicos->push($detalle->examen->tipoExamen->nombre);
            }
            }
            
        }
        $examenesUnicos = $examenesUnicos->unique()->values();

        // === Agrupar exámenes en bloques de 4 ===
        $bloques = $examenesUnicos->chunk(4);

        $zpl = '';

        foreach ($bloques as $bloque) {
    $examenLines = '';
    $startY = 135;
    $lineHeight = 25;

    foreach ($bloque->values() as $index => $examen) {
        $examenTexto = strtoupper(substr($examen, 0, 20));
        $col = $index % 2; // 0 = primera columna, 1 = segunda
        $row = intdiv($index, 2); // fila dentro del bloque
        $posX = $col === 0 ? 30 : 200;
        $posY = $startY + ($lineHeight * $row);

        $examenLines .= "^FO{$posX},{$posY}^ADN,8,4^FD-{$examenTexto}^FS\n";
    }
$ordenId = $items->first()->orden->id;
    // Plantilla fija de la etiqueta
// Asegúrate de tener el ID disponible antes: $ordenId = $items->first()->orden->id;

$zpl .= "^XA
^PW406
^LL203
^CI28

^FO0,15^FB406,1,0,C,0^A0N,24,24^FD{$labNombre}^FS
^FO10,42^GB386,1,1^FS

^FO15,50^FB376,2,0,L,0^A0N,18,18^FDPaciente: {$paciente}^FS

^FO15,90^A0N,18,18^FDRecip.: {$recipiente}^FS
^FO270,90^A0N,18,18^FD{$fecha}^FS

^FO10,115^GB386,1,1^FS
{$examenLines}

^FO15,180^A0N,15,15^FDHora: {$hora}^FS
^FO280,180^A0N,15,15^FDOrd: #{$ordenId}^FS
^XZ\n\n";
}

    foreach ($examenesSecereciones->values() as $examen) {
    $examenTexto = strtoupper(substr($examen, 0, 30));
    $posX = 30;
    $posY = 135;

  // Dentro del foreach ($examenesSecereciones...)

$zpl .= "^XA
^PW406
^LL203
^CI28

^FO0,15^FB406,1,0,C,0^A0N,24,24^FD{$labNombre}^FS
^FO10,42^GB386,1,1^FS

^FO15,50^FB376,2,0,L,0^A0N,18,18^FDPaciente: {$paciente}^FS

^FO15,90^A0N,18,18^FDRecip.: {$recipiente}^FS
^FO270,90^A0N,18,18^FD{$fecha}^FS

^FO10,115^GB386,1,1^FS

^FO0,130^FB406,2,0,C,0^A0N,22,22^FD{$examenTexto}^FS

^FO15,180^A0N,15,15^FDHora: {$hora}^FS
^FO280,180^A0N,15,15^FDOrd: #{$ordenId}^FS
^XZ\n\n";
}

        return $zpl;
    }
}
