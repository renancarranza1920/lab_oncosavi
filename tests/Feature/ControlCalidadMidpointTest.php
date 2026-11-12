<?php

namespace Tests\Feature;

use App\ControlCalidad\Filament\Pages\Mantenimiento;
use App\ControlCalidad\Models\Area;
use App\ControlCalidad\Models\Lote;
use App\ControlCalidad\Models\Producto;
use App\ControlCalidad\Models\Prueba;
use App\ControlCalidad\Services\ControlCalidadService;
use App\Models\User;
use App\Support\ControlCalidadPermissions as Cci;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ControlCalidadMidpointTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    private Area $area;

    private Lote $lote;

    private ?\Closure $restoreRepeaters = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->restoreRepeaters = Repeater::fake();
        foreach (['access_admin_panel', ...Cci::all()] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $this->actor = User::factory()->create(['name' => 'Daniela Martínez']);
        $this->actor->givePermissionTo(['access_admin_panel', ...Cci::all()]);
        $this->actingAs($this->actor);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
        $this->area = Area::create(['nombre' => 'Hematología', 'equipo' => 'Mindray BC-20']);
        $producto = Producto::create(['nombre' => 'Control hematológico']);
        $this->lote = Lote::create(['producto_id' => $producto->id, 'nombre' => 'Control hematológico',
            'numero' => 'BC-2611', 'caduca_el' => now()->addYear()->toDateString()]);
    }

    protected function tearDown(): void
    {
        if ($this->restoreRepeaters !== null) {
            ($this->restoreRepeaters)();
        }
        parent::tearDown();
    }

    #[DataProvider('rangos')]
    public function test_el_servidor_calcula_el_centro_del_rango_y_no_necesita_valor_esperado(string $minimo, string $maximo, string $esperado): void
    {
        $prueba = app(ControlCalidadService::class)->guardarPrueba($this->datos($minimo, $maximo), $this->actor);
        $control = $prueba->niveles->sole()->valorControlActual;

        $this->assertSame($esperado, $control->esperado);
    }

    public static function rangos(): array
    {
        return [
            'coma decimal' => ['5,7', '6,7', '6.200000'],
            'mínimo cero' => ['0', '11', '5.500000'],
            'rango igual' => ['9,125', '9.125', '9.125000'],
            'negativos' => ['-5.1', '-2,3', '-3.700000'],
            'centro cero' => ['-8', '8', '0.000000'],
            'redondeo positivo' => ['0', '0.000001', '0.000001'],
            'redondeo negativo' => ['-0.000001', '0', '-0.000001'],
        ];
    }

    public function test_el_calculo_conserva_los_seis_decimales_significativos_con_valores_grandes(): void
    {
        $this->assertSame('999999999999.999999', ControlCalidadService::puntoMedio('999999999999.999998', '1000000000000'));
    }

    public function test_ignora_el_valor_esperado_enviado_y_preserva_las_referencias_de_resultados_anteriores(): void
    {
        $service = app(ControlCalidadService::class);
        $data = $this->datos('5', '9');
        $data['niveles'][0]['esperado'] = ['valor' => '999999'];
        $prueba = $service->guardarPrueba($data, $this->actor);
        $nivel = $prueba->niveles->sole();
        $this->assertSame('7.000000', $nivel->valorControlActual->esperado);
        $registro = $service->guardar(['area_id' => $this->area->id,
            'resultados' => [['nivel_id' => $nivel->id, 'valor' => '7,1']]], $this->actor);
        $before = $registro->resultados->sole()->getAttributes();
        $data['niveles'][0] = ['id' => $nivel->id, 'nombre' => 'Nivel normal', 'lote_id' => $this->lote->id,
            'minimo' => '6', 'maximo' => '10', 'esperado' => '-100'];
        $service->guardarPrueba($data, $this->actor, $prueba);

        $this->assertSame('8.000000', $nivel->fresh()->valorControlActual->esperado);
        $this->assertSame($before, $registro->resultados()->sole()->getAttributes());
    }

    public function test_rechaza_limites_invertidos_sin_guardar_una_prueba(): void
    {
        try {
            app(ControlCalidadService::class)->guardarPrueba($this->datos('9', '5'), $this->actor);
            $this->fail('Debe rechazar un mínimo mayor que el máximo.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('niveles.0.maximo', $exception->errors());
        }
        $this->assertSame(0, Prueba::count());
    }

    public function test_el_formulario_muestra_el_calculo_no_editable_y_lo_actualiza_al_cambiar_los_limites(): void
    {
        $component = Livewire::test(Mantenimiento::class)->call('cambiarSeccion', 'pruebas')
            ->mountTableAction('crear')->setTableActionData($this->datos('5,7', '6,7'));
        $component->set('mountedTableActionsData.0.niveles.0.minimo', '5,7')
            ->set('mountedTableActionsData.0.niveles.0.maximo', '6,7')
            ->assertSet('mountedTableActionsData.0.niveles.0.esperado', '6,2');

        $fields = $component->instance()->getMountedTableActionForm()->getFlatFields();
        $field = collect($fields)->first(fn ($field) => $field->getName() === 'esperado');
        $this->assertNotNull($field);
        $this->assertTrue($field->isReadOnly());
        $this->assertFalse($field->isDehydrated());

        $component->set('mountedTableActionsData.0.niveles.0.maximo', '7,7')
            ->assertSet('mountedTableActionsData.0.niveles.0.esperado', '6,7')
            ->call('callMountedTableAction')->assertHasNoTableActionErrors();
        $this->assertSame('6.700000', Prueba::sole()->niveles->sole()->valorControlActual->esperado);
    }

    private function datos(string $minimo, string $maximo): array
    {
        return ['area_id' => $this->area->id, 'nombre' => 'HEMOGLOBINA', 'unidad' => 'g/dL',
            'niveles' => [['nombre' => 'Nivel normal', 'lote_id' => $this->lote->id, 'minimo' => $minimo, 'maximo' => $maximo]]];
    }
}
