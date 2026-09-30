<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte de Resultados</title>
<style>

@page {
    margin: 210px 50px 50px 50px;
}

/* ================= BASE ================= */

body {
    font-family: 'DejaVu Sans', sans-serif;
    font-size: 10px; /* antes 11.5px */
    color: #333;
}

/* ================= AREA TABLE ================= */

.area-table {
    width: 100%;
    border-collapse: collapse;
    border: none;
    margin-bottom: 0;
}

.results-table tfoot {
    display: table-footer-group;
}

.firma-cell {
    padding-top: -20px;
    padding-bottom: 5px;
    border: none;
}

/* ================= FIRMAS ================= */

.firma-wrapper {
    position: relative;
    width: 170px;
    height: 120px;
    margin: 0 auto;
    display: inline-block;
}

.firma-img-sello {
    position: absolute;
    top: 25px;
    left: 0;
    width: 100%;
    height: 90px;
    object-fit: contain;
    opacity: 1;
}

.firma-img-rubrica {
    position: absolute;
    top: -35px;
    left: 0;
    width: 100%;
    height: 105px;
    object-fit: contain;
    z-index: 10;
}

/* ================= TITULO AREA ================= */

.area-title-row {
    page-break-after: avoid;
    padding-top: 3px;
    padding-bottom: 5px;
    border: none;
}

.area-title {
    background-color: #f4f4f4;
    border-left: 5px solid #64ABC6;
    color: #000;
    padding: 6px 10px;
    font-weight: bold;
    font-size: 10.5px; /* antes 12px */
    text-transform: uppercase;
}

/* ================= WATERMARK ================= */

.watermark {
    position: fixed;
    top: 40%;
    left: 0;
    width: 100%;
    text-align: center;
    transform: rotate(-30deg);
    font-size: 64px;
    font-weight: bold;
    letter-spacing: 6px;
    color: #64ABC6;
    opacity: 0.09;
    z-index: -1000;
}
.watermark span { font-size: 12px; letter-spacing: 3px; }

/* ================= TABLA RESULTADOS ================= */

.results-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 8px;
    page-break-inside: auto;
}

.results-table thead {
    display: table-header-group;
}

.results-table thead th {
    background-color: #64ABC6;
    color: black;
    border: none;
    text-align: center;
    padding: 3px;
    font-size: 9.5px;
}

.results-table tr {
    page-break-after: auto;
}

.result-row{
    page-break-inside: avoid;
}

.results-table td {
    padding: 4px;
    text-align: left;
    vertical-align: top;
}

.result-row {
    border-bottom: 1px solid #eee;
}

.result-prueba-name {
    font-style: italic;
    text-transform: uppercase;
    padding-left: 15px;
    font-size: 9.5px;
}

.result-value {
    text-align: center;
    font-weight: bold;
    font-size: 9.5px;
}

.muestra-text {
    font-weight: bold;
    font-size: 8.5px; /* antes 10px */
    margin-bottom: 4px;
}

/* ================= EXAMEN TITLE ================= */

.examen-title-row td {
    font-weight: bold;
    font-size: 9.5px;
    background-color: #f2f2f2;
    border-bottom: 1px solid #ccc;
    padding: 6px 5px;
}
/* ================= EXAMEN separador si no hay titulo ================= */

.examen-separatora td{
    background-color: #f2f2f2;
    border-bottom: 1px solid #ccc;
    padding: 2px 5px; /* más delgado */
}
/* ================= MATRIX ================= */

.matrix-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
    page-break-inside: auto;
}

.matrix-table th,
.matrix-table td {
    border: 1px solid #ccc;
    text-align: center;
    padding: 4px;
    vertical-align: middle;
}

.matrix-table thead th {
    font-weight: bold;
    text-transform: uppercase;
    background-color: #f2f2f2;
    border-bottom: 2px solid #000;
    font-size: 9px;
}

.matrix-table tbody th {
    font-weight: bold;
    background-color: #f2f2f2;
    font-size: 9px;
}

.matrix-table td {
    font-size: 9px; /* antes 11px */
    font-weight: bold;
}

.matrix-table thead th:first-child {
    background-color: transparent;
    border: none;
}

/* ================= HEADER ================= */

.header-clean {
    position: fixed;
    top: -190px;
    left: 0;
    right: 0;
    height: 190px;
    background: transparent;
}

