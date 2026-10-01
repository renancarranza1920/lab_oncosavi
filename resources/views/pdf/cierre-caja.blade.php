<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Financiero - {{ $etiqueta }}</title>
    <style>
        @page { margin: 28px 32px 34px; }
        body { margin: 0; font-family: DejaVu Sans, sans-serif; color: #182A37; font-size: 9px; }
        .page-number { position: fixed; right: 0; bottom: -21px; color: #78909C; font-size: 8px; }
        .page-number:after { content: "Página " counter(page); }
        .header { width: 100%; border-collapse: collapse; border-bottom: 2px solid #64ABC6; padding-bottom: 8px; }
        .header td { padding: 0; vertical-align: middle; }
        .logo { width: 52px; height: 52px; }
        .brand { padding-left: 10px !important; }
        .brand-name { color: #090B3B; font-size: 20px; font-weight: bold; letter-spacing: 1px; }
        .brand-subtitle { margin-top: 3px; color: #526976; font-size: 8px; }
        .title { text-align: right; }
        .title small { color: #E32737; font-size: 7px; font-weight: bold; letter-spacing: 1px; }
        .title h1 { margin: 3px 0; color: #090B3B; font-size: 20px; }
        .title div { color: #526976; font-size: 8px; }
        .contact { width: 100%; border-collapse: collapse; margin: 8px 0 12px; background: #F1F7F9; }
        .contact td { padding: 5px 8px; white-space: nowrap; color: #090B3B; font-size: 7px; }
        .contact img { width: 11px; height: 11px; margin-right: 4px; vertical-align: -3px; }
        .contact strong { color: #607784; font-size: 6px; text-transform: uppercase; }
        .period { margin-bottom: 12px; padding: 7px 10px; border-left: 3px solid #E32737; background: #FAFBFC; }
        .period strong { color: #090B3B; font-size: 11px; }
        .summary { width: 100%; border-collapse: separate; border-spacing: 5px 0; margin: 0 -5px 13px; }
        .summary td { width: 20%; padding: 9px; border: 1px solid #DCE7EB; background: #F8FBFC; }
        .summary-label { color: #607784; font-size: 7px; text-transform: uppercase; }
        .summary-value { margin-top: 3px; color: #090B3B; font-size: 15px; font-weight: bold; }
        .summary-value.net { color: #218657; }
        .summary-value.discount { color: #B7791F; }
        h2 { margin: 13px 0 5px; padding-bottom: 4px; border-bottom: 1px solid #64ABC6; color: #090B3B; font-size: 11px; }
        table.data { width: 100%; border-collapse: collapse; }
        .data th { padding: 5px 6px; background: #EAF4F8; color: #090B3B; text-align: left; font-size: 7px; text-transform: uppercase; }
        .data td { padding: 4px 6px; border-bottom: 1px solid #E8EEF1; }
        .right { text-align: right !important; }
        .center { text-align: center !important; }
        .status { font-size: 7px; font-weight: bold; text-transform: uppercase; }
        .cancelled { color: #C62828; }
        .totals { page-break-inside: avoid; }
        .two-columns { width: 100%; border-collapse: collapse; }
        .two-columns > tbody > tr > td { width: 50%; vertical-align: top; padding: 0 6px 0 0; }
        .two-columns > tbody > tr > td + td { padding: 0 0 0 6px; }
        .note { margin-top: 10px; color: #607784; font-size: 7px; }
    </style>
</head>
<body>
    <div class="page-number"></div>

    <table class="header">
        <tr>
            <td style="width: 56px;">@if (is_file($logoPath))<img class="logo" src="{{ $logoPath }}" alt="">@endif</td>
            <td class="brand">
                <div class="brand-name">{{ config('laboratorio.nombre') }}</div>
                <div class="brand-subtitle">{{ config('laboratorio.sede') }} · Laboratorio clínico</div>
            </td>
            <td class="title">
                <small>CIERRE DE CAJA</small>
                <h1>Reporte Financiero - {{ $etiqueta }}</h1>
                <div>Generado {{ now()->format('d/m/Y H:i') }} por {{ $generadoPor }}</div>
            </td>
        </tr>
    </table>

    <table class="contact">
        <tr>
            <td><img src="{{ public_path('images/icon-phone.svg') }}" alt=""><strong>Llamadas</strong>&nbsp; {{ config('laboratorio.telefono') }}</td>
            <td><img src="{{ public_path('images/icon-whatsapp.svg') }}" alt=""><strong>WhatsApp</strong>&nbsp; {{ config('laboratorio.telefono') }}</td>
            <td><img src="{{ public_path('images/icon-email.svg') }}" alt=""><strong>Correo</strong>&nbsp; {{ config('laboratorio.correo') }}</td>
        </tr>
    </table>

    <div class="period">
        <strong>{{ $etiqueta }}</strong><br>
        Período comprendido del {{ $desde->format('d/m/Y') }} al {{ $hasta->format('d/m/Y') }}
    </div>

    <table class="summary">
        <tr>
            <td><div class="summary-label">Ingreso bruto</div><div class="summary-value">${{ number_format($resumen['ingreso_bruto'], 2) }}</div></td>
            <td><div class="summary-label">Descuentos</div><div class="summary-value discount">${{ number_format($resumen['descuentos'], 2) }}</div></td>
            <td><div class="summary-label">Ingreso neto</div><div class="summary-value net">${{ number_format($resumen['ingreso_neto'], 2) }}</div></td>
            <td><div class="summary-label">Ticket promedio</div><div class="summary-value">${{ number_format($resumen['ticket_promedio'], 2) }}</div></td>
            <td><div class="summary-label">Órdenes registradas</div><div class="summary-value">{{ $resumen['ordenes'] }}</div></td>
        </tr>
    </table>

    <table class="two-columns totals">
        <tr>
            <td>
                <h2>Resumen por estado</h2>
                <table class="data">
                    <thead><tr><th>Estado</th><th class="right">Órdenes</th><th class="right">Importe neto</th></tr></thead>
                    <tbody>
                        @foreach ($estados as $estado)
                            <tr><td>{{ $estado['etiqueta'] }}</td><td class="right">{{ $estado['cantidad'] }}</td><td class="right">${{ number_format($estado['total'], 2) }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </td>
            <td>
                <h2>Movimiento diario</h2>
                <table class="data">
                    <thead><tr><th>Fecha</th><th class="right">Órdenes</th><th class="right">Bruto</th><th class="right">Desc.</th><th class="right">Neto</th></tr></thead>
                    <tbody>
                        @forelse ($movimientos as $movimiento)
                            <tr><td>{{ $movimiento['fecha'] }}</td><td class="right">{{ $movimiento['ordenes'] }}</td><td class="right">${{ number_format($movimiento['bruto'], 2) }}</td><td class="right">${{ number_format($movimiento['descuentos'], 2) }}</td><td class="right">${{ number_format($movimiento['neto'], 2) }}</td></tr>
                        @empty
                            <tr><td colspan="5" class="center">Sin movimientos en el período</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    <h2>Detalle de órdenes</h2>
    <table class="data">
        <thead>
            <tr><th style="width: 7%;">Orden</th><th style="width: 11%;">Fecha</th><th>Cliente</th><th style="width: 13%;">Estado</th><th class="right" style="width: 12%;">Bruto</th><th class="right" style="width: 12%;">Descuento</th><th class="right" style="width: 12%;">Neto</th></tr>
        </thead>
        <tbody>
            @forelse ($ordenes as $orden)
                <tr>
                    <td>#{{ $orden['id'] }}</td><td>{{ $orden['fecha'] }}</td><td>{{ $orden['cliente'] }}</td>
                    <td class="status {{ $orden['estado'] === 'cancelado' ? 'cancelled' : '' }}">{{ $orden['estado'] }}</td>
                    <td class="right">${{ number_format($orden['bruto'], 2) }}</td><td class="right">${{ number_format($orden['descuento'], 2) }}</td><td class="right">${{ number_format($orden['neto'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="center">No hay órdenes registradas en este período.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="note">
        Los ingresos consideran todas las órdenes no canceladas. Valor de órdenes canceladas: ${{ number_format($resumen['valor_cancelado'], 2) }}. Este reporte representa ingresos registrados; el sistema no almacena actualmente método ni confirmación de pago.
    </div>
</body>
</html>
