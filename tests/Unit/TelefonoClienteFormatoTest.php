<?php

namespace Tests\Unit;

use App\Support\TelefonoCliente;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class TelefonoClienteFormatoTest extends TestCase
{
    public function test_normaliza_los_cuatro_formatos_telefonicos(): void
    {
        $normalizar = new ReflectionMethod(TelefonoCliente::class, 'normalizarFormato');

        $this->assertSame('50372001156', $normalizar->invoke(null, '7200-1156', 'sv'));
        $this->assertSame('12125551234', $normalizar->invoke(null, '(212) 555-1234', 'us'));
        $this->assertSame('50323935349', $normalizar->invoke(null, '2393-5349', 'fijo'));
        $this->assertSame('50212345678', $normalizar->invoke(null, '(502)1234-5678', 'internacional'));
    }
}
