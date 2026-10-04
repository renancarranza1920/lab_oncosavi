<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Orden;
use App\Models\User;
use App\Support\SelloLaboratorio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EntornoTestTest extends TestCase
{
    use RefreshDatabase;

    public function test_prepara_datos_ficticios_documentos_y_tres_roles_en_base_vacia(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        config(['test_demo.enabled' => true]);
        $this->artisan('oncosavi:preparar-test')->assertSuccessful();
        $this->assertSame(12, Cliente::count());
        $this->assertSame(12, Orden::count());
        $this->assertSame(4, User::count());
        foreach (['prueba.admin' => 'admin', 'prueba.lab' => 'laboratorista', 'prueba.recepcion' => 'recepcion'] as $nickname => $rol) {
            $usuario = User::where('nickname', $nickname)->firstOrFail();
            $this->assertSame($rol, strtolower($usuario->roles->first()->name));
        }
        $lab = User::where('nickname', 'prueba.lab')->firstOrFail();
        $this->assertTrue(Storage::disk('public')->exists($lab->sello_path));
        $this->assertTrue(Storage::disk('public')->exists($lab->firma_path));
        $this->assertNotNull(SelloLaboratorio::base64());
        foreach (Orden::where('estado', 'finalizado')->get() as $orden) {
            $this->assertTrue($orden->reporteGuardadoExists());
            $this->assertStringStartsWith('%PDF-', Storage::disk('public')->get($orden->reporteGuardadoPath()));
            $this->assertNotEmpty($orden->resultados);
        }
        $this->assertSame(3, count(json_decode(Storage::disk('local')->get('usuarios-prueba.json'), true)));
        $hash = User::where('nickname', 'prueba.admin')->value('password');
        $this->artisan('oncosavi:preparar-test')->assertFailed();
        $this->assertSame(12, Cliente::count());
        $this->assertSame($hash, User::where('nickname', 'prueba.admin')->value('password'));
    }

    public function test_rechaza_base_con_datos_y_rechaza_entorno_no_habilitado(): void
    {
        config(['test_demo.enabled' => true]);
        $cliente = Cliente::create(['nombre' => 'Paciente', 'apellido' => 'Existente', 'genero' => 'Femenino']);
        $this->artisan('oncosavi:preparar-test')->assertFailed();
        $this->assertSame('Existente', $cliente->fresh()->apellido);
        $this->assertSame(0, User::count());
        config(['test_demo.enabled' => false]);
        $this->artisan('oncosavi:usuarios-prueba')->assertFailed();
        $this->artisan('oncosavi:preparar-test')->assertFailed();
    }
}
