<?php

namespace Tests\Feature;

use App\Models\Cliente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClienteTelefonosTest extends TestCase
{
    use RefreshDatabase;

    public function test_guarda_varios_telefonos_y_los_convierte_para_whatsapp(): void
    {
        $cliente = Cliente::create([
            'nombre' => 'Paciente',
            'apellido' => 'Prueba',
            'genero' => 'Femenino',
            'estado' => 'Activo',
            'telefonos' => [
                ['tipo' => 'sv', 'numero' => '9999-9999'],
                ['tipo' => 'us', 'numero' => '(999) 999-9999'],
            ],
        ]);

        $this->assertSame('+50399999999', $cliente->telefono);
        $this->assertSame(['+503 9999-9999', '+1 (999) 999-9999'], $cliente->telefonos_registrados);
        $this->assertSame([
            '50399999999' => '+503 9999-9999',
            '19999999999' => '+1 (999) 999-9999',
        ], $cliente->telefonosParaWhatsapp());
    }

    public function test_mantiene_compatibilidad_con_un_telefono_existente(): void
    {
        $cliente = new Cliente(['telefono' => '9999-9999']);

        $this->assertSame(['50399999999' => '+503 9999-9999'], $cliente->telefonosParaWhatsapp());
    }

    public function test_formatea_los_tres_formatos_visuales_admitidos(): void
    {
        $this->assertSame('9999-9999', Cliente::formatearTelefono('99999999'));
        $this->assertSame('+503 9999-9999', Cliente::formatearTelefono('+50399999999'));
        $this->assertSame('+1 (999) 999-9999', Cliente::formatearTelefono('+19999999999'));
    }
}
