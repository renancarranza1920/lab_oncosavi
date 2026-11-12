<?php

namespace Tests\Feature;

use App\ControlCalidad\Models\Area;
use App\ControlCalidad\Models\Lote;
use App\ControlCalidad\Models\Producto;
use App\ControlCalidad\Models\Registro;
use App\ControlCalidad\Services\ControlCalidadService;
use App\ControlCalidad\Services\DemoControlCalidadService;
use App\ControlCalidad\Services\VaciarControlCalidad;
use App\Models\Cliente;
use App\Models\User;
use App\Support\ControlCalidadPermissions as Cci;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ControlCalidadProduccionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        foreach (['access_admin_panel', ...Cci::all()] as $name) {
            Permission::findOrCreate($name, 'web');
        }
        $actor = User::factory()->create();
        $actor->givePermissionTo(['access_admin_panel', ...Cci::all()]);
        $patient = Cliente::create(['nombre' => 'Paciente', 'apellido' => 'Existente', 'genero' => 'Femenino']);
        DB::table('ordens')->insert(['cliente_id' => $patient->id, 'total' => 20, 'fecha' => now()->toDateString(), 'estado' => 'pendiente']);
        app(DemoControlCalidadService::class)->crear($actor->id);
        $area = Area::create(['nombre' => 'Área configurada manualmente', 'equipo' => 'Equipo del laboratorio']);
        $product = Producto::create(['nombre' => 'Producto de prueba manual']);
        $lot = Lote::create(['producto_id' => $product->id, 'nombre' => 'Control manual', 'numero' => 'MANUAL-01', 'caduca_el' => now()->addMonths(6)->toDateString()]);
        $prueba = app(ControlCalidadService::class)->guardarPrueba([
            'area_id' => $area->id, 'nombre' => 'GLUCOSA', 'unidad' => 'mg/dL',
            'niveles' => [['nombre' => 'Medio', 'lote_id' => $lot->id, 'minimo' => '80', 'maximo' => '120']],
        ], $actor);
        app(ControlCalidadService::class)->guardar(['area_id' => $area->id, 'resultados' => [['nivel_id' => $prueba->niveles->sole()->id, 'valor' => '97,5']]], $actor);
        Registro::first()->delete();
    }

    public function test_vaciar_qa_elimina_toda_su_configuracion_y_mediciones_y_conserva_el_resto_del_sistema(): void
    {
        $before = $this->otrosDatos();
        $qaBefore = [];
        foreach (VaciarControlCalidad::TABLAS as $table) {
            $qaBefore[$table] = DB::table($table)->count();
        }

        $result = app(VaciarControlCalidad::class)->ejecutar();

        $this->assertSame($qaBefore, $result['conteos']);
        $this->assertFileExists($result['respaldo']);
        $this->assertSame(0600, fileperms($result['respaldo']) & 0777);
        $backup = json_decode(file_get_contents($result['respaldo']), true, 512, JSON_THROW_ON_ERROR);
        foreach ($qaBefore as $table => $count) {
            $this->assertSame(0, DB::table($table)->count());
            $this->assertCount($count, $backup['tablas'][$table]);
        }
        $this->assertCount(33, $backup['tablas']['cci_registros']);
        $this->assertNotNull($backup['identificacion_demo']);
        Storage::disk('local')->assertMissing('cci-demo.json');
        $this->assertEquals($before, $this->otrosDatos());
        $second = app(VaciarControlCalidad::class)->ejecutar();
        $this->assertNull($second['respaldo']);
        $this->assertSame(0, array_sum($second['conteos']));
        $this->assertEquals($before, $this->otrosDatos());
    }

    public function test_la_limpieza_solo_se_ejecuta_al_pedirla_expresamente(): void
    {
        $before = DB::table('cci_registros')->get()->toArray();
        $this->artisan('oncosavi:cci-vaciar')->assertFailed();
        $this->assertEquals($before, DB::table('cci_registros')->get()->toArray());
        $this->artisan('oncosavi:cci-vaciar', ['--force' => true])->assertSuccessful();
        foreach (VaciarControlCalidad::TABLAS as $table) {
            $this->assertSame(0, DB::table($table)->count());
        }
    }

    public function test_no_se_borra_qa_si_falla_el_respaldo_privado(): void
    {
        $disk = Storage::disk('local');
        $mock = \Mockery::mock($disk);
        $mock->shouldReceive('put')->once()->andReturnFalse();
        Storage::shouldReceive('disk')->with('local')->andReturn($mock);
        $before = DB::table('cci_registros')->get()->toArray();
        $this->artisan('oncosavi:cci-vaciar', ['--force' => true])->assertFailed();
        $this->assertEquals($before, DB::table('cci_registros')->get()->toArray());
        $this->assertSame(3, DB::table('cci_areas')->count());
    }

    private function otrosDatos(): array
    {
        $data = [];
        foreach (Schema::getTableListing(schemaQualified: false) as $table) {
            if (in_array($table, VaciarControlCalidad::TABLAS, true) || str_starts_with($table, 'sqlite_')) {
                continue;
            }
            $data[$table] = DB::table($table)->get()->toArray();
        }

        return $data;
    }
}
