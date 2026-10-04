<?php

namespace Tests\Feature;

use App\Models\Actividad;
use App\Models\Cliente;
use App\Models\DetalleOrden;
use App\Models\Medico;
use App\Models\Orden;
use App\Models\User;
use App\Services\CierreCajaService;
use App\Services\ReiniciarDatosOperativos;
use App\Support\SesionPortalMedico;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReiniciarOperacionesTest extends TestCase
{
    use RefreshDatabase;

    private function preparar(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $this->seed(RolesPermisosSeeder::class);
        $staff = User::factory()->create(['firma_path' => 'firmas/real.png', 'sello_path' => 'sellos/personal.png']);
        $staff->assignRole('Laboratorista');
        Storage::disk('public')->put('firmas/real.png', 'Firma existente');
        Storage::disk('public')->put('sellos/personal.png', 'Sello existente');
        Storage::disk('public')->put('sellos/laboratorio/real.png', 'Sello institucional existente');
        Storage::disk('local')->put('laboratorio/sello.json', json_encode(['path' => 'sellos/laboratorio/real.png']));
        DB::table('medicos')->where('portal_usuario', 'medicos')->update(['id' => 50]);
        $general = Medico::where('portal_usuario', 'medicos')->firstOrFail();
        $general->forceFill(['password' => 'ClaveGeneral2026', 'portal_activo' => true])->save();
        Storage::disk('local')->put('portal-medicos-general.json', '{"usuario":"medicos","password":"ClaveGeneral2026"}');
        $medico = Medico::create(['nombre' => 'Dra. Prueba']);
        $cliente = Cliente::create(['nombre' => 'Paciente', 'apellido' => 'Prueba', 'genero' => 'Femenino']);
        $cliente->telefonos()->create(['numero' => '50370000001', 'codigo_pais' => '503', 'tipo' => 'movil']);
        $tipo = DB::table('tipo_examens')->insertGetId(['nombre' => 'Química', 'estado' => 1]);
        $examen = DB::table('examens')->insertGetId(['nombre' => 'Examen', 'tipo_examen_id' => $tipo, 'precio' => 10, 'estado' => 1]);
        $perfil = DB::table('perfil')->insertGetId(['nombre' => 'Perfil', 'precio' => 10, 'estado' => 1]);
        DB::table('detalle_perfil')->insert(['perfil_id' => $perfil, 'examen_id' => $examen]);
        $orden = Orden::create(['cliente_id' => $cliente->id, 'medico_id' => $medico->id, 'total' => 10, 'fecha' => now(), 'estado' => 'finalizado']);
        $detalle = DetalleOrden::create(['orden_id' => $orden->id, 'examen_id' => $examen, 'precio_examen' => 10, 'nombre_examen' => 'Examen', 'status' => 'completado']);
        DB::table('detalle_orden_perfils')->insert(['orden_id' => $orden->id, 'perfil_id' => $perfil]);
        DB::table('resultados')->insert(['detalle_orden_id' => $detalle->id, 'user_id' => $staff->id, 'resultado' => '80']);
        Storage::disk('public')->put($orden->reporteGuardadoPath(), 'PDF anterior');
    }

    public function test_preview_no_modifica_datos_ni_archivos(): void
    {
        $this->preparar();
        $antes = DB::table('users')->get()->toJson();
        $this->artisan('oncosavi:reiniciar-operaciones')->assertSuccessful();
        $this->assertSame(1, Orden::count());
        $this->assertSame(1, Cliente::count());
        $this->assertSame($antes, DB::table('users')->get()->toJson());
        $this->assertSame([], Storage::disk('local')->allFiles('reinicios'));
        $this->assertSame('PDF anterior', Storage::disk('public')->get(Orden::first()->reporteGuardadoPath()));
    }

    public function test_vacia_operaciones_reinicia_ids_y_conserva_personal_catalogos_y_portal(): void
    {
        $this->preparar();
        $conservar = [];
        foreach (['users', 'roles', 'permissions', 'model_has_roles', 'model_has_permissions', 'role_has_permissions', 'examens', 'perfil', 'detalle_perfil', 'tipo_examens'] as $tabla) {
            $conservar[$tabla] = DB::table($tabla)->get()->toJson();
        }
        $general = (array) DB::table('medicos')->where('portal_usuario', 'medicos')->first();
        $log = Actividad::where('subject_type', Orden::class)->firstOrFail();
        $pdf = Orden::first()->reporteGuardadoPath();
        $this->artisan('oncosavi:reiniciar-operaciones', ['--ejecutar' => true])->assertSuccessful();
        foreach (array_diff(ReiniciarDatosOperativos::TABLAS, ['medicos']) as $tabla) {
            $this->assertSame(0, DB::table($tabla)->count(), $tabla);
        }
        $this->assertSame(0, Medico::whereNull('portal_usuario')->count());
        $this->assertSame(array_replace($general, ['id' => 1]), (array) DB::table('medicos')->where('portal_usuario', 'medicos')->first());
        foreach ($conservar as $tabla => $valor) {
            $this->assertSame($valor, DB::table($tabla)->get()->toJson(), $tabla);
        }
        $this->assertSame('Firma existente', Storage::disk('public')->get('firmas/real.png'));
        $this->assertSame('Sello existente', Storage::disk('public')->get('sellos/personal.png'));
        $this->assertSame('Sello institucional existente', Storage::disk('public')->get('sellos/laboratorio/real.png'));
        $this->assertSame('sellos/laboratorio/real.png', \App\Support\SelloLaboratorio::path());
        $this->assertTrue(Storage::disk('local')->exists('portal-medicos-general.json'));
        $this->assertFalse(Storage::disk('public')->exists($pdf));
        $json = collect(Storage::disk('local')->allFiles('reinicios'))->first(fn ($path) => str_ends_with($path, '/datos.json'));
        $backup = json_decode(Storage::disk('local')->get($json), true);
        $this->assertCount(1, $backup['tablas']['ordens']);
        $this->assertCount(2, $backup['tablas']['medicos']);
        $this->assertSame('PDF anterior', Storage::disk('local')->get(dirname($json).'/'.$pdf));
        $this->assertSame(0600, fileperms(Storage::disk('local')->path($json)) & 0777);
        $this->assertNull($log->fresh()->subject_id);
        $this->assertSame(1, $log->fresh()->properties['referencia_anterior_al_reinicio']['id']);
        $this->assertDatabaseHas('activity_log', ['event' => 'reinicio_operativo']);
        $cierre = app(CierreCajaService::class)->generar('mensual', now()->year, now()->month);
        $this->assertSame(0, $cierre['resumen']['ordenes']);
        $this->assertEquals(0, $cierre['resumen']['ingreso_neto']);
        $cliente = Cliente::create(['nombre' => 'Nuevo', 'apellido' => 'Paciente', 'genero' => 'Femenino']);
        $this->assertSame(1, $cliente->id);
        $this->assertStringEndsWith('001', $cliente->NumeroExp);
        $medico = Medico::create(['nombre' => 'Nuevo médico']);
        $this->assertSame(2, $medico->id);
        $orden = Orden::create(['cliente_id' => $cliente->id, 'medico_id' => $medico->id, 'total' => 10, 'fecha' => now(), 'estado' => 'pendiente']);
        $this->assertSame(1, $orden->id);
        $this->assertNull($log->fresh()->subject);
        $this->assertTrue(Hash::check('ClaveGeneral2026', Medico::find(1)->password));
    }

    public function test_requiere_mantenimiento_fuera_de_testing(): void
    {
        $this->preparar();
        $this->app->instance('env', 'production');
        try {
            $this->artisan('oncosavi:reiniciar-operaciones', ['--ejecutar' => true])->assertFailed();
            $this->assertSame(1, Orden::count());
            $this->assertSame([], Storage::disk('local')->allFiles('reinicios'));
        } finally {
            $this->app->instance('env', 'testing');
        }
    }

    public function test_no_borra_cascadas_de_tablas_que_no_fueron_autorizadas(): void
    {
        $this->preparar();
        Schema::create('dependencia_externa', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('orden_id')->constrained('ordens')->cascadeOnDelete();
        });
        DB::table('dependencia_externa')->insert(['orden_id' => Orden::first()->id]);
        $this->artisan('oncosavi:reiniciar-operaciones', ['--ejecutar' => true])->assertFailed();
        $this->assertSame(1, Orden::count());
        $this->assertSame(1, DB::table('dependencia_externa')->count());
        $this->assertSame([], Storage::disk('local')->allFiles('reinicios'));
    }

    public function test_sesiones_medicas_anteriores_al_reinicio_no_reutilizan_ids(): void
    {
        $this->preparar();
        $general = Medico::where('portal_usuario', 'medicos')->firstOrFail();
        $this->post('/expediente/ingresar', ['usuario' => 'medicos', 'password' => 'ClaveGeneral2026'])->assertRedirect('/expediente');
        $this->get('/expediente')->assertOk();
        $this->artisan('oncosavi:reiniciar-operaciones', ['--ejecutar' => true])->assertSuccessful();
        // Incluso con una cookie cuyo ID coincida con un registro nuevo, se exige entrar de nuevo.
        $this->actingAs(Medico::where('portal_usuario', 'medicos')->firstOrFail(), 'medico')
            ->withSession(['medico_portal_version' => $general->portal_version, 'medico_portal_reinicio' => '']);
        $this->get('/expediente')->assertRedirect('/expediente/ingresar');
        $this->post('/expediente/ingresar', ['usuario' => 'medicos', 'password' => 'ClaveGeneral2026'])->assertRedirect('/expediente');
        $this->get('/expediente')->assertOk();
        $this->assertNotSame('', SesionPortalMedico::version());
    }
}