.header-bar {
    display: table;
    width: 100%;
    background-color: #f5f5f5;
    color: black;
    padding: 10px 18px;
    border-radius: 18px;
}

.header-bar-left {
    display: table-cell;
    vertical-align: middle;
}

.header-bar-left strong {
    font-size: 12px; /* antes 14px */
    letter-spacing: 0.5px;
}

.header-bar-sub {
    font-size: 8px; /* antes 9px */
    opacity: 0.9;
    margin-top: 2px;
}

.header-bar-right {
    display: table-cell;
    text-align: right;
    vertical-align: middle;
}

.header-bar-right img {
    max-height: 66px;
}

/* ================= PATIENT CARD ================= */

.patient-card {
    margin: 4px 18px 0;
    background: #f8f9fb;
    border-left: 4px solid #64ABC6;
    padding: 6px 10px;
    border-radius: 10px;
}

.patient-card table {
    width: 100%;
    font-size: 9px; /* antes 10px */
}

.patient-card td {
    padding: 3px 5px;
}

.patient-card span {
    display: block;
    font-size: 7px; /* antes 8px */
    color: #777;
    text-transform: uppercase;
    margin-bottom: 1px;
}

/* ================= OBSERVACIONES ================= */

.observaciones-box {
    background-color: #f9f9f9;
    border: 1px solid #eee;
    padding: 8px;
    font-size: 8px; /* antes 9px */
    margin-top: -20px;
    margin-bottom: 6px;
    page-break-inside: avoid;
}

/* ================= FOOTER ================= */

footer {
    position: fixed;
    bottom: -30px;
    left: 0;
    right: 0;
    height: 30px;
    text-align: center;
    font-size: 7px; /* antes 8px */
    color: #888;
    border-top: 1px solid #eee;
    padding-top: 4px;
}

footer .page-number:before {
    content: "Página " counter(page);
}

/* ================= MISC ================= */

.seccion-laboratorista {
    margin-bottom: 6px;
}

.firma-container {
    margin-top: 8px;
    page-break-inside: avoid;
    width: 100%;
}

.firma-table {
    width: 100%;
}

.salto-pagina {
    page-break-after: always;
}

.fuera-de-rango {
    color: #B9182A !important;
    font-weight: bold;
}

</style>
</head>

<body>
    <div class="watermark">{{ config('laboratorio.nombre') }}<br><span>SAN VICENTE · RESULTADOS DE LABORATORIO</span></div>
        @php
