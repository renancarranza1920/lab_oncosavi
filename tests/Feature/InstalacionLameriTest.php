<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InstalacionLameriTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::getDriverName());
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->artisan('migrate:fresh')->assertSuccessful();
        config([
            'laboratorio.admin_nickname' => 'lameri',
            'laboratorio.admin_nombre' => 'Administración Lameri',
            'laboratorio.admin_correo' => 'administracion@lameri.test',
            'laboratorio.admin_password' => 'ClaveInicialSoloParaPruebas2026!',
        ]);
    }

    protected function tearDown(): void
    {
        RefreshDatabaseState::$migrated = false;
        RefreshDatabaseState::$inMemoryConnections = [];
        parent::tearDown();
    }

    public function test_instalacion_nueva_conserva_catalogo_roles_y_referencias_sin_datos_operativos(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertCatalogoCompleto();
        foreach (['clientes', 'cliente_telefonos', 'ordens', 'detalle_orden', 'detalle_orden_perfils', 'resultados', 'sessions', 'password_reset_tokens', 'jobs', 'failed_jobs'] as $tabla) {
            $this->assertDatabaseCount($tabla, 0);
        }
        $this->assertDatabaseCount('users', 1);
        $admin = User::sole();
        $this->assertSame('lameri', $admin->nickname);
        $this->assertSame('Administración Lameri', $admin->name);
        $this->assertSame('administracion@lameri.test', $admin->email);
        $this->assertTrue(Hash::check('ClaveInicialSoloParaPruebas2026!', $admin->password));
        $this->assertNull($admin->firma_path);
        $this->assertNull($admin->sello_path);
        $this->assertTrue($admin->hasRole('admin'));
        $this->assertSame(Permission::count(), $admin->getAllPermissions()->count());
        $this->assertDatabaseCount('roles', 3);
        $this->assertTrue(Role::findByName('Recepcion')->hasPermissionTo('create_orden'));
        $this->assertFalse(Role::findByName('Recepcion')->hasPermissionTo('ingresar_resultados_orden'));
        $this->assertTrue(Role::findByName('Laboratorista')->hasPermissionTo('ingresar_resultados_orden'));
        $this->assertFalse(Role::findByName('Laboratorista')->hasPermissionTo('ver_bitacora_completa'));

        // La migración conserva únicamente el acceso funcional, desactivado, del portal.
        $this->assertDatabaseCount('medicos', 1);
        $this->assertDatabaseHas('medicos', [
            'nombre' => 'Acceso médico general', 'portal_usuario' => 'medicos',
            'password' => null, 'portal_activo' => false, 'portal_todos_pacientes' => true,
        ]);
        $this->assertFalse(DB::table('medicos')->whereNull('portal_usuario')->orWhere('portal_usuario', '!=', 'medicos')->exists());
        $this->assertDatabaseHas('valor_referencias', [
            'id' => 1, 'prueba_id' => 227, 'grupo_etario_id' => 10,
            'genero' => 'Masculino', 'valor_max' => 38, 'unidades' => 'U/L',
        ]);
        foreach ([169 => 'coprologia', 170 => 'hematologia'] as $id => $recipiente) {
            $this->assertDatabaseHas('examens', [
                'id' => $id, 'tipo_examen_id' => 1, 'nombre' => 'HEMOCULTIVO',
                'es_externo' => true, 'precio' => 25, 'recipiente' => $recipiente,
            ]);
            $this->assertDatabaseHas('examen_muestra', ['examen_id' => $id, 'muestra_id' => 15]);
        }
    }

    public function test_repetir_seeder_no_duplica_catalogo_ni_restaurar_password_o_perfil(): void
    {
        config(['laboratorio.admin_nickname' => 'gestion.lameri']);
        $this->seed(DatabaseSeeder::class);
        $admin = User::sole();
        $admin->update([
            'name' => 'Cuenta personalizada', 'email' => 'nuevo@lameri.test',
            'password' => 'ClaveRenovadaPorElPropietario!',
        ]);
        $hash = $admin->fresh()->password;
        config(['laboratorio.admin_password' => '', 'laboratorio.admin_correo' => '']);

        $this->seed(DatabaseSeeder::class);

        $this->assertCatalogoCompleto();
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('roles', 3);
        $this->assertDatabaseCount('medicos', 1);
        $admin->refresh();
        $this->assertSame('gestion.lameri', $admin->nickname);
        $this->assertSame('Cuenta personalizada', $admin->name);
        $this->assertSame('nuevo@lameri.test', $admin->email);
        $this->assertSame($hash, $admin->password);
        $this->assertTrue(Hash::check('ClaveRenovadaPorElPropietario!', $admin->password));
    }

    public function test_no_crea_administrador_con_password_predeterminada(): void
    {
        config(['laboratorio.admin_password' => '']);
        try {
            $this->app->make(DatabaseSeeder::class)->run();
            $this->fail('La instalación debe requerir LAMERI_ADMIN_PASSWORD.');
        } catch (RuntimeException $error) {
            $this->assertStringContainsString('LAMERI_ADMIN_PASSWORD', $error->getMessage());
        }
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('examens', 0);
        $this->assertDatabaseCount('roles', 0);
    }

    public function test_no_reutiliza_un_correo_perteneciente_a_otra_cuenta(): void
    {
        $usuario = User::factory()->create(['nickname' => 'propietario', 'email' => 'administracion@lameri.test']);
        $password = $usuario->password;
        try {
            $this->app->make(DatabaseSeeder::class)->run();
            $this->fail('El seeder debe conservar la cuenta que ya utiliza ese correo.');
        } catch (RuntimeException $error) {
            $this->assertStringContainsString('ya pertenece a otro usuario', $error->getMessage());
        }
        $this->assertDatabaseCount('users', 1);
        $this->assertSame($password, $usuario->fresh()->password);
        $this->assertFalse($usuario->fresh()->hasRole('admin'));
        $this->assertDatabaseCount('examens', 0);
    }

    private function assertCatalogoCompleto(): void
    {
        foreach ([
            'tipo_examens' => 13, 'examens' => 170, 'muestras' => 23,
            'examen_muestra' => 182, 'tipos_pruebas' => 8, 'pruebas' => 312,
            'grupos_etarios' => 10, 'valor_referencias' => 296,
            'perfil' => 7, 'detalle_perfil' => 40,
        ] as $tabla => $cantidad) {
            $this->assertDatabaseCount($tabla, $cantidad);
        }
        $this->assertFalse(DB::table('pruebas')->whereNotNull('examen_id')->whereNotIn('examen_id', DB::table('examens')->select('id'))->exists());
        $this->assertFalse(DB::table('valor_referencias')->whereNull('prueba_id')->orWhereNotIn('prueba_id', DB::table('pruebas')->select('id'))->exists());
    }
}
