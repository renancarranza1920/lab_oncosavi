<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 20px; }
        
        body { 
            font-family: 'Helvetica', sans-serif; 
            font-size: 11px; 
            color: #000;
            line-height: 1.2;
        }

        /* UTILITARIOS */
        .w-100 { width: 100%; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }

        /* HEADER Y TABLAS */
        .header { border-bottom: 2px solid #090B3B; padding-bottom: 5px; margin-bottom: 10px; }
        .title { font-size: 14px; font-weight: bold; }

        .info-table { width: 100%; margin-bottom: 10px; }
        .info-table td { vertical-align: top; padding-bottom: 4px; }
        .label { font-size: 9px; font-weight: bold; color: #444; }

        .items-table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        .items-table th { border-bottom: 1px solid #000; text-align: left; font-size: 9px; padding: 4px 0; }
        .items-table td { border-bottom: 1px solid #ddd; padding: 4px 0; font-size: 10px; }

        .total-section { margin-top: 10px; text-align: right; }
        .total-row { font-size: 14px; font-weight: bold; border-top: 1px solid #000; display: inline-block; padding-top: 2px; margin-top: 2px; }

        /* --- AQUÍ ESTÁ EL TRUCO PARA SEPARARLAS --- */
        .ticket-cliente {
            /* Forzamos que el primer ticket ocupe unos 380px (aprox media carta) 
               incluso si tiene poco contenido. Si tiene mucho, crecerá solo. */
            min-height: 380px; 
            position: relative;
        }

        .separator {
            margin: 10px 0 20px 0;
            border-bottom: 1px dashed #000;
            text-align: center;
            height: 10px;
        }
        .scissors {
            background-color: #fff;
            padding: 0 10px;
            font-size: 16px;
            position: relative;
            top: 2px;
        }
    </style>
</head>
<body>

@php
    $items = $orden->detalleOrden->groupBy(fn($d) => $d->nombre_perfil ?? 'Individuales');
@endphp

{{-- ========================================== --}}
{{-- COPIA 1: CLIENTE (Con altura mínima forzada) --}}
{{-- ========================================== --}}

<div class="ticket-cliente"> {{-- CLASE NUEVA AQUI --}}
    
    <div class="header">
        @include('pdf.contacto')
        <table class="w-100">
            <tr>
                <td>
                    <span class="title uppercase">Recibo de Orden</span>
                    <span style="font-size: 10px; margin-left: 10px;">(COPIA CLIENTE)</span>
                </td>
                <td class="text-right">
                    <span class="bold" style="font-size: 12px;">ORDEN #{{ str_pad($orden->id, 6, '0', STR_PAD_LEFT) }}</span>
                </td>
            </tr>
        </table>
    </div>

    <table class="info-table">
        <tr>
            <td style="width: 60%;">
                <div class="label uppercase">PACIENTE</div>
                <div class="bold">{{ strtoupper($orden->cliente->nombre . ' ' . $orden->cliente->apellido) }}</div>
            </td>
            <td class="text-right">
                <div class="label uppercase">FECHA Y HORA</div>
                <div>{{ $orden->created_at->format('d/m/Y h:i A') }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label uppercase">NO. EXPEDIENTE</div>
                <div>{{ $orden->cliente->NumeroExp ?? 'N/A' }}</div>
                <div class="label uppercase">DUI</div>
                <div>{{ $orden->cliente->dui ?? 'N/A' }}</div>
            </td>
            <td class="text-right">
                <div class="label uppercase">ATENDIDO POR</div>
                <div class="uppercase">{{ $usuario }}</div>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th>DESCRIPCIÓN</th>
                <th class="text-right">PRECIO</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $grupo => $detalles)
                @if($grupo !== 'Individuales')
                    <tr>
                        <td class="bold">{{ $grupo }} <span style="font-size: 9px; font-weight: normal;">(PERFIL)</span></td>
                        <td class="text-right bold">${{ number_format($detalles->first()->precio_perfil, 2) }}</td>
                    </tr>
                @else
                    @foreach($detalles as $detalle)
                        <tr>
                            <td>{{ $detalle->nombre_examen }}</td>
                            <td class="text-right bold">${{ number_format($detalle->precio_examen, 2) }}</td>
                        </tr>
                    @endforeach
                @endif
            @endforeach
        </tbody>
    </table>

    <div class="total-section">
        @if($orden->descuento > 0)
            <div style="font-size: 10px;">Desc: -${{ number_format($orden->descuento, 2) }}</div>
        @endif
        <div class="total-row">TOTAL: ${{ number_format($orden->total, 2) }}</div>
    </div>

    <div style="margin-top: 15px; font-size: 10px; text-align: center;">
        <em>Presentar esta boleta para retirar resultados. Gracias por su preferencia.</em>
    </div>

</div> {{-- Fin ticket-cliente --}}


{{-- ========================================== --}}
{{-- SEPARADOR --}}
{{-- ========================================== --}}

<div class="separator">
    <span class="scissors">✂</span>
</div>


{{-- ========================================== --}}
{{-- COPIA 2: LABORATORIO (Flujo normal) --}}
{{-- ========================================== --}}

<div> {{-- Contenedor simple --}}
    
    <div class="header">
        @include('pdf.contacto')
        <table class="w-100">
            <tr>
                <td>
                    <span class="title uppercase">Recibo de Orden</span>
                    <span style="font-size: 10px; margin-left: 10px;">(COPIA LABORATORIO)</span>
                </td>
                <td class="text-right">
                    <span class="bold" style="font-size: 12px;">ORDEN #{{ str_pad($orden->id, 6, '0', STR_PAD_LEFT) }}</span>
                </td>
            </tr>
        </table>
    </div>

    <table class="info-table">
        <tr>
            <td style="width: 60%;">
                <div class="label uppercase">PACIENTE</div>
                <div class="bold">{{ strtoupper($orden->cliente->nombre . ' ' . $orden->cliente->apellido) }}</div>
            </td>
            <td class="text-right">
                <div class="label uppercase">FECHA Y HORA</div>
                <div>{{ $orden->created_at->format('d/m/Y h:i A') }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label uppercase">NO. EXPEDIENTE</div>
                <div>{{ $orden->cliente->NumeroExp ?? 'N/A' }}</div>
                <div class="label uppercase">DUI</div>
                <div>{{ $orden->cliente->dui ?? 'N/A' }}</div>
            </td>
            <td class="text-right">
                <div class="label uppercase">ATENDIDO POR</div>
                <div class="uppercase">{{ $usuario }}</div>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th>DESCRIPCIÓN</th>
                <th class="text-right">PRECIO</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $grupo => $detalles)
                @if($grupo !== 'Individuales')
                    <tr>
                        <td class="bold">{{ $grupo }} <span style="font-size: 9px; font-weight: normal;">(PERFIL)</span></td>
                        <td class="text-right bold">${{ number_format($detalles->first()->precio_perfil, 2) }}</td>
                    </tr>
                @else
                    @foreach($detalles as $detalle)
                        <tr>
                            <td>{{ $detalle->nombre_examen }}</td>
                            <td class="text-right bold">${{ number_format($detalle->precio_examen, 2) }}</td>
                        </tr>
                    @endforeach
                @endif
            @endforeach
        </tbody>
    </table>

    <div class="total-section">
        @if($orden->descuento > 0)
            <div style="font-size: 10px;">Desc: -${{ number_format($orden->descuento, 2) }}</div>
        @endif
        <div class="total-row">TOTAL: ${{ number_format($orden->total, 2) }}</div>
    </div>

    <div style="margin-top: 15px; font-size: 10px; text-align: center;">
        <em>Copia de control interno.</em>
    </div>

</div>

</body>
</html>
