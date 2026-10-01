<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cotización de Servicios</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 12px; color: #333; }
        .container { width: 100%; margin: 0 auto; }
        .header { border-bottom: 3px solid #64ABC6; padding-bottom: 12px; margin-bottom: 24px; }
        .header-main { width: 100%; border-collapse: collapse; margin: 0; }
        .header-main td { padding: 0; border: 0; vertical-align: middle; }
        .logo-cell { width: 82px; }
        .logo-cell img { width: 68px; height: 68px; object-fit: contain; }
        .brand-name { color: #090B3B; font-size: 24px; font-weight: bold; letter-spacing: 1.2px; line-height: 1; }
        .brand-subtitle { margin-top: 5px; color: #476170; font-size: 9px; letter-spacing: .4px; }
        .brand-address { margin-top: 9px; max-width: 450px; color: #344B59; font-size: 8px; line-height: 1.35; }
        .document-cell { width: 235px; text-align: right; }
        .document-kicker { color: #E32737; font-size: 8px; font-weight: bold; letter-spacing: 1.3px; }
        h1 { color: #090B3B; font-size: 23px; margin: 4px 0 9px; line-height: 1.1; }
        .document-meta { color: #526976; font-size: 9px; line-height: 1.6; }
        .contact-bar { width: 100%; border-collapse: collapse; margin: 12px 0 0; background: #F1F7F9; }
        .contact-bar td { padding: 7px 10px; border: 0; vertical-align: middle; white-space: nowrap; color: #090B3B; font-size: 8px; }
        .contact-bar a { color: #090B3B; text-decoration: none; }
        .contact-icon { width: 13px; height: 13px; margin-right: 5px; vertical-align: -3px; }
        .contact-label { color: #607784; font-size: 7px; font-weight: bold; text-transform: uppercase; }
        .generated-by { margin: 9px 0 0; color: #607784; font-size: 9px; text-align: right; }
        h2 { font-size: 16px; margin-bottom: 10px; border-bottom: 1px solid #ccc; padding-bottom: 5px; }
        .client-info p { margin: 0; line-height: 1.5; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; page-break-inside: auto; }
        tr { page-break-inside: avoid; page-break-after: auto; }
        th, td { padding: 8px; text-align: left; }
        thead th { background-color: #EAF4F8; color: #090B3B; border-bottom: 2px solid #ddd; }
        .text-right { text-align: right; }
        .font-mono { font-family: 'Courier New', Courier, monospace; }
        .profile-main-row { font-weight: bold; }
        .profile-detail-row td { padding-top: 0; padding-bottom: 5px; font-size: 11px; color: #555; border: none; }
        .profile-detail-row .description { padding-left: 25px; }
        .total-section { margin-top: 20px; float: right; width: 40%; }
        .total-section table { width: 100%; }
        .total-section td { padding: 10px; }
        .total-final { font-size: 18px; font-weight: bold; background-color: #EAF4F8; color: #090B3B; }
        .footer-note { margin-top: 40px; padding: 10px; border-top: 1px solid #eee; text-align: center; font-size: 11px; color: #777; }
        @page { margin: 35px 25px; }
        footer { position: fixed; bottom: -20px; left: 0px; right: 0px; height: 30px; text-align: center; font-size: 10px; color: #aaa; }
        footer .page-number:before { content: "Página " counter(page); }
    </style>
</head>
<body>
    <footer>
        <span class="page-number"></span>
    </footer>
    <div class="container">
        <div class="header">
            <table class="header-main">
                <tr>
                    <td class="logo-cell">
                        <img src="{{ public_path(config('laboratorio.logo')) }}" alt="{{ config('laboratorio.nombre') }}">
                    </td>
                    <td>
                        <div class="brand-name">{{ config('laboratorio.nombre') }}</div>
                        <div class="brand-subtitle">{{ config('laboratorio.sede') }} · Laboratorio clínico</div>
                        <div class="brand-address">{{ config('laboratorio.direccion') }}</div>
                    </td>
                    <td class="document-cell">
                        <div class="document-kicker">DOCUMENTO COMERCIAL</div>
                        <h1>Cotización de Servicios</h1>
                        <div class="document-meta">Fecha de emisión<br><strong>{{ now()->translatedFormat('d \d\e F \d\e Y') }}</strong></div>
                    </td>
                </tr>
            </table>
            <table class="contact-bar">
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
        @if ($cliente_nombre)
            <div class="client-info">
                <h2>Cliente</h2>
                <p><strong>Nombre:</strong> {{ $cliente_nombre }}</p>
            </div>
        @endif
        <h2>Detalles de la Cotización</h2>
        <table>
            <thead>
                <tr>
                    <th>Descripción</th>
                    <th class="text-right">Precio</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($perfiles as $perfil)
                    <tr class="profile-main-row">
                        <td>{{ $perfil['nombre'] }}</td>
                        <td class="text-right font-mono">${{ number_format($perfil['precio'], 2) }}</td>
                    </tr>
                    @foreach ($perfil['examenes'] as $examen)
                        <tr class="profile-detail-row">
                            <td class="description">- {{ $examen->nombre }}</td>
                            <td></td>
                        </tr>
                    @endforeach
                @endforeach
                @foreach ($examenes as $examen)
                    <tr>
                        <td>{{ $examen['nombre'] }}</td>
                        <td class="text-right font-mono">${{ number_format($examen['precio'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="total-section">
            <table>
                <tr class="total-final">
                    <td><strong>Total a Pagar:</strong></td>
                    <td class="text-right font-mono">${{ number_format($total, 2) }}</td>
                </tr>
            </table>
        </div>

        <div style="clear: both;"></div>
        
        <div class="footer-note">
            <p>Esta cotización tiene una validez de 30 días. Los precios están sujetos a cambios sin previo aviso.</p>
        </div>
    </div>
</body>
</html>
