<div style="font-size: 8px; line-height: 1.4; color: #1E73BE; margin: 4px 0 8px;">
    <strong>{{ config('laboratorio.nombre') }} · {{ config('laboratorio.sede') }}</strong><br>
    <a href="{{ config('laboratorio.mapa_url') }}" style="color: #1E73BE; text-decoration: none;">{{ config('laboratorio.direccion') }}</a><br>
    <a href="{{ config('laboratorio.telefono_uri') }}" style="color: #1E73BE;">{{ config('laboratorio.telefono') }}</a>
    · <a href="{{ config('laboratorio.whatsapp_url') }}" style="color: #1E73BE;">WhatsApp: {{ config('laboratorio.whatsapp') }}</a>
    @if (config('laboratorio.correo'))
    · <a href="mailto:{{ config('laboratorio.correo') }}" style="color: #1E73BE;">{{ config('laboratorio.correo') }}</a>
    @endif
</div>
