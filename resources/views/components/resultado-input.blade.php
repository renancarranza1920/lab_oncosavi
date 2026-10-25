@props(['valor' => null])

@php
    $textoInicial = trim((string) $valor);
    $usaComaDecimal = preg_match('/^[+-]?\d+,\d+$/D', $textoInicial)
        && ! preg_match('/^[+-]?\d{1,3}(?:,\d{3})+$/D', $textoInicial);
@endphp

<x-filament::input
    type="text"
    :value="$valor"
    x-data="{
        comaDecimal: {{ $usaComaDecimal ? 'true' : 'false' }},
        activarComa(input) {
            if (this.comaDecimal) return;

            if (/^-?[\d,]+(?:\.\d*)?$/.test(input.value)) {
                const inicio = input.selectionStart ?? input.value.length;
                const fin = input.selectionEnd ?? inicio;
                const inicioSinGrupos = input.value.slice(0, inicio).replace(/,/g, '').length;
                const finSinGrupos = input.value.slice(0, fin).replace(/,/g, '').length;
                input.value = input.value.replace(/,/g, '');
                input.setSelectionRange(inicioSinGrupos, finSinGrupos);
            }

            this.comaDecimal = true;
        }
    }"
    x-on:beforeinput="if (($event.data ?? '').includes(',')) activarComa($el)"
    x-on:paste="
        const texto = ($event.clipboardData?.getData('text') ?? '').trim();
        if (/^[+-]?\d+,\d+$/.test(texto) && !/^[+-]?\d{1,3}(?:,\d{3})+$/.test(texto)) activarComa($el)
    "
    x-on:input="if (!$el.value.includes(',')) comaDecimal = false"
    x-mask:dynamic="comaDecimal ? '' : (/^-?[\d,]*(\.\d*)?$/.test($input) ? $money($input, '.', ',', 20) : '')"
    {{ $attributes }}
/>