function normalizarSimbolosClinicos($texto)
{
    //  Lógica original (desactivada temporalmente para pruebas Unicode)
    /*
    return str_replace(
        ['≥','≤'],
        ['>=','<='],
        $texto
    );
    */

//MODO TEST: no modificar símbolos
    return $texto;
}

        $formatearNumerosReferencia = fn ($texto) => \App\Support\NumeroLaboratorio::imprimirTexto($texto);

    @endphp

    @php
   $esFueraDeRango = function ($resultado, $referencia, $alertar = false) {

    // PRIORIDAD ABSOLUTA: Si el checkbox de "Colorear" está marcado, siempre es true
    if ($alertar) {
        return true;
    }

    $referencia = normalizarSimbolosClinicos($referencia);

    if (empty($resultado) || empty($referencia)) {
        return false;
    }

    $resultadoTexto = strtoupper(strip_tags($resultado));
    $referenciaTexto = strtoupper(strip_tags($referencia));

    // Si el resultado ya dice POSITIVO / REACTIVO → rojo directo
    if (str_contains($resultadoTexto, 'POSITIVO') || str_contains($resultadoTexto, 'REACTIVO')) {
        return true;
    }

    // =====================================================
    // ⏱ DETECTAR MINUTOS Y SEGUNDOS
    // =====================================================
    $resultadoTextoOriginal = strtoupper(strip_tags($resultado));

    if (str_contains($resultadoTextoOriginal, 'MIN') || str_contains($resultadoTextoOriginal, 'SEG')) {
        $min = 0; $seg = 0;
        if (preg_match('/([0-9]+)\s*(MIN|MINUTO|MINUTOS)/', $resultadoTextoOriginal, $m)) { $min = (int) $m[1]; }
        if (preg_match('/([0-9]+)\s*(SEG|SEGUNDO|SEGUNDOS)/', $resultadoTextoOriginal, $s)) { $seg = (int) $s[1]; }
        $valor = $min + ($seg / 60);
    } else {
        // Lógica normal numérica
        $valorStr = preg_replace('/[^0-9\.,\-]/', '', \App\Support\NumeroLaboratorio::normalizar($resultado));
        if (!is_numeric($valorStr)) { return false; }
        $valor = (float) $valorStr;
    }

    /*
    =====================================================
    INTERPRETACIÓN CUALITATIVA (CLÍNICA REAL)
    =====================================================
    */
    if (str_contains($referenciaTexto, 'POSITIVO') || str_contains($referenciaTexto, 'NEGATIVO')) {
        if (preg_match('/POSITIVO\s*>=\s*([0-9\.]+)/', $referenciaTexto, $m)) { if ($valor >= (float)$m[1]) return true; }
        if (preg_match('/POSITIVO\s*>\s*([0-9\.]+)/', $referenciaTexto, $m)) { if ($valor > (float)$m[1]) return true; }
        if (preg_match('/POSITIVO\s*<=\s*([0-9\.]+)/', $referenciaTexto, $m)) { if ($valor <= (float)$m[1]) return true; }
        if (preg_match('/POSITIVO\s*<\s*([0-9\.]+)/', $referenciaTexto, $m)) { if ($valor < (float)$m[1]) return true; }
        return false;
    }

    /*
    =====================================================
    📊 RANGOS NUMÉRICOS NORMALES
    =====================================================
    */
    
    // 🚀 NUEVA REGLA ANTI-PÁRRAFOS:
    // Si la referencia tiene más de 25 letras (es un párrafo explicativo largo), 
    // no adivinamos nada matemáticamente. Solo respetará el checkbox "Colorear".
    $soloLetras = preg_replace('/[^A-Z]/', '', $referenciaTexto);
    if (strlen($soloLetras) > 25) {
        return false; 
    }

    preg_match_all('/-?[0-9]+(\.[0-9]+)?/', str_replace(',', '.', $referencia), $matches);
    $nums = $matches[0];

    if (count($nums) > 2) return false;

    if (count($nums) === 2) {
        $min = (float)$nums[0];
        $max = (float)$nums[1];
        return ($valor < $min || $valor > $max);
    }

    if (count($nums) === 1) {
        $limite = (float)$nums[0];
        if (str_contains($referencia, '<')) return $valor >= $limite;
        if (str_contains($referencia, '>')) return $valor <= $limite;
    }

    return false;
};

