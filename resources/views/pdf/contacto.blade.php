<div style="font-size: 8px; line-height: 1.4; color: #090B3B; margin: 4px 0 8px;">
    <strong>{{ config('laboratorio.nombre') }} · {{ config('laboratorio.sede') }}</strong><br>
    <a href="{{ config('laboratorio.mapa_url') }}" style="color: #090B3B; text-decoration: none;">{{ config('laboratorio.direccion') }}</a><br>
    <a href="{{ config('laboratorio.telefono_uri') }}" style="color: #090B3B;">{{ config('laboratorio.telefono') }}</a>
    · <a href="{{ config('laboratorio.whatsapp_url') }}" style="color: #090B3B;">WhatsApp</a>
    · <a href="mailto:{{ config('laboratorio.correo') }}" style="color: #090B3B;">{{ config('laboratorio.correo') }}</a>
</div>
