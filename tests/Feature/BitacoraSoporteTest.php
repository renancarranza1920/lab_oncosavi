<?php

namespace Tests\Feature;

use App\Filament\Resources\ActivityLogResource\Pages\ListActivityLogs;
use App\Filament\Resources\RegistroSoporteResource\Pages\ListRegistrosSoporte;
use App\Models\Actividad;
use App\Models\Cliente;
use App\Models\RegistroSoporte;
use App\Models\User;
use Database\Seeders\RolesPermisosSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BitacoraSoporteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermisosSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
    }

    private function usuario(bool $soporte = false): User
    {
        $user = User::factory()->create(['nickname' => 'auditoria.'.\Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(12))]);
        $user->assignRole('admin');
        if ($soporte) $user->assignRole(Role::findOrCreate('super_admin', 'web'));
        return $user;
    }

    public function test_detalles_de_soporte_se_conservan_y_no_se_filtran_en_bitacora_general(): void
    {
        $cliente = Cliente::create(['nombre' => 'Paciente', 'apellido' => 'Ejemplo', 'genero' => 'Femenino']);
        $soporte = $this->usuario(true);
        $this->actingAs($soporte);
        $registro = activity('Pacientes')->performedOn($cliente)->event('updated')
            ->withProperties(['old' => ['direccion' => 'Domicilio reservado anterior'], 'attributes' => ['direccion' => 'Domicilio reservado nuevo', 'datos_anidados' => ['nota' => 'Nota privada', 'token' => 'token-privado'], 'password' => 'clave-que-no-debe-guardarse']])
            ->log('Cambio de domicilio solicitado privadamente.');
        $publico = Activity::findOrFail($registro->id);
        $this->assertSame('Ajuste de soporte técnico', $publico->description);
        $this->assertSame(Actividad::EVENTO_SOPORTE, $publico->event);
        $this->assertNull($publico->subject_type);
        $this->assertNull($publico->subject_id);
        $this->assertSame([], $publico->properties->all());
        $privado = RegistroSoporte::where('activity_id', $publico->id)->firstOrFail();
        $this->assertSame('Cambio de domicilio solicitado privadamente.', $privado->datos['description']);
        $this->assertSame('Domicilio reservado nuevo', $privado->datos['properties']['attributes']['direccion']);
        $this->assertArrayNotHasKey('password', $privado->datos['properties']['attributes']);
        $this->assertSame($cliente->id, $privado->datos['subject_id']);
        $this->actingAs($this->usuario());
        $pagina = Livewire::test(ListActivityLogs::class)->assertCanSeeTableRecords([$publico])
            ->assertSee('Ajuste de soporte técnico')->assertDontSee('Domicilio reservado nuevo')
            ->mountTableAction('view', $publico)->assertDontSee('direccion')->assertDontSee('Cambio de domicilio solicitado privadamente.');
        $this->assertStringNotContainsString('Domicilio reservado', json_encode($pagina->snapshot));
        Livewire::test(ListActivityLogs::class)->searchTable('Domicilio reservado')->assertCanNotSeeTableRecords([$publico]);
        Livewire::test(ListActivityLogs::class)->filterTable('event', 'updated')->assertCanNotSeeTableRecords([$publico]);
        $this->get('/admin/bitacora-soporte')->assertForbidden();
        $this->actingAs($soporte);
        Livewire::test(ListRegistrosSoporte::class)->mountTableAction('view', $privado)
            ->assertSee('Domicilio reservado nuevo')->assertSee('Nota privada')->assertDontSee('token-privado')->assertSee('Cambio de domicilio solicitado privadamente.');
    }

    public function test_acceso_del_propietario_es_directo_y_revocable_no_se_hereda_por_ser_admin(): void
    {
        $permiso = Permission::findOrCreate('ver_bitacora_soporte', 'web');
        Role::findByName('admin')->givePermissionTo($permiso);
        $propietario = $this->usuario();
        $this->actingAs($propietario);
        $this->get('/admin/bitacora-soporte')->assertForbidden();
        $this->artisan('oncosavi:autorizar-bitacora-soporte', ['usuario' => $propietario->nickname])->assertSuccessful();
        $this->actingAs($propietario->fresh());
        $this->get('/admin/bitacora-soporte')->assertOk();
        $this->artisan('oncosavi:autorizar-bitacora-soporte', ['usuario' => $propietario->nickname, '--revocar' => true])->assertSuccessful();
        $this->actingAs($propietario->fresh());
        $this->get('/admin/bitacora-soporte')->assertForbidden();
    }

    public function test_eventos_anteriores_se_reservan_sin_perder_detalle_o_fecha_y_sin_duplicarlos(): void
    {
        $soporte = $this->usuario(true);
        $registro = Activity::create(['log_name' => 'Pacientes', 'event' => 'updated', 'description' => 'Dato confidencial anterior',
            'causer_type' => User::class, 'causer_id' => $soporte->id, 'subject_type' => Cliente::class, 'subject_id' => 123,
            'properties' => ['attributes' => ['direccion' => 'Dirección reservada']], 'created_at' => '2026-10-01 09:00:00',
        ]);
        $this->artisan('oncosavi:proteger-bitacora-soporte')->assertSuccessful();
        $this->assertSame('2026-10-01 09:00:00', $registro->fresh()->created_at->format('Y-m-d H:i:s'));
        $this->assertSame('Ajuste de soporte técnico', $registro->fresh()->description);
        $this->assertSame('Dato confidencial anterior', RegistroSoporte::sole()->datos['description']);
        $this->artisan('oncosavi:proteger-bitacora-soporte')->assertSuccessful();
        $this->assertSame(1, RegistroSoporte::count());
        $this->assertDatabaseHas('activity_log', ['id' => $registro->id, 'causer_id' => $soporte->id]);
    }

    public function test_admin_no_autorizado_no_puede_autoconcederse_soporte_ni_tomar_su_cuenta(): void
    {
        $soporte = $this->usuario(true);
        $admin = $this->usuario();
        $this->actingAs($admin);
        $rol = Role::findByName('super_admin');
        $this->get('/admin/users/'.$soporte->id.'/edit')->assertForbidden();
        $this->get('/admin/shield/roles/'.$rol->id.'/edit')->assertForbidden();
        $this->assertFalse($admin->can('delete', $soporte));
        $this->assertFalse($admin->can('delete', $rol));
        Livewire::test(\App\Filament\Resources\UserResource\Pages\CreateUser::class)->fillForm([
            'name' => 'Cuenta indebida', 'nickname' => 'indebida', 'email' => 'indebida@example.invalid',
            'password' => 'Contraseña segura de prueba', 'roles' => $rol->id,
        ])->call('create')->assertHasFormErrors(['roles']);
        $this->assertDatabaseMissing('users', ['nickname' => 'indebida']);
        $this->artisan('oncosavi:autorizar-bitacora-soporte', ['usuario' => $admin->nickname])->assertSuccessful();
        $this->assertTrue(\App\Support\AccesoSoporte::autorizado($admin->fresh()));
        $this->assertTrue($admin->fresh()->can('update', $soporte));
        $this->actingAs($admin->fresh());
        $this->get('/admin/users/'.$soporte->id.'/edit')->assertOk();
        $this->get('/admin/shield/roles/'.$rol->id.'/edit')->assertOk();
    }

    public function test_no_se_puede_renombrar_un_rol_normal_a_super_admin_desde_la_web(): void
    {
        $this->actingAs($this->usuario());
        $role = Role::findOrCreate('Rol ordinario', 'web');
        $flag = new \ReflectionProperty($this->app, 'isRunningInConsole');
        $original = $flag->getValue($this->app);
        $flag->setValue($this->app, false);
        try {
            try {
                $role->update(['name' => 'super_admin']);
                $this->fail('El rol reservado no debe poder crearse desde una cuenta sin autorización.');
            } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
                $this->assertSame(403, $exception->getStatusCode());
            }
        } finally {
            $flag->setValue($this->app, $original);
        }
        $this->assertSame('Rol ordinario', $role->fresh()->name);
    }

    public function test_fallo_al_guardar_el_detalle_no_deja_un_registro_general_incompleto(): void
    {
        $this->actingAs($this->usuario(true));
        $cantidad = Activity::count();
        \Illuminate\Support\Facades\Event::listen('eloquent.creating: '.RegistroSoporte::class, function (): void {
            throw new \RuntimeException('Fallo de prueba al archivar.');
        });
        try {
            try {
                activity('Soporte')->event('updated')->log('Detalle que debe conservarse');
                $this->fail('El fallo debe impedir un registro incompleto.');
            } catch (\RuntimeException $exception) {
                $this->assertSame('Fallo de prueba al archivar.', $exception->getMessage());
            }
        } finally {
            \Illuminate\Support\Facades\Event::forget('eloquent.creating: '.RegistroSoporte::class);
        }
        $this->assertSame($cantidad, Activity::count());
        $this->assertSame(0, RegistroSoporte::count());
    }

    public function test_actividad_de_otros_usuarios_sigue_mostrando_detalles_normales(): void
    {
        $this->actingAs($this->usuario());
        $registro = activity('Pacientes')->event('updated')->withProperties(['attributes' => ['nombre' => 'Nombre normal']])->log('Cambio normal');
        $this->assertSame('Cambio normal', $registro->description);
        $this->assertSame('Nombre normal', $registro->properties->get('attributes')['nombre']);
        $this->assertSame(0, RegistroSoporte::count());
    }
}
