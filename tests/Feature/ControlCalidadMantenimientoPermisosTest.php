<?php

namespace Tests\Feature;

use App\ControlCalidad\Filament\Pages\Mantenimiento;
use App\ControlCalidad\Models\Area;
use App\ControlCalidad\Models\Lote;
use App\ControlCalidad\Models\Producto;
use App\ControlCalidad\Models\Prueba;
use App\ControlCalidad\Services\ControlCalidadService;
use App\Filament\Resources\RoleResource\Pages\EditRole;
use App\Models\User;
use App\Support\ControlCalidadPermissions as Cci;
use Database\Seeders\RolesPermisosSeeder;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ControlCalidadMantenimientoPermisosTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Area $area;

    private Lote $lote;

    private Prueba $prueba;

    private \Closure $restoreRepeaters;

    protected function setUp(): void
    {
        parent::setUp();
        $this->restoreRepeaters = Repeater::fake();
        $this->seed(RolesPermisosSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        $this->actingAs($this->admin);
        $this->area = Area::create(['nombre' => 'Química', 'equipo' => 'Analizador de química']);
        $producto = Producto::create(['nombre' => 'Control multianalito']);
        $this->lote = Lote::create(['producto_id' => $producto->id, 'nombre' => $producto->nombre, 'numero' => 'QC-2026', 'caduca_el' => now()->addYear()->toDateString()]);
        $this->prueba = app(ControlCalidadService::class)->guardarPrueba($this->datosPrueba(), $this->admin);
    }

    protected function tearDown(): void
    {
        ($this->restoreRepeaters)();
        parent::tearDown();
    }

    public static function secciones(): array
    {
        return [['areas'], ['productos'], ['pruebas']];
    }

    public static function acciones(): array
    {
        $cases = [];
        foreach (['areas', 'productos', 'pruebas'] as $section) {
            foreach (['create', 'edit', 'delete'] as $action) {
                $cases["$section $action"] = [$section, $action];
            }
        }

        return $cases;
    }

    #[DataProvider('secciones')]
    public function test_consultar_mantenimiento_no_permite_agregar_editar_o_eliminar(string $section): void
    {
        $user = $this->usuario([]);
        $this->actingAs($user);
        $record = $this->objetivo($section);
        $before = $this->snapshot();
        $page = Livewire::test(Mantenimiento::class)->call('cambiarSeccion', $section);
        foreach (['crear', 'editar', 'eliminar'] as $action) {
            $page->assertTableActionHidden($action, $action === 'crear' ? null : $record)
                ->call('mountTableAction', $action, $action === 'crear' ? null : (string) $record->id)
                ->call('callMountedTableAction');
        }
        $this->assertEquals($before, $this->snapshot());
    }

    #[DataProvider('acciones')]
    public function test_cada_permiso_autoriza_solo_su_accion_y_su_apartado(string $section, string $action): void
    {
        $user = $this->usuario([Cci::maintenancePermission($section, $action)]);
        $this->actingAs($user);
        $record = $this->objetivo($section);
        $page = Livewire::test(Mantenimiento::class)->call('cambiarSeccion', $section);
        foreach (['create' => 'crear', 'edit' => 'editar', 'delete' => 'eliminar'] as $operation => $name) {
            $method = $operation === $action ? 'assertTableActionVisible' : 'assertTableActionHidden';
            $page->$method($name, $operation === 'create' ? null : $record);
        }
        foreach (array_diff(['areas', 'productos', 'pruebas'], [$section]) as $other) {
            $otherRecord = $this->objetivo($other);
            Livewire::test(Mantenimiento::class)->call('cambiarSeccion', $other)
                ->assertTableActionHidden('crear')->assertTableActionHidden('editar', $otherRecord)->assertTableActionHidden('eliminar', $otherRecord);
        }
        if ($action === 'delete') {
            $page->callTableAction('eliminar', $record);
            $this->assertModelMissing($record);
            if ($record instanceof Lote) {
                $this->assertDatabaseMissing('cci_productos', ['id' => $record->producto_id]);
            }
        } else {
            $data = $section === 'areas' ? ['nombre' => 'Área actualizada', 'equipo' => 'Equipo actualizado']
                : ($section === 'productos' ? ['producto_nombre' => 'Control nuevo', 'numero' => 'QC-OTRO', 'caduca_el' => now()->addYear()->toDateString()]
                    : $this->datosPrueba('GLUCOSA NUEVA'));
            if ($action === 'edit' && $record instanceof Prueba) {
                $data['niveles'][0]['id'] = $record->niveles->sole()->id;
            }
            $page->callTableAction($action === 'create' ? 'crear' : 'editar', $action === 'create' ? null : $record, data: $data)
                ->assertHasNoTableActionErrors();
            $this->assertDatabaseHas($record->getTable(), $section === 'productos' ? ['numero' => 'QC-OTRO'] : ['nombre' => $data['nombre']]);
        }
    }

    #[DataProvider('secciones')]
    public function test_eliminar_no_retira_configuracion_vinculada_ni_mediciones_historicas(string $section): void
    {
        $registro = app(ControlCalidadService::class)->guardar([
            'area_id' => $this->area->id,
            'resultados' => [['nivel_id' => $this->prueba->niveles->sole()->id, 'valor' => '100']],
        ], $this->admin);
        $registro->delete();
        $record = match ($section) {
            'areas' => $this->area, 'productos' => $this->lote, default => $this->prueba
        };
        $before = $this->snapshot();
        Livewire::test(Mantenimiento::class)->call('cambiarSeccion', $section)->callTableAction('eliminar', $record)->assertNotified('No se puede eliminar');
        $this->assertEquals($before, $this->snapshot());
    }

    public function test_los_servicios_rechazan_permisos_de_solo_consulta_sin_alterar_datos(): void
    {
        $user = $this->usuario([]);
        $before = $this->snapshot();
        $service = app(ControlCalidadService::class);
        foreach ([fn () => $service->guardarPrueba($this->datosPrueba('OTRA'), $user),
            fn () => $service->guardarPrueba($this->datosPrueba(), $user, $this->prueba),
            fn () => $service->eliminarCatalogo($this->area, $user)] as $attempt) {
            try {
                $attempt();
                $this->fail('La operación sin permiso debe ser rechazada.');
            } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
                $this->assertSame(403, $exception->getStatusCode());
            }
        }
        $this->assertEquals($before, $this->snapshot());
    }

    public function test_la_migracion_conserva_accesos_anteriores_sin_convertir_la_herencia_del_rol_en_permisos_directos(): void
    {
        $legacy = Role::findOrCreate('Mantenimiento anterior', 'web');
        $legacy->givePermissionTo(['access_admin_panel', Cci::VIEW, Cci::MAINTAIN]);
        $inherited = User::factory()->create();
        $inherited->assignRole($legacy);
        $direct = $this->usuario([]);
        $reader = Role::findOrCreate('Consulta sin mantenimiento', 'web');
        $reader->givePermissionTo(Cci::VIEW);
        $before = $this->snapshot();
        $migration = require database_path('migrations/2026_11_12_000002_add_granular_quality_control_maintenance_permissions.php');
        $migration->up();
        $migration->up();
        $this->assertTrue($legacy->fresh()->hasAllPermissions(Cci::maintenanceActions()));
        $this->assertTrue($inherited->fresh()->hasAllPermissions(Cci::maintenanceActions()));
        $this->assertCount(0, $inherited->fresh()->permissions);
        $this->assertTrue($direct->fresh()->hasAllPermissions(Cci::maintenanceActions()));
        $this->assertFalse($reader->fresh()->hasAnyPermission(Cci::maintenanceActions()));
        $this->assertEquals($before, $this->snapshot());
    }

    public function test_laboratorista_inicia_completo_pero_el_administrador_puede_retirar_las_acciones_de_areas(): void
    {
        $role = Role::findByName('Laboratorista', 'web');
        $this->assertTrue($role->hasAllPermissions(Cci::all()));
        Livewire::test(EditRole::class, ['record' => $role->id])
            ->fillForm(['cci_permissions_2' => []])->call('save')->assertHasNoFormErrors();
        $this->assertFalse($role->fresh()->hasAnyPermission([Cci::CREATE_AREA, Cci::EDIT_AREA, Cci::DELETE_AREA]));
        $this->assertTrue($role->fresh()->hasAllPermissions([Cci::MAINTAIN, Cci::CREATE_PRODUCT, Cci::EDIT_PRODUCT, Cci::DELETE_PRODUCT, Cci::CREATE_TEST, Cci::EDIT_TEST, Cci::DELETE_TEST]));
        $user = User::factory()->create();
        $user->assignRole($role);
        $this->actingAs($user);
        Livewire::test(Mantenimiento::class)->assertTableActionHidden('crear')->assertTableActionHidden('editar', $this->area)->assertTableActionHidden('eliminar', $this->area);
        $this->assertFalse($role->fresh()->hasPermissionTo(Cci::CREATE_AREA));
    }

    private function usuario(array $permissions): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['access_admin_panel', Cci::VIEW, Cci::MAINTAIN, ...$permissions]);

        return $user;
    }

    private function datosPrueba(string $name = 'GLUCOSA'): array
    {
        return ['area_id' => $this->area->id, 'nombre' => $name, 'unidad' => 'mg/dL',
            'niveles' => [['nombre' => 'Medio', 'lote_id' => $this->lote->id, 'minimo' => '80', 'maximo' => '120']]];
    }

    private function objetivo(string $section): Model
    {
        if ($section === 'areas') {
            return Area::firstOrCreate(['nombre' => 'Área sin uso', 'equipo' => 'Equipo sin uso']);
        }
        if ($section === 'productos') {
            $product = Producto::firstOrCreate(['nombre' => 'Producto sin uso']);

            return Lote::firstOrCreate(['producto_id' => $product->id, 'numero' => 'LIBRE'], ['nombre' => $product->nombre, 'caduca_el' => now()->addYear()->toDateString()]);
        }

        return $this->prueba;
    }

    private function snapshot(): array
    {
        $rows = [];
        foreach (['cci_areas', 'cci_productos', 'cci_lotes', 'cci_pruebas', 'cci_niveles', 'cci_valores_control', 'cci_registros', 'cci_resultados'] as $table) {
            $rows[$table] = DB::table($table)->orderBy('id')->get()->toArray();
        }

        return $rows;
    }
}
