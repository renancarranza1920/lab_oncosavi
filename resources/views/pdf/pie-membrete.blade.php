<div class="paper-contact">
    <div class="paper-address">{{ config('laboratorio.direccion') }}</div>
    <table class="paper-contact-table">
        <tr>
            <td><img src="{{ public_path('images/icon-phone.svg') }}" alt=""> {{ config('laboratorio.telefono') }}</td>
            <td><img src="{{ public_path('images/icon-whatsapp.svg') }}" alt=""> {{ config('laboratorio.telefono') }}</td>
            <td><img src="{{ public_path('images/icon-email.svg') }}" alt=""> {{ config('laboratorio.correo') }}</td>
        </tr>
    </table>
</div>
<img class="paper-bottom-corner" src="{{ public_path('images/pdf-corner.svg') }}" alt="">
