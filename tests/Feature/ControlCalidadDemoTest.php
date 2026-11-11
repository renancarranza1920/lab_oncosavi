<?php

namespace Tests\Feature;

use App\ControlCalidad\Models\Area;
use App\ControlCalidad\Models\Lote;
use App\ControlCalidad\Models\Nivel;
use App\ControlCalidad\Models\Producto;
use App\ControlCalidad\Models\Prueba;
use App\ControlCalidad\Models\Registro;
use App\ControlCalidad\Models\Resultado;
use App\ControlCalidad\Models\ValorControl;
use App\ControlCalidad\Services\ControlCalidadService;
use App\ControlCalidad\Services\DemoControlCalidadService;
use App\Models\Cliente;
use App\Models\User;
use App\Support\ControlCalidadPermissions as Cci;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ControlCalidadDemoTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->travelTo(Carbon::parse('2026-11-11 10:00:00', 'America/El_Salvador'));
        foreach (['access_admin_panel', ...Cci::all()] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $this->actor = User::factory()->create(['name' => 'Daniela Martínez']);
        $this->actor->givePermissionTo(['access_admin_panel', ...Cci::all()]);
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    public function test_crear_añade_controles_identificados_y_lecturas_para_los_tres_periodos_sin_tocar_usuarios(): void
    {
        $before = $this->clinicalSnapshot();
        $this->artisan('oncosavi:cci-demo-crear', ['--usuario' => (string) $this->actor->id])->assertSuccessful();
        $this->assertSame(2, Area::count());
        $this->assertSame(2, Producto::count());
        $this->assertSame(6, Lote::count());
        $this->assertSame(8, Prueba::count());
        $this->assertSame(24, Nivel::count());
        $this->assertSame(24, ValorControl::count());
        $this->assertSame(30, Registro::count());
        $this->assertSame(360, Resultado::count());
        $this->assertSame(2, Registro::whereDate('fecha_hora', now()->toDateString())->count());
        $this->assertSame(15, Registro::query()->selectRaw('DATE(fecha_hora) as dia')->distinct()->get()->count());
        $this->assertSame('2026-10-28', Registro::oldest('fecha_hora')->first()->fecha_hora->toDateString());
        $this->assertSame(0, Registro::where('registrado_por_id', '!=', $this->actor->id)->count());
        foreach (Area::all() as $area) {
            $this->assertStringStartsWith('[DEMO]', $area->nombre);
        }
        foreach (ValorControl::all() as $control) {
            $this->assertEqualsWithDelta(((float) $control->minimo + (float) $control->maximo) / 2, (float) $control->esperado, 0.000001);
        }
        $this->assertTrue(Resultado::whereColumn('valor', '>', 'maximo')->exists());
        $this->assertEquals($before, $this->clinicalSnapshot());
        Storage::disk('local')->assertExists('cci-demo.json');
        $this->assertSame(0600, fileperms(Storage::disk('local')->path('cci-demo.json')) & 0777);
    }

    public function test_repetir_crear_no_duplica_ni_reemplaza_las_pruebas_que_el_usuario_edito(): void
    {
        $demo = app(DemoControlCalidadService::class);
        $this->assertTrue($demo->crear()['creado']);
        $registro = Registro::first();
        $registro->update(['observaciones' => 'Prueba manual del laboratorista.']);
        $resultado = $registro->resultados()->first();
        $resultado->update(['valor' => '12.345', 'valor_original' => '12,345']);
        $before = $this->demoSnapshot();

        $this->assertFalse($demo->crear()['creado']);
        $this->assertEquals($before, $this->demoSnapshot());
    }

    public function test_limpiar_borra_solo_la_demostracion_y_conserva_controles_y_datos_clinicos_reales(): void
    {
        $patient = Cliente::create(['nombre' => 'Paciente', 'apellido' => 'Existente', 'genero' => 'Femenino']);
        DB::table('ordens')->insert(['cliente_id' => $patient->id, 'total' => 20, 'fecha' => now()->toDateString(), 'estado' => 'pendiente']);
        [$area, $registro] = $this->crearControlReal();
        $beforeClinical = $this->clinicalSnapshot();
        $beforeReal = $registro->load(['area', 'laboratorista', 'registradoPor', 'resultados'])->toArray();
        $demo = app(DemoControlCalidadService::class);
        $demo->crear();
        // Incluye una lectura borrada desde la interfaz: su retiro sigue siendo sólo de la demostración.
        Registro::where('area_id', '!=', $area->id)->first()->delete();

        $this->artisan('oncosavi:cci-demo-limpiar')->assertSuccessful();
        $this->assertSame(1, Area::count());
        $this->assertSame(1, Producto::count());
        $this->assertSame(1, Lote::count());
        $this->assertSame(1, Prueba::count());
        $this->assertSame(1, Nivel::count());
        $this->assertSame(1, ValorControl::count());
        $this->assertSame(1, Registro::withTrashed()->count());
        $this->assertSame(1, Resultado::count());
        $this->assertEquals($beforeClinical, $this->clinicalSnapshot());
        $this->assertEquals($beforeReal, $registro->fresh()->load(['area', 'laboratorista', 'registradoPor', 'resultados'])->toArray());
        $this->assertSame(0, $demo->limpiar()['registros']);
        $this->assertEquals($beforeReal, $registro->fresh()->load(['area', 'laboratorista', 'registradoPor', 'resultados'])->toArray());
    }

    public function test_limpiar_rechaza_todo_si_un_lote_de_demostracion_se_usa_en_un_area_real(): void
    {
        $demo = app(DemoControlCalidadService::class);
        $demo->crear();
        $lote = Lote::first();
        $area = Area::create(['nombre' => 'Inmunología', 'equipo' => 'Equipo del laboratorio']);
        app(ControlCalidadService::class)->guardarPrueba([
            'area_id' => $area->id, 'nombre' => 'PROTEÍNA C REACTIVA', 'unidad' => 'mg/L',
            'niveles' => [['nombre' => 'Normal', 'lote_id' => $lote->id, 'minimo' => '8', 'maximo' => '12']],
        ], $this->actor);
        $before = $this->demoSnapshot();

        $this->artisan('oncosavi:cci-demo-limpiar')->assertFailed();
        $this->assertEquals($before, $this->demoSnapshot());
    }

    public function test_limpiar_rechaza_todo_si_una_lectura_de_otra_area_referencia_un_nivel_de_demostracion(): void
    {
        $demo = app(DemoControlCalidadService::class);
        $demo->crear();
        [$area, $registro] = $this->crearControlReal();
        $resultado = Resultado::where('registro_id', '!=', $registro->id)->first()->replicate();
        $resultado->registro_id = $registro->id;
        $resultado->save();
        $before = $this->demoSnapshot();

        try {
            $demo->limpiar();
            $this->fail('No debe borrar una referencia desde un área real.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('área real', $exception->getMessage());
        }
        $this->assertEquals($before, $this->demoSnapshot());
    }

    public function test_crear_no_añade_usuarios_y_rechaza_un_usuario_sin_permisos(): void
    {
        $withoutPermissions = User::factory()->create();
        $this->artisan('oncosavi:cci-demo-crear', ['--usuario' => (string) $withoutPermissions->id])->assertFailed();
        $this->assertSame(0, Area::count());
        $this->assertSame(0, Producto::count());
        $this->assertSame(2, User::count());
        $this->artisan('oncosavi:cci-demo-crear', ['--usuario' => 'abc'])->assertFailed();
        $this->assertSame(0, Area::count());
    }

    public function test_limpiar_identifica_areas_y_productos_renombrados_y_conserva_padres_reales_que_adoptaron_lotes_demo(): void
    {
        [$realArea, $realRegistro] = $this->crearControlReal();
        $realLote = Lote::first();
        $realProducto = $realLote->producto;
        $realBefore = $realRegistro->load(['area', 'laboratorista', 'registradoPor', 'resultados'])->toArray();
        $demo = app(DemoControlCalidadService::class);
        $demo->crear();
        Area::where('id', '!=', $realArea->id)->first()->update(['nombre' => 'Área de pruebas manuales', 'equipo' => 'Equipo modificado']);
        Producto::where('id', '!=', $realProducto->id)->first()->update(['nombre' => 'Producto renombrado durante la prueba']);
        $demoLotes = Lote::where('id', '!=', $realLote->id)->orderBy('id')->get();
        $demoLotes[0]->update(['producto_id' => $realProducto->id]);
        $adopted = Producto::create(['nombre' => 'Producto nuevo del laboratorio']);
        $demoLotes[1]->update(['producto_id' => $adopted->id]);
        $this->assertFalse($demo->crear()['creado']);

        $demo->limpiar();

        $this->assertSame([$realArea->id], Area::pluck('id')->all());
        $this->assertSame([$realLote->id], Lote::pluck('id')->all());
        $this->assertSame([$realProducto->id, $adopted->id], Producto::orderBy('id')->pluck('id')->all());
        $this->assertEquals($realBefore, $realRegistro->fresh()->load(['area', 'laboratorista', 'registradoPor', 'resultados'])->toArray());
        Storage::disk('local')->assertMissing('cci-demo.json');
    }

    public function test_un_manifest_de_otra_base_no_puede_borrar_ningun_dato(): void
    {
        $demo = app(DemoControlCalidadService::class);
        $demo->crear();
        $manifest = json_decode(Storage::disk('local')->get('cci-demo.json'), true);
        $manifest['context'] = 'contexto-de-otra-base';
        Storage::disk('local')->put('cci-demo.json', json_encode($manifest));
        $before = $this->demoSnapshot();

        $this->artisan('oncosavi:cci-demo-limpiar')->assertFailed();
        $this->assertEquals($before, $this->demoSnapshot());
    }

    public function test_no_borra_si_un_id_original_ahora_pertenece_a_otra_fila(): void
    {
        app(DemoControlCalidadService::class)->crear();
        DB::table('cci_areas')->where('id', Area::first()->id)->update(['created_at' => '2026-11-01 00:00:00']);
        $before = $this->demoSnapshot();

        $this->artisan('oncosavi:cci-demo-limpiar')->assertFailed();
        $this->assertEquals($before, $this->demoSnapshot());
    }

    public function test_un_manifest_residual_sin_filas_originales_se_retira_sin_borrar_filas_reales(): void
    {
        $demo = app(DemoControlCalidadService::class);
        $demo->crear();
        $manifest = Storage::disk('local')->get('cci-demo.json');
        $demo->limpiar();
        $this->crearControlReal();
        Storage::disk('local')->put('cci-demo.json', $manifest);
        $before = $this->demoSnapshot();

        $this->assertSame(0, $demo->limpiar()['registros']);
        $this->assertEquals($before, $this->demoSnapshot());
        Storage::disk('local')->assertMissing('cci-demo.json');
    }

    public function test_no_borra_ni_recrea_datos_sin_el_manifest_de_identificacion(): void
    {
        app(DemoControlCalidadService::class)->crear();
        Storage::disk('local')->delete('cci-demo.json');
        $before = $this->demoSnapshot();

        $this->artisan('oncosavi:cci-demo-limpiar')->assertFailed();
        $this->artisan('oncosavi:cci-demo-crear')->assertFailed();
        $this->assertEquals($before, $this->demoSnapshot());
    }

    private function crearControlReal(): array
    {
        $area = Area::create(['nombre' => 'Química', 'equipo' => 'Analizador del laboratorio']);
        $producto = Producto::create(['nombre' => 'Control multianalito vigente']);
        $lote = Lote::create(['producto_id' => $producto->id, 'nombre' => 'Lote del laboratorio', 'numero' => 'REAL-2611', 'caduca_el' => '2027-02-28']);
        $prueba = app(ControlCalidadService::class)->guardarPrueba([
            'area_id' => $area->id, 'nombre' => 'GLUCOSA', 'unidad' => 'mg/dL',
            'niveles' => [['nombre' => 'Normal', 'lote_id' => $lote->id, 'minimo' => '90', 'maximo' => '110']],
        ], $this->actor);
        $registro = app(ControlCalidadService::class)->guardar([
            'area_id' => $area->id, 'observaciones' => 'Control existente del laboratorio.',
            'resultados' => [['nivel_id' => $prueba->niveles->first()->id, 'valor' => '101,5']],
        ], $this->actor);

        return [$area, $registro];
    }

    private function demoSnapshot(): array
    {
        $data = [];
        foreach (['areas', 'productos', 'lotes', 'pruebas', 'niveles', 'valores_control', 'registros', 'resultados'] as $table) {
            $data[$table] = DB::table('cci_'.$table)->orderBy('id')->get()->toArray();
        }

        return $data;
    }

    private function clinicalSnapshot(): array
    {
        $data = [];
        foreach (['users', 'clientes', 'ordens', 'pruebas', 'examens', 'resultados', 'roles', 'permissions'] as $table) {
            $data[$table] = DB::table($table)->orderBy('id')->get()->toArray();
        }

        return $data;
    }
}
