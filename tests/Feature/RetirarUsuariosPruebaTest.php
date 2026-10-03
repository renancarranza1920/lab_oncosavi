<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\DetalleOrden;
use App\Models\Examen;
use App\Models\Orden;
use App\Models\Resultado;
use App\Models\TipoExamen;
use App\Models\User;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RetirarUsuariosPruebaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermisosSeeder::class);
        Storage::fake('local');
        Storage::fake('public');
    }

    private function cuenta(string $nickname, string $rol, array $datos = []): User
    {
        $usuario = User::factory()->create($datos + [
            'nickname' => $nickname, 'name' => 'Usuario '.$nickname, 'email' => $nickname.'@oncosavi.test',
        ]);
        $usuario->assignRole($rol);

        return $usuario;
    }

    public function test_retira_solo_personal_de_prueba_y_conserva_admin_roles_permisos_y_personal_real(): void
    {
        $admin = $this->cuenta('prueba.admin', 'admin');
        $realRecepcion = $this->cuenta('recepcion', 'Recepcion');
        $realLab = $this->cuenta('laboratorista', 'Laboratorista');
        $colision = $this->cuenta('prueba.recepcion', 'Recepcion', ['email' => 'persona@example.test']);
        $adminProtegido = $this->cuenta('admin.protegido', 'admin', ['name' => 'Prueba Lab']);
        $pruebas = collect([
            $this->cuenta('prueba.lab', 'Laboratorista'),
            $this->cuenta('prueba.laboratorista', 'Laboratorista'),
            $this->cuenta('demo.recepcion', 'Recepcion', ['name' => 'Prueba Recepción']),
            $this->cuenta('demo.sin-nickname', 'Laboratorista', ['name' => 'Prueba Lab', 'nickname' => null]),
        ]);
        $roles = Role::orderBy('id')->get()->toArray();
        $permisos = DB::table('role_has_permissions')->orderBy('role_id')->orderBy('permission_id')->get()->toArray();
        $hashAdmin = $admin->password;
        foreach ($pruebas as $usuario) {
            $usuario->givePermissionTo('view_any_orden');
        }

        $this->artisan('oncosavi:eliminar-usuarios-prueba')->assertSuccessful();

        foreach ($pruebas as $usuario) {
            $this->assertDatabaseMissing('users', ['id' => $usuario->id]);
            $this->assertDatabaseMissing('model_has_roles', ['model_type' => User::class, 'model_id' => $usuario->id]);
            $this->assertDatabaseMissing('model_has_permissions', ['model_type' => User::class, 'model_id' => $usuario->id]);
            $this->assertTrue(Activity::where('subject_type', User::class)->where('subject_id', $usuario->id)->where('event', 'deleted')->exists());
        }
        foreach ([$admin, $realRecepcion, $realLab, $colision, $adminProtegido] as $usuario) {
            $this->assertDatabaseHas('users', ['id' => $usuario->id]);
        }
        $this->assertSame($hashAdmin, $admin->fresh()->password);
        $this->assertSame($roles, Role::orderBy('id')->get()->toArray());
        $this->assertEquals($permisos, DB::table('role_has_permissions')->orderBy('role_id')->orderBy('permission_id')->get()->toArray());
    }

    public function test_conserva_ordenes_resultados_pdfs_firmas_y_bitacora_y_retira_sesiones(): void
    {
        $usuario = $this->cuenta('prueba.laboratorista', 'Laboratorista', ['firma_path' => 'firmas/prueba.png']);
        $cliente = Cliente::create(['nombre' => 'Paciente', 'apellido' => 'Prueba', 'genero' => 'Femenino']);
        $tipo = TipoExamen::create(['nombre' => 'Química']);
        $examen = Examen::create(['nombre' => 'Glucosa', 'precio' => 10, 'tipo_examen_id' => $tipo->id]);
        $orden = Orden::create(['cliente_id' => $cliente->id, 'total' => 10, 'fecha' => now(), 'estado' => 'finalizado', 'toma_muestra_user_id' => $usuario->id]);
        $detalle = DetalleOrden::create(['orden_id' => $orden->id, 'examen_id' => $examen->id, 'nombre_examen' => 'Glucosa', 'precio_examen' => 10, 'status' => 'finalizado']);
        $resultado = Resultado::create(['detalle_orden_id' => $detalle->id, 'user_id' => $usuario->id, 'resultado' => '95', 'es_externo' => true]);
        Storage::disk('public')->put($orden->reporteGuardadoPath(), '%PDF-1.7 original firmado');
        Storage::disk('public')->put($usuario->firma_path, 'firma original');
        $evento = Activity::create(['log_name' => 'Resultados', 'description' => 'Resultado ingresado', 'causer_type' => User::class, 'causer_id' => $usuario->id]);
        DB::table('sessions')->insert(['id' => 'sesion-prueba', 'user_id' => $usuario->id, 'payload' => '', 'last_activity' => time()]);
        DB::table('sessions')->insert(['id' => 'sesion-ajena', 'user_id' => null, 'payload' => '', 'last_activity' => time()]);
        DB::table('password_reset_tokens')->insert(['email' => $usuario->email, 'token' => 'token-prueba']);

        $this->artisan('oncosavi:eliminar-usuarios-prueba')->assertSuccessful();

        $this->assertSame('finalizado', $orden->fresh()->estado);
        $this->assertNull($orden->fresh()->toma_muestra_user_id);
        $this->assertSame('95', $resultado->fresh()->resultado);
        $this->assertNull($resultado->fresh()->user_id);
        $this->assertDatabaseHas('detalle_orden', ['id' => $detalle->id]);
        $this->assertDatabaseHas('clientes', ['id' => $cliente->id]);
        $this->assertDatabaseHas('activity_log', ['id' => $evento->id, 'causer_id' => $usuario->id]);
        $this->assertSame('%PDF-1.7 original firmado', Storage::disk('public')->get($orden->reporteGuardadoPath()));
        $this->assertSame('firma original', Storage::disk('public')->get($usuario->firma_path));
        $this->assertDatabaseMissing('sessions', ['id' => 'sesion-prueba']);
        $this->assertDatabaseHas('sessions', ['id' => 'sesion-ajena']);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $usuario->email]);
    }

    public function test_limpieza_repetible_retira_credenciales_borradas_y_conserva_las_de_admin(): void
    {
        $this->cuenta('prueba.admin', 'admin');
        $this->cuenta('prueba.recepcion', 'Recepcion');
        Storage::disk('local')->put('usuarios-prueba.json', json_encode([
            ['usuario' => 'prueba.admin', 'rol' => 'admin', 'password' => 'clave-admin-de-prueba'],
            ['usuario' => 'prueba.recepcion', 'rol' => 'Recepcion', 'password' => 'clave-recepcion-de-prueba'],
        ]));

        $this->artisan('oncosavi:eliminar-usuarios-prueba')->assertSuccessful();
        $archivo = Storage::disk('local')->get('usuarios-prueba.json');
        $this->assertSame([['usuario' => 'prueba.admin', 'rol' => 'admin', 'password' => 'clave-admin-de-prueba']], json_decode($archivo, true));
        $this->artisan('oncosavi:eliminar-usuarios-prueba')->assertSuccessful();
        $this->assertSame($archivo, Storage::disk('local')->get('usuarios-prueba.json'));
    }

    public function test_retira_archivo_si_solo_contenia_cuentas_eliminadas(): void
    {
        $this->cuenta('prueba.recepcion', 'Recepcion');
        Storage::disk('local')->put('usuarios-prueba.json', json_encode([['usuario' => 'prueba.recepcion', 'password' => 'clave-prueba']]));
        $this->artisan('oncosavi:eliminar-usuarios-prueba')->assertSuccessful();
        Storage::disk('local')->assertMissing('usuarios-prueba.json');
    }

    public function test_migracion_retira_cuentas_existentes_sin_recrearlas_al_revertir(): void
    {
        $admin = $this->cuenta('prueba.admin', 'admin');
        $usuario = $this->cuenta('prueba.recepcion', 'Recepcion');
        $migracion = require database_path('migrations/2026_10_03_000002_remove_staff_test_accounts.php');
        $migracion->up();
        $migracion->up();
        $migracion->down();
        $this->assertDatabaseMissing('users', ['id' => $usuario->id]);
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
        $this->assertCount(3, Role::all());
    }
}
