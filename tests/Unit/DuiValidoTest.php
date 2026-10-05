<?php

namespace Tests\Unit;

use App\Rules\DuiValido;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class DuiValidoTest extends TestCase
{
    public function test_acepta_un_dui_con_digito_verificador_correcto(): void
    {
        $validator = Validator::make(['dui' => '12345678-4'], [
            'dui' => [new DuiValido()],
        ]);

        $this->assertFalse($validator->fails());
    }

    public function test_rechaza_un_dui_con_digito_verificador_incorrecto(): void
    {
        $validator = Validator::make(['dui' => '12345678-9'], [
            'dui' => [new DuiValido()],
        ]);

        $this->assertTrue($validator->fails());
        $this->assertSame(
            'El DUI no es válido.',
            $validator->errors()->first('dui'),
        );
    }

    public function test_acepta_el_resultado_cero_del_modulo(): void
    {
        $validator = Validator::make(['dui' => '00000000-0'], [
            'dui' => [new DuiValido()],
        ]);

        $this->assertFalse($validator->fails());
    }
}
