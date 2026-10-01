@php
    $diseno = isset($paginas) ? compact('paginas', 'tamano', 'interlineado') : app(\App\Services\CatalogoExamenesPdf::class)->organizar($areas, $perfiles);
    extract($diseno);
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Solicitud de exámenes · ONCOSAVI</title>
    <style>
        @page { margin: 18pt; }
        body { margin: 0; font-family: 'DejaVu Sans', sans-serif; color: #090B3B; }
        .sheet { position: relative; height: 805pt; width: 559pt; page-break-inside: avoid; }
        .next-page { page-break-before: always; }
        .logo { position: absolute; top: 0; left: 0; width: 36pt; height: 36pt; }
        .brand { position: absolute; top: 0; left: 46pt; font-size: 16pt; font-weight: bold; letter-spacing: 1pt; }
        .subtitle { position: absolute; top: 24pt; left: 46pt; font-size: 7pt; letter-spacing: 0.4pt; }
        .document-title { position: absolute; right: 0; top: 3pt; font-size: 9pt; font-weight: bold; text-align: right; }
        .document-title small { display: block; font-size: 6pt; color: #476170; font-weight: normal; margin-top: 3pt; }
        .contact { position: absolute; top: 40pt; width: 559pt; font-size: 6.5pt; line-height: 8pt; }
        .contact a { color: #090B3B; text-decoration: none; }
        .contact-address { color: #344B59; margin-bottom: 1.5pt; }
        .contact-items { border-collapse: collapse; }
        .contact-items td { padding: 0 13pt 0 0; vertical-align: middle; white-space: nowrap; }
        .contact-icon { width: 8pt; height: 8pt; vertical-align: -2pt; margin-right: 3pt; }
        .contact-label { color: #607784; font-size: 5.5pt; font-weight: bold; text-transform: uppercase; }
        .rule { position: absolute; top: 64pt; width: 559pt; border-top: 1.5pt solid #64ABC6; }
        .patient { position: absolute; top: 70pt; width: 559pt; font-size: 7pt; border-collapse: collapse; }
        .patient td { padding: 0 8pt 0 0; }
        .write-line { display: inline-block; height: 10pt; border-bottom: 0.6pt solid #85949E; }
        .instruction { position: absolute; top: 96pt; font-size: 6.5pt; color: #3B5263; }
        .column { position: absolute; top: 110pt; width: 178.33pt; }
        .section { position: absolute; width: 178.33pt; }
        .section-heading { position: absolute; top: 0; width: 178.33pt; border-top: 1pt solid #64ABC6; padding-top: 3pt; }
        .section-heading.profile { border-color: #E32737; }
        .heading-name { position: relative; margin-left: 0; font-size: {{ $tamano }}pt; line-height: 1; font-weight: bold; }
        .profile .heading-name { margin-left: 15pt; }
        .heading-price { position: absolute; right: 1pt; top: 3pt; font-size: {{ $tamano }}pt; line-height: {{ $interlineado }}pt; font-weight: bold; }
        .continuation { position: absolute; left: 15pt; font-size: 4.5pt; color: #526976; font-weight: normal; line-height: 1; }
        .row { position: absolute; width: 178.33pt; border-bottom: 0.3pt solid #E5EBEE; }
        .circle { display: block; position: absolute; left: 0; top: 1.5pt; width: 8.5pt; height: 8.5pt; border: 0.8pt solid #435665; border-radius: 50%; background: #fff; }
        .profile .circle { top: 4pt; }
        .name { position: absolute; left: 15pt; top: 0; font-size: {{ $tamano }}pt; line-height: 1; color: #182A37; }
        .text-line { position: absolute; left: 0; white-space: nowrap; }
        .price { position: absolute; right: 1pt; top: 0; font-size: {{ $tamano }}pt; line-height: {{ $interlineado }}pt; color: #344B59; }
    </style>
</head>
<body>
@foreach ($paginas as $pagina)
    <div class="sheet {{ !$loop->first ? 'next-page' : '' }}">
        @if (is_file($logoPath))<img class="logo" src="{{ $logoPath }}" alt="ONCOSAVI">@endif
        <div class="brand">{{ config('laboratorio.nombre') }}</div>
        <div class="subtitle">{{ config('laboratorio.sede') }} · Laboratorio clínico</div>
        <div class="document-title">SOLICITUD DE EXÁMENES<small>Catálogo de exámenes y perfiles</small></div>
        <div class="contact">
            <div class="contact-address">{{ config('laboratorio.direccion') }}</div>
            <table class="contact-items">
                <tr>
                    <td>
                        <a href="{{ config('laboratorio.telefono_uri') }}">
                            <img class="contact-icon" src="{{ public_path('images/icon-phone.svg') }}" alt="">
                            <span class="contact-label">Llamadas</span>&nbsp; {{ config('laboratorio.telefono') }}
                        </a>
                    </td>
                    <td>
                        <a href="{{ config('laboratorio.whatsapp_url') }}">
                            <img class="contact-icon" src="{{ public_path('images/icon-whatsapp.svg') }}" alt="">
                            <span class="contact-label">WhatsApp</span>&nbsp; {{ config('laboratorio.telefono') }}
                        </a>
                    </td>
                    <td>
                        <a href="mailto:{{ config('laboratorio.correo') }}">
                            <img class="contact-icon" src="{{ public_path('images/icon-email.svg') }}" alt="">
                            <span class="contact-label">Correo</span>&nbsp; {{ config('laboratorio.correo') }}
                        </a>
                    </td>
                </tr>
            </table>
        </div>
        <div class="rule"></div>
        <table class="patient">
            <tr>
                <td>Paciente: <span class="write-line" style="width: 210pt;"></span></td>
                <td>Médico: <span class="write-line" style="width: 160pt;"></span></td>
                <td>Edad: <span class="write-line" style="width: 43pt;"></span></td>
            </tr>
        </table>
        <div class="instruction">Marque con ✓ los círculos de los exámenes solicitados. En perfiles, marque el nombre para solicitar el conjunto. Precios en USD.</div>
        @foreach ($pagina as $columna)
            <div class="column" style="left: {{ $loop->index * 190.33 }}pt;">
                @foreach ($columna as $seccion)
                    <div class="section" style="top: {{ $seccion['y'] }}pt;">
                        <div class="section-heading {{ $seccion['perfil'] ? 'profile' : '' }}">
                            @if ($seccion['perfil'])<span class="circle"></span>@endif
                            <div class="heading-name">@foreach ($seccion['lineas'] as $linea)<span class="text-line" style="top: {{ $loop->index * $interlineado }}pt;">{{ $linea }}</span>@endforeach</div>
                            @if ($seccion['precio'] !== null)<span class="heading-price">${{ number_format($seccion['precio'], 2) }}</span>@endif
                            @if ($seccion['continuacion'])<span class="continuation" style="top: {{ $seccion['alto_titulo'] - 6 }}pt;">Continuación</span>@endif
                        </div>
                        @foreach ($seccion['filas'] as $fila)
                            <div class="row" style="top: {{ $fila['y'] }}pt; height: {{ $fila['alto'] - 1 }}pt;">
                                <span class="circle"></span>
                                <div class="name">@foreach ($fila['lineas'] as $linea)<span class="text-line" style="top: {{ $loop->index * $interlineado }}pt;">{{ $linea }}</span>@endforeach</div>
                                @if ($fila['precio'] !== null)<span class="price">${{ number_format($fila['precio'], 2) }}</span>@endif
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
@endforeach
</body>
</html>
