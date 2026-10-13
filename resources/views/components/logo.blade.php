<span class="lab-brand" role="img" aria-label="{{ config('laboratorio.nombre') }} · {{ config('laboratorio.sede') }}">
    <span class="lab-brand-logo" aria-hidden="true">
        <img class="lab-logo-claro" src="{{ asset(config('laboratorio.logo_claro')) }}" alt="">
        <img class="lab-logo-oscuro" src="{{ asset(config('laboratorio.logo_oscuro')) }}" alt="">
    </span>
    <span class="lab-brand-sede" aria-hidden="true">{{ mb_strtoupper(config('laboratorio.sede')) }}</span>
</span>
