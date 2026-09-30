<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Orden</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 12px; color: #333; }
        .container { width: 100%; margin: 0 auto; }
        .header { display: table; width: 100%; border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 20px; }
        .header-left { display: table-cell; vertical-align: middle; }
        .header-right { display: table-cell; vertical-align: middle; text-align: right; }
        .header-right img { max-width: 120px; max-height: 82px; }
        h1 { font-size: 20px; margin: 0; }
        h2 { font-size: 16px; margin-bottom: 10px; border-bottom: 1px solid #ccc; padding-bottom: 5px; }
        .client-info p { margin: 0; line-height: 1.5; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 8px; text-align: left; }
        thead th { background-color: #EAF4F8; color: #090B3B; border-bottom: 2px solid #ddd; }
        .text-right { text-align: right; }
        .font-mono { font-family: 'Courier New', Courier, monospace; }
        .profile-main-row { font-weight: bold; }
        .profile-detail-row td { padding-top: 0; padding-bottom: 5px; font-size: 11px; color: #555; border: none; }
        .profile-detail-row .description { padding-left: 25px; }
        
        /* Ajustes para la sección de totales */
        .total-section { margin-top: 20px; float: right; width: 45%; } /* Aumenté un poco el ancho */
        .total-section table { width: 100%; }
        .total-section td { padding: 5px 10px; } /* Padding un poco más compacto */
        
        .total-final { font-size: 18px; font-weight: bold; background-color: #EAF4F8; color: #090B3B; }
        .total-final td { padding: 10px; border-top: 1px solid #ccc; } /* Borde superior para resaltar */

        .footer-note { margin-top: 40px; padding: 10px; border: 1px dashed #ccc; background-color: #fafafa; text-align: center; font-size: 11px; color: #777; }
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
            <div class="header-left">
                @include('pdf.contacto')
                <h1>Resumen de Orden</h1>
                <p>Fecha de Emisión: {{ now()->translatedFormat('d \d\e F \d\e Y') }}</p>
                @if ($usuario_nombre)
                    <p style="font-size: 11px; color: #555;">Atendido por: {{ $usuario_nombre }}</p>
                @endif
            </div>
            <div class="header-right">
                <img src="{{ public_path(config('laboratorio.logo')) }}" alt="Logo">
            </div>
        </div>

        @if ($cliente)
            <div class="client-info">
                <h2>Cliente</h2>
                <p><strong>Expediente:</strong> {{ $cliente->NumeroExp }}</p>
                <p><strong>DUI:</strong> {{ $cliente->dui ?? 'N/A' }}</p>
                <p><strong>Nombre:</strong> {{ $cliente->nombre }} {{ $cliente->apellido }}</p>
            </div>
        @endif

        <h2>Detalles de la Orden</h2>
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
                <tr>
                    <td><strong>Subtotal:</strong></td>
                    <td class="text-right font-mono">${{ number_format($subtotal, 2) }}</td>
                </tr>

                @if(isset($descuento) && $descuento > 0)
                    <tr style="color: #E32737;"> <td>
                            Descuento 
                            @if(!empty($codigo))
                                <span style="font-size: 10px; color: #666;">({{ $codigo }})</span>
                            @endif
                            :
                        </td>
                        <td class="text-right font-mono">- ${{ number_format($descuento, 2) }}</td>
                    </tr>
                @endif

                <tr class="total-final">
                    <td>Total a Pagar:</td>
                    <td class="text-right font-mono">${{ number_format($total, 2) }}</td>
                </tr>
            </table>
        </div>

        <div style="clear: both;"></div> <div class="footer-note">
            <p><strong>Nota:</strong> Este es un comprobante preliminar y no representa una factura fiscal. La orden aún no ha sido confirmada.</p>
        </div>
    </div>
</body>
</html>