//////////////////////////
function agregarUnidadesPorLinea($referencia, $unidad)
{
    if (empty($unidad)) return $referencia;

    // Convertimos <br> a salto real temporal
    $referencia = str_replace('<br>', "\n", $referencia);

    $lineas = preg_split('/\r\n|\r|\n/', $referencia);

    $lineas = array_map(function ($linea) use ($unidad) {

        $linea = trim($linea);
        if ($linea === '') return $linea;

        if (!str_contains(strtoupper($linea), strtoupper($unidad))) {
            $linea .= ' ' . $unidad;
        }

        // 🔥 Escapamos HTML aquí (esto protege < y >)
        return e($linea);

    }, $lineas);

    // Volvemos a unir con <br>
    return implode('<br>', $lineas);
}

    @endphp



    <header class="header-clean">

            <div class="header-bar">
                <div class="header-bar-left">
                    @include('pdf.contacto')
                </div>

                <div class="header-bar-right">
                    @if (!empty($logo_b64))
                        <img src="{{ $logo_b64 }}" alt="Logo" style="max-height: 66px; max-width: 100%;">
                    @endif
                </div>
            </div>

            <div class="patient-card">
                {{-- PARTE 1: DATOS DEL PACIENTE (Tabla Superior) --}}
                <table style="width: 100%;">
                    <tr>
                        <td>
                            <span>Paciente</span>
                            {{ $orden->cliente->nombre }} {{ $orden->cliente->apellido }}
                        </td>

                        <td>
                            {{-- CASO 1: Si tiene Fecha de Nacimiento O Edad manual --}}
                            @if(!empty($orden->cliente->fecha_nacimiento) || !empty($orden->cliente->edad))
                                <span>Edad</span>
                                {{-- Llamamos a la función inteligente que dice "meses", "días" o "años" --}}
                                {{ $orden->cliente->edad_legible }}
                            
                            {{-- CASO 2: Si no tiene ni fecha ni edad, mostramos Grupo Etario --}}
                            @else
                                <span>Grupo etario</span>
                                {{ $orden->cliente->getGrupoEtario()->nombre ?? 'No especificado' }}
                            @endif
                        </td>

                        <td>
                            <span>Género</span>
                            {{ $orden->cliente->genero ?? 'No especificado' }}
                        </td>

                        <td>
                            <span>Ingreso</span>
                            {{ $orden->created_at->format('d/m/Y H:i') }}
                        </td>

                        <td>
                            <span>Impresión</span>
                            {{ now()->format('d/m/Y H:i') }}
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <span>No. expediente</span>
                            {{ $orden->cliente->NumeroExp ?? 'N/A' }}
                        </td>
                        <td colspan="3">
                            <span>DUI</span>
                            {{ $orden->cliente->dui ?? 'N/A' }}
                        </td>
                    </tr>
                </table>

                {{-- PARTE 2: DATOS DEL MÉDICO (Inferior) --}}
                @if($orden->medico)
                    <div style="border-top: 1px solid #e0e0e0; margin: 4px 0 4px 0;"></div>

                    <div style="font-size: 9px; padding-left: 6px;">
                        {{-- Usamos 'display: inline' para forzar que estén en el mismo renglón --}}
                        <span
                            style="display: inline; color: #777; text-transform: uppercase; font-size: 8px; margin-right: 5px;">
                            MÉDICO REFERENTE:
                        </span>

                        <strong style="color: #333; text-transform: uppercase;">
                            {{-- Agregamos lógica para poner "DR." si no viene incluido --}}
                            {{ str_starts_with(strtoupper($orden->medico->nombre), 'DR') ? $orden->medico->nombre : 'DR. ' . $orden->medico->nombre }}
                        </strong>
                    </div>
                @endif
            </div>

        </header>


        <footer>
            <div style="margin-top: 5px;">
                <div style="text-align: right;" class="page-number"></div>
            </div>
        </footer>



        {{-- El contenido del cuerpo empieza aquí. Gracias al margin-top del @page, no se solapará con el header --}}

        @if (!empty($orden->observaciones))
            <div class="observaciones-box">
                <strong style="color: #090B3B;">Observaciones Generales:</strong>
                <p style="margin: 0; padding: 0; color: #333;">{!! nl2br(e($orden->observaciones)) !!}</p>
            </div>
        @endif

        {{-- BUCLE PRINCIPAL POR LABORATORISTA --}}
        @foreach($grupos_por_usuario as $grupo)

            {{-- BUCLE POR TIPO DE EXAMEN (ÁREA) --}}
            @foreach($grupo['datos'] as $tipoExamenNombre => $examenes)

                {{-- Creamos una tabla MAESTRA por cada Área --}}
                <div class="area-table">

                        {{-- A. Título Visual del Área --}}
                        <div class="area-title-row">
                                <div class="area-title">
                                    REPORTE DE: {{ $tipoExamenNombre }}
                                    @if(!empty($orden->observaciones_por_area[$tipoExamenNombre] ?? null))
                                        <div class="observaciones-box" style="margin-top:1px;">
                                            <strong>Observación del Área: </strong>
                                            {{ $orden->observaciones_por_area[$tipoExamenNombre] }}
                                        </div>
                                    @endif
                                </div>
                            </div>

                        {{-- ===== DETECTAR SI EL ÁREA COMPLETA TIENE REFERENCIAS ===== --}}
                        @php
                            $tieneReferenciasArea = false;

                            foreach ($examenes as $ex) {
                                if (!empty($ex['pruebas_unitarias'])) {
                                    foreach ($ex['pruebas_unitarias'] as $p) {
                                        $ref = trim(strip_tags($p['referencia'] ?? ''));
                                        $uni = trim($p['unidades'] ?? '');
                                        if (($ref !== '' && $ref !== 'N/A') || $uni !== '') {
                                            $tieneReferenciasArea = true;
                                            break 2;
                                        }
                                    }
                                }
                            }
                        @endphp


                        @php
                                    // 1. VALIDACIÓN EXACTA PARA OCULTAR TÍTULOS DE EXAMEN
                                    $areaNormalizada = mb_strtoupper(trim($tipoExamenNombre), 'UTF-8');
                                    
                                    $areasSinTitulo = [
                                        'ELECTROLITOS', 'ENDOCRINOLOGÍA', 'INMUNOLOGÍA', 
                                        'MARCADORES TUMORALES', 'QUÍMICA SANGUÍNEA', 
                                        'QUÍMICA URINARIA', 'CARDIOVASCULAR', 'MINERALES'
                                    ];
                                    
                                    // Si el área NO está en la lista de arriba, SÍ mostramos el título
                                    $mostrarTituloExamen = !in_array($areaNormalizada, $areasSinTitulo);

                                    // 2. AGRUPAMOS LOS EXÁMENES EN "CUBETAS"
                                    $gigantes = [];
                                    $conRango = [];
                                    $sinRango = [];

                                    foreach ($examenes as $examen) {
                                        // A. Detectar Gigantes
                                        $nombreExamenActual = mb_strtolower(trim($examen['nombre']), 'UTF-8');
                                        $esGigante = (str_contains($nombreExamenActual, 'general de orina') || str_contains($nombreExamenActual, 'hemograma'));

                                        if ($esGigante) {
                                            $gigantes[] = $examen;
                                            continue; // Si es gigante, no lo metemos en los grupos cortos
                                        }

                                        // B. Detectar si tiene rangos de referencia
                                        $tieneRef = false;
                                        if (!empty($examen['pruebas_unitarias'])) {
                                            foreach ($examen['pruebas_unitarias'] as $p) {
                                                $ref = trim(strip_tags($p['referencia'] ?? ''));
                                                $uni = trim($p['unidades'] ?? '');
                                                if (($ref !== '' && $ref !== 'N/A') || $uni !== '') {
                                                    $tieneRef = true;
                                                    break;
                                                }
                                            }
                                        }

                                        // C. Meter en la cubeta correspondiente
                                        if ($tieneRef) {
                                            $conRango[] = $examen;
                                        } else {
                                            $sinRango[] = $examen;
                                        }
                                    }

                                    // 3. PREPARAMOS LAS TABLAS A IMPRIMIR
                                    $bloques = [];
                                    
                                    // Primero metemos los gigantes (cada uno en su propia tabla/página)
                                    foreach ($gigantes as $g) {
                                        $tieneRef = false;
                                        if (!empty($g['pruebas_unitarias'])) {
                                            foreach ($g['pruebas_unitarias'] as $p) {
                                                $ref = trim(strip_tags($p['referencia'] ?? ''));
                                                $uni = trim($p['unidades'] ?? '');
                                                if (($ref !== '' && $ref !== 'N/A') || $uni !== '') { $tieneRef = true; break; }
                                            }
                                        }
                                        $bloques[] = ['tipo' => 'gigante', 'tiene_referencia' => $tieneRef, 'examenes' => [$g]];
                                    }

                                    // Luego metemos la tabla con TODOS los que tienen rango (3 columnas)
                                    if (count($conRango) > 0) {
                                        $bloques[] = ['tipo' => 'con_rango', 'tiene_referencia' => true, 'examenes' => $conRango];
                                    }

                                    // Finalmente metemos la tabla con TODOS los que NO tienen rango (2 columnas)
                                    if (count($sinRango) > 0) {
                                        $bloques[] = ['tipo' => 'sin_rango', 'tiene_referencia' => false, 'examenes' => $sinRango];
                                    }
                                @endphp

                                {{-- AHORA IMPRIMIMOS CADA BLOQUE (TABLA) --}}
                                @foreach($bloques as $indexBloque => $bloque)
                                    @php
                                        $esTablaConRef = $bloque['tiene_referencia'];
                                        $listaExamenes = $bloque['examenes'];
                                        $esGigante = ($bloque['tipo'] === 'gigante');

                                        // Controlamos los saltos de página
                                        $saltoPaginaStr = '';
                                        if ($indexBloque > 0) {
                                            $bloqueAnterior = $bloques[$indexBloque - 1];
                                            if ($esGigante || $bloqueAnterior['tipo'] === 'gigante') {
                                                $saltoPaginaStr = 'page-break-before: always; margin-top: 0px;';
                                            } else {
                                                $saltoPaginaStr = 'margin-top: 15px;'; // Margen entre la tabla de 3 cols y la de 2 cols
                                            }
                                        }

                                        // Alineación de la cabecera
                                        $cabeceraAlineacion = 'center'; 
                                        $cabeceraPadding = '';
                                        if ($esTablaConRef) {
                                            foreach ($listaExamenes as $ex_eval) {
                                                if (!empty($ex_eval['pruebas_unitarias'])) {
                                                    foreach ($ex_eval['pruebas_unitarias'] as $p_eval) {
                                                        // 🚀 LA NUEVA REGLA PARA LA CABECERA: CENTRADO A MENOS QUE SEA BETA HCG
                                                        $nombrePruebaTest = mb_strtoupper(trim($p_eval['nombre'] ?? ''), 'UTF-8');
                                                        if (str_contains($nombrePruebaTest, 'BETA HCG CUANTITATIVO')) {
                                                            $cabeceraAlineacion = 'left'; 
                                                            $cabeceraPadding = 'padding-left: 10px;';
                                                            break 2;
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    @endphp

                                    <table class="results-table" style="width: 100%; {{ $saltoPaginaStr }}">
                                        <thead>
                                            <tr>
                                                <th style="width: {{ $esTablaConRef ? '40%' : '50%' }}">PRUEBA</th>
                                                <th style="width: {{ $esTablaConRef ? '25%' : '50%' }}">RESULTADO</th>
                                                @if($esTablaConRef)
                                                    <th style="width: 35%; text-align: {{ $cabeceraAlineacion }}; {{ $cabeceraPadding }}">RANGO DE REFERENCIA</th>
                                                @endif
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($listaExamenes as $idxExamen => $examen)
                                                @php
                                                    $pruebasCollection = collect($examen['pruebas_unitarias'] ?? []);
                                                    $agrupadas = $pruebasCollection->groupBy(fn($item) => $item['tipo_prueba'] ?? '');
                                                    $sinGrupo = $agrupadas->pull('') ?? collect();
                                                    $ordenMedicoVisual = ['LINEA ROJA'=> 1, 'LINEA BLANCA'=> 2, 'LINEA PLAQUETARIA' => 3];
                                                    $conGrupo = $agrupadas->sortBy(function ($items, $key) use ($ordenMedicoVisual) { return $ordenMedicoVisual[strtoupper($key)] ?? 999; });
                                                @endphp

                                                {{-- Separador normal para exámenes en la misma tabla --}}
                                                @if($idxExamen > 0 && $mostrarTituloExamen)
                                                    <tr class="examen-separatora">
                                                        <td style="border:none;height:4px;"></td>
                                                        <td style="border:none;height:4px;"></td>
                                                        @if($esTablaConRef)<td style="border:none;height:4px;"></td>@endif
                                                    </tr>
                                                @endif

                                                @if($mostrarTituloExamen)
                                                    <tr class="examen-title-row">
                                                        <td>EXAMEN: {{ $examen['nombre'] }}</td>
                                                        <td></td>
                                                        @if($esTablaConRef) <td></td> @endif
                                                    </tr>
                                                @endif

                                                @foreach($sinGrupo as $pruebaData)
                                                    <tr class="result-row">
                                                        <td><div class="result-prueba-name">{{ $pruebaData['nombre'] }}</div></td>
                                                        <td>
                                                            <div class="result-value @if($esFueraDeRango($pruebaData['resultado'], $pruebaData['referencia'], $pruebaData['alertar'] ?? false)) fuera-de-rango @endif">
                                                                {{ $formatearNumerosReferencia($pruebaData['resultado']) }}
                                                            </div>
                                                        </td>
                                                        @if($esTablaConRef)
                                                            @php
                                                                $refFinal = ''; $alineacion = 'center'; $padding = '';
                                                                $refTemp = normalizarSimbolosClinicos($pruebaData['referencia'] ?? '');
                                                                $refTemp = $formatearNumerosReferencia($refTemp);
                                                                $refFinal = agregarUnidadesPorLinea($refTemp, $pruebaData['unidades'] ?? '');
                                                                
                                                                // 🚀 LA NUEVA REGLA PARA EL RESULTADO: CENTRADO A MENOS QUE SEA BETA HCG
                                                                $nombrePruebaResult = mb_strtoupper(trim($pruebaData['nombre'] ?? ''), 'UTF-8');
                                                                if(str_contains($nombrePruebaResult, 'BETA HCG CUANTITATIVO')) { 
                                                                    $alineacion = 'left'; 
                                                                    $padding = 'padding-left: 10px;'; 
                                                                }
                                                            @endphp
                                                            <td style="text-align: {{ $alineacion }}; {{ $padding }} font-size: 8.5px;">{!! $refFinal !!}</td>
                                                        @endif
                                                    </tr>
                                                @endforeach

                                                @foreach($conGrupo as $nombreGrupo => $items)
                                                    <tr class="group-title-row"><td colspan="{{ $esTablaConRef ? 3 : 2 }}">{{ $nombreGrupo }}</td></tr>
                                                    @foreach($items as $pruebaData)
                                                        <tr class="result-row">
                                                            <td><div class="result-prueba-name">{{ $pruebaData['nombre'] }}</div></td>
                                                            <td>
                                                                <div class="result-value @if($esFueraDeRango($pruebaData['resultado'], $pruebaData['referencia'], $pruebaData['alertar'] ?? false)) fuera-de-rango @endif">
                                                                    {{ $formatearNumerosReferencia($pruebaData['resultado']) }}
                                                                </div>
                                                            </td>
                                                            @if($esTablaConRef)
                                                                @php
                                                                    $refFinal = ''; $alineacion = 'center'; $padding = '';
                                                                    $refTemp = normalizarSimbolosClinicos($pruebaData['referencia'] ?? '');
                                                                    $refTemp = $formatearNumerosReferencia($refTemp);
                                                                    $refFinal = agregarUnidadesPorLinea($refTemp, $pruebaData['unidades'] ?? '');
                                                                    
                                                                    // 🚀 LA NUEVA REGLA PARA EL RESULTADO (EN GRUPO): CENTRADO A MENOS QUE SEA BETA HCG
                                                                    $nombrePruebaResult = mb_strtoupper(trim($pruebaData['nombre'] ?? ''), 'UTF-8');
                                                                    if(str_contains($nombrePruebaResult, 'BETA HCG CUANTITATIVO')) { 
                                                                        $alineacion = 'left'; 
                                                                        $padding = 'padding-left: 10px;'; 
                                                                    }
                                                                @endphp
                                                                <td style="text-align: {{ $alineacion }}; {{ $padding }} font-size: 8.5px;">{!! $refFinal !!}</td>
                                                            @endif
                                                        </tr>
                                                    @endforeach
                                                @endforeach

                                            @if (!empty($examen['matrices']))
                                                @foreach ($examen['matrices'] as $matriz)
                                                    <tr>
                                                        <td colspan="{{ $tieneReferenciasArea ? 3 : 2 }}" style="padding: 5px 0;">
                                                            <table class="matrix-table">
                                                                <thead>
                                                                    <tr>
                                                                        <th></th>
                                                                        @foreach ($matriz['columnas'] as $columna)
                                                                            <th>{{ $columna }}</th>
                                                                        @endforeach
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @foreach ($matriz['filas'] as $fila)
                                                                        <tr>
                                                                            <th>{{ $fila }}</th>
                                                                            @foreach ($matriz['columnas'] as $columna)
                                                                                @php
                                                                                    $celda = $matriz['data'][$fila][$columna] ?? null;
                                                                                    $fueraRangoMatriz = ($celda && isset($celda['resultado'])) ? $esFueraDeRango($celda['resultado'], $celda['referencia'] ?? '') : false;
                                                                                @endphp
                                                                                <td class="@if($fueraRangoMatriz) fuera-de-rango @endif">{{ $formatearNumerosReferencia($celda['resultado'] ?? '-') }}</td>
                                                                            @endforeach
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @endif

                                            @endforeach
                                        </tbody>
                                        @include('pdf.firmas')
                                    </table>
                                @endforeach
                </div>

                {{-- SALTO DE PÁGINA AL FINAL DEL ÁREA --}}
                @if(!($loop->parent->last && $loop->last))
                    <div class="salto-pagina"></div>
                @endif

            @endforeach {{-- Fin Foreach Area --}}

        @endforeach {{-- Fin Foreach Laboratorista --}}


</body>

</html>
