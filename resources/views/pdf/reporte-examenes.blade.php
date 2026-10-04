@php
    $diseno = isset($paginas) ? compact('paginas', 'tamano', 'interlineado') : app(\App\Services\CatalogoExamenesPdf::class)->organizar($areas, $perfiles);
    extract($diseno);
    $mostrarPrecios = $mostrarPrecios ?? false;
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Solicitud de exámenes · ONCOSAVI</title>
    <style>
        @page { margin: 18pt; }
        body { margin: 0; font-family: 'DejaVu Sans', sans-serif; color: #17202a; }
        .sheet { position: relative; height: 805pt; width: 559pt; page-break-inside: avoid; }
        .next-page { page-break-before: always; }
        .banner { position: absolute; top: 0; left: 0; width: 559pt; height: 61pt; }
        .logo { position: absolute; top: 4pt; right: 5pt; width: 54pt; height: 54pt; }
        .brand { position: absolute; top: 25pt; left: 25pt; width: 440pt; text-align: center; color: #fff; font-size: 20pt; font-weight: bold; font-style: italic; }
        .subtitle { position: absolute; top: 7pt; left: 25pt; width: 440pt; text-align: center; color: #fff; font-size: 13pt; font-style: italic; }
        .patient { position: absolute; top: 70pt; width: 559pt; font-size: 8pt; border-collapse: collapse; }
        .patient td { padding: 0 8pt 0 0; vertical-align: top; }
        .write-line { display: inline-block; height: 10pt; border-bottom: 0.6pt solid #82909b; }
        .metadata { position: absolute; top: 91pt; width: 559pt; font-size: 7pt; border-collapse: collapse; }
        .metadata td { padding: 0 9pt 0 0; vertical-align: top; }
        .sex-box { display: inline-block; border: 0.6pt solid #82909b; width: 9pt; height: 9pt; vertical-align: middle; }
        .signature-line { text-align: center; font-size: 6.5pt; }
        .instruction { position: absolute; top: 111pt; font-size: 5.5pt; color: #526170; }
        .crab-watermark { position: absolute; top: 290pt; left: 60pt; width: 450pt; height: 380pt; z-index: -1; }
        .column { position: absolute; top: 124pt; width: 178.33pt; }
        .section { position: absolute; width: 178.33pt; }
        .section-heading { position: absolute; top: 0; width: 178.33pt; background: #203552; color: #fff; }
        .heading-name { position: relative; margin: 3pt 4pt 0; font-size: {{ $tamano }}pt; line-height: 1; font-weight: bold; font-style: italic; }
        .profile .heading-name { margin-left: 15pt; }
        .heading-price { position: absolute; right: 2pt; top: 3pt; font-size: {{ $tamano }}pt; line-height: {{ $interlineado }}pt; font-weight: bold; }
        .continuation { position: absolute; left: 4pt; font-size: 4.5pt; color: #d6e3ed; line-height: 1; }
        .row { position: absolute; width: 178.33pt; }
        .circle { display: block; position: absolute; left: 1pt; top: 1.5pt; width: 7pt; height: 7pt; border: 0.8pt solid #435665; border-radius: 50%; background: #fff; }
        .profile .circle { top: 4pt; }
        .name { position: absolute; left: 13pt; top: 0; font-size: {{ $tamano }}pt; line-height: 1; color: #18232f; }
        .text-line { position: absolute; left: 0; white-space: nowrap; }
        .price { position: absolute; right: 1pt; top: 0; font-size: {{ $tamano }}pt; line-height: {{ $interlineado }}pt; color: #465565; }
        .guidance { position: absolute; top: 741pt; width: 543pt; border: 1pt solid #203552; border-radius: 6pt; padding: 4pt 7pt; font-size: 6pt; line-height: 8pt; }
        .guidance-title { text-align: center; font-size: 9pt; font-weight: bold; font-style: italic; margin-bottom: 3pt; }
        .contact { position: absolute; top: 795pt; width: 559pt; color: #465565; font-size: 5.5pt; text-align: center; }
    </style>
</head>
<body>
@foreach ($paginas as $pagina)
    <div class="sheet {{ !$loop->first ? 'next-page' : '' }}">
        <img class="banner" src="{{ public_path('images/pdf-banner.svg') }}" alt="">
        <img class="crab-watermark" src="{{ public_path('images/pdf-crab.svg') }}" alt="">
        <div class="subtitle">Laboratorio Clínico Especializado</div>
        <div class="brand">{{ config('laboratorio.nombre') }}</div>
        @if (is_file($logoPath))<img class="logo" src="{{ $logoPath }}" alt="ONCOSAVI">@endif
        <table class="patient"><tr>
            <td>Paciente: <span class="write-line" style="width: 351pt;"></span></td>
            <td>Edad: <span class="write-line" style="width: 86pt;"></span></td>
        </tr></table>
        <table class="metadata"><tr>
            <td>Fecha: <span class="write-line" style="width: 113pt;"></span></td>
            <td class="signature-line"><span class="write-line" style="width: 210pt;"></span><br>Firma y sello del médico</td>
            <td>Sexo: M <span class="sex-box"></span>&nbsp; F <span class="sex-box"></span></td>
        </tr></table>
        <div class="instruction">Marque los círculos de los exámenes solicitados. En perfiles, marque el nombre para solicitar el conjunto.{{ $mostrarPrecios ? ' Precios en USD.' : '' }}</div>
        @foreach ($pagina as $columna)
            <div class="column" style="left: {{ $loop->index * 190.33 }}pt;">
                @foreach ($columna as $seccion)
                    <div class="section" style="top: {{ $seccion['y'] }}pt;">
                        <div class="section-heading {{ $seccion['perfil'] ? 'profile' : '' }}" style="height: {{ $seccion['alto_titulo'] - 4 }}pt;">
                            @if ($seccion['perfil'])<span class="circle"></span>@endif
                            <div class="heading-name">@foreach ($seccion['lineas'] as $linea)<span class="text-line" style="top: {{ $loop->index * $interlineado }}pt;">{{ $linea }}</span>@endforeach</div>
                            @if ($mostrarPrecios && $seccion['precio'] !== null)<span class="heading-price">${{ number_format($seccion['precio'], 2) }}</span>@endif
                            @if ($seccion['continuacion'])<span class="continuation" style="top: {{ $seccion['alto_titulo'] - 12 }}pt;">Continuación</span>@endif
                        </div>
                        @foreach ($seccion['filas'] as $fila)
                            <div class="row" style="top: {{ $fila['y'] }}pt; height: {{ $fila['alto'] - 1 }}pt;">
                                <span class="circle"></span>
                                <div class="name">@foreach ($fila['lineas'] as $linea)<span class="text-line" style="top: {{ $loop->index * $interlineado }}pt;">{{ $linea }}</span>@endforeach</div>
                                @if ($mostrarPrecios && $fila['precio'] !== null)<span class="price">${{ number_format($fila['precio'], 2) }}</span>@endif
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        @endforeach
        <div class="guidance">
            <div class="guidance-title">INDICACIONES GENERALES</div>
            <div>1. Para exámenes en ayunas, siga la indicación de su médico o del laboratorio.</div>
            <div>2. Para muestras de orina, solicite el recipiente y las indicaciones de recolección al laboratorio.</div>
            <div>3. Para muestras de heces, consulte las indicaciones de preparación y entrega al laboratorio.</div>
        </div>
        <div class="contact">{{ config('laboratorio.telefono') }} &nbsp; · &nbsp; {{ config('laboratorio.correo') }} &nbsp; · &nbsp; {{ config('laboratorio.sede') }}</div>
    </div>
@endforeach
</body>
</html>
