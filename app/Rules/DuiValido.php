<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class DuiValido implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $dui = preg_replace('/\D/', '', (string) $value);

        if (strlen($dui) !== 9) {
            $fail('El DUI debe contener nueve dígitos.');

            return;
        }

        $pesos = [9, 8, 7, 6, 5, 4, 3, 2];
        $suma = 0;

        for ($i = 0; $i < 8; $i++) {
            $suma += ((int) $dui[$i]) * $pesos[$i];
        }

        $verificador = (10 - ($suma % 10)) % 10;

        if ($verificador !== (int) $dui[8]) {
            $fail('El DUI no es válido.');
        }
    }
}
