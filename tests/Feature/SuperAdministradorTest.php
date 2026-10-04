<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\User;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class SuperAdministradorTest extends TestCase
{
    use RefreshDatabase;

    public function test_soporte_es_administrador_identificado_y_sus_cambios_se_auditan(): void
    {
        Storage::fake('local');
        $this->seed(RolesPermisosSeeder::class);
        $this->artisan('oncosavi:crear-superadmin')->assertSuccessful();
        $usuario = User::where('nickname', 'soporte.superadmin')->firstOrFail();
        $this->assertTrue($usuario->hasRole('super_admin'));
        $this->assertTrue($usuario->hasRole('admin'));
        $this->assertTrue($usuario->can('access_admin_panel'));
        $credenciales = json_decode(Storage::disk('local')->get('soporte-superadmin.json'), true);
        $this->assertTrue(Hash::check($credenciales['password'], $usuario->password));
        $this->actingAs($usuario);
        $this->get('/admin/users')->assertOk()->assertSee('soporte.superadmin');
        $cliente = Cliente::create(['nombre' => 'Paciente', 'apellido' => 'Ejemplo', 'genero' => 'Femenino']);
        $this->assertTrue(Activity::where('subject_type', Cliente::class)->where('subject_id', $cliente->id)->where('causer_id', $usuario->id)->exists());
        $this->artisan('oncosavi:crear-superadmin')->assertSuccessful();
        $this->assertSame($usuario->password, $usuario->fresh()->password);
        $this->assertSame(1, User::where('nickname', 'soporte.superadmin')->count());
    }

    public function test_no_reasigna_una_cuenta_existente_por_coincidencia(): void
    {
        $this->seed(RolesPermisosSeeder::class);
        $usuario = User::factory()->create(['nickname' => 'soporte.superadmin']);
        $this->artisan('oncosavi:crear-superadmin')->assertFailed();
        $this->assertFalse($usuario->fresh()->hasRole('admin'));
    }
}
