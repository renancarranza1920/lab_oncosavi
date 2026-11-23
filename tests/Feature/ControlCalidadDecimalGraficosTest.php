<?php

namespace Tests\Feature;

use App\ControlCalidad\Filament\Pages\Mantenimiento;
use App\ControlCalidad\Models\Area;
use App\ControlCalidad\Models\Lote;
use App\ControlCalidad\Models\Producto;
use App\ControlCalidad\Services\ControlCalidadService;
use App\ControlCalidad\Services\DatosGraficoControlCalidad;
use App\ControlCalidad\Services\ExportadorControlCalidad;
use App\ControlCalidad\Services\GraficoControlCalidadSvg;
use App\ControlCalidad\Services\NumeroControlCalidad;
use App\Models\User;
use App\Support\ControlCalidadPermissions as Cci;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ControlCalidadDecimalGraficosTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    private Area $area;

    private Lote $lote;

    private \Closure $restoreRepeaters;

    protected function setUp(): void
    {
        parent::setUp();
        $this->restoreRepeaters = Repeater::fake();
        foreach (['access_admin_panel', ...Cci::all()] as $name) {
            Permission::findOrCreate($name, 'web');
        }
        $this->actor = User::factory()->create();
        $this->actor->givePermissionTo(['access_admin_panel', ...Cci::all()]);
        $this->actingAs($this->actor);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
        $this->area = Area::create(['nombre' => 'Química clínica', 'equipo' => 'Semiautomatizado']);
        $producto = Producto::create(['nombre' => 'Control normal']);
        $this->lote = Lote::create(['producto_id' => $producto->id, 'nombre' => $producto->nombre, 'numero' => '2594', 'caduca_el' => now()->addYear()->toDateString()]);
    }

    protected function tearDown(): void
    {
        ($this->restoreRepeaters)();
        parent::tearDown();
    }

    public static function decimales(): array
    {
        return [
            ['3,61', '4.87', '3,765', '3.610000', '4.870000', '4.240000', '3.770000'],
            ['3.61', '4,87', '3.765', '3.610000', '4.870000', '4.240000', '3.770000'],
            ['0', '0,01', '0.004', '0.000000', '0.010000', '0.010000', '0.000000'],
            ['-1,23', '1.23', '-0,005', '-1.230000', '1.230000', '0.000000', '-0.010000'],
        ];
    }

    #[DataProvider('decimales')]
    public function test_pruebas_y_lecturas_aceptan_ambos_separadores_y_guardan_dos_decimales(string $min, string $max, string $value, string $storedMin, string $storedMax, string $expected, string $storedValue): void
    {
        $service = app(ControlCalidadService::class);
        $prueba = $service->guardarPrueba($this->datos($min, $max), $this->actor);
        $nivel = $prueba->niveles->sole();
        $control = $nivel->valorControlActual;
        $this->assertSame($storedMin, $control->minimo);
        $this->assertSame($storedMax, $control->maximo);
        $this->assertSame($expected, $control->esperado);
        $registro = $service->guardar(['area_id' => $this->area->id, 'resultados' => [['nivel_id' => $nivel->id, 'valor' => $value]]], $this->actor);
        $this->assertSame($storedValue, $registro->resultados->sole()->valor);
        $this->assertSame($value, $registro->resultados->sole()->valor_original);
    }

    public function test_editar_referencias_antiguas_muestra_minimo_maximo_y_esperado_con_dos_decimales_sin_reescribir_la_base(): void
    {
        $prueba = app(ControlCalidadService::class)->guardarPrueba($this->datos('3.61', '4.87'), $this->actor);
        $control = $prueba->niveles->sole()->valorControlActual;
        $control->update(['minimo' => '3.614321', 'maximo' => '4.874321', 'esperado' => '4.244321']);
        $before = $control->fresh()->getAttributes();
        $page = Livewire::test(Mantenimiento::class)->call('cambiarSeccion', 'pruebas')->mountTableAction('editar', $prueba);
        $values = array_values($page->get('mountedTableActionsData')[0]['niveles'])[0];
        $this->assertSame('3,61', $values['minimo']);
        $this->assertSame('4,87', $values['maximo']);
        $this->assertSame('4,24', $values['esperado']);
        $fields = collect($page->instance()->getMountedTableActionForm()->getFlatFields());
        $names = $fields->map(fn ($field) => $field->getName())->filter(fn ($name) => in_array($name, ['minimo', 'maximo', 'esperado'], true))->values()->all();
        $this->assertSame(['minimo', 'maximo', 'esperado'], $names);
        $this->assertTrue($fields->first(fn ($field) => $field->getName() === 'esperado')->isDisabled());
        $this->assertSame($before, $control->fresh()->getAttributes());
    }

    public function test_una_medicion_conserva_sus_tres_lineas_de_referencia_y_cuadricula_en_el_pdf(): void
    {
        $service = app(ControlCalidadService::class);
        $prueba = $service->guardarPrueba($this->datos('3.61', '4.87'), $this->actor);
        $nivel = $prueba->niveles->sole();
        $service->guardar(['area_id' => $this->area->id, 'resultados' => [['nivel_id' => $nivel->id, 'valor' => '3,76']]], $this->actor);
        $chart = app(DatosGraficoControlCalidad::class)->obtener($nivel->id);
        $options = app(DatosGraficoControlCalidad::class)->opciones($chart);
        $this->assertCount(1, $chart['labels']);
        $this->assertSame(['mode' => 'nearest', 'intersect' => true], $options['interaction']);
        $this->assertFalse($options['plugins']['legend']['display']);
        $this->assertCount(4, array_unique(array_column($chart['datasets'], 'borderColor')));
        $image = app(GraficoControlCalidadSvg::class)->imagenes($chart)[0];
        $svg = base64_decode(explode(',', $image['src'], 2)[1]);
        $doc = new \DOMDocument;
        $this->assertTrue($doc->loadXML($svg));
        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('s', 'http://www.w3.org/2000/svg');
        $this->assertSame(6, $xpath->query('//s:line[@data-grid="true"]')->length);
        foreach ([1, 2, 3] as $index) {
            $line = $xpath->query('//s:polyline[@data-series="'.$index.'"]')->item(0);
            $this->assertNotNull($line);
            $points = explode(' ', $line->getAttribute('points'));
            $this->assertCount(2, $points);
            $this->assertNotSame(explode(',', $points[0])[0], explode(',', $points[1])[0]);
            $this->assertSame(explode(',', $points[0])[1], explode(',', $points[1])[1]);
        }
        $this->assertSame(1, $xpath->query('//s:circle')->length);
        $this->assertGreaterThan(strpos($svg, '<polygon'), strpos($svg, 'data-grid="true"'));
        $this->assertGreaterThan(strpos($svg, 'data-grid="true"'), strpos($svg, 'data-series="1"'));
        foreach ($xpath->query('//s:text[@text-anchor="end"]') as $label) {
            $this->assertMatchesRegularExpression('/^-?\d+,\d{2}$/', $label->textContent);
        }
        $pdf = app(ExportadorControlCalidad::class)->generar('nivel', ['nivel_id' => $nivel->id], $this->actor);
        $this->assertStringStartsWith('%PDF-', $pdf->output());
    }

    public function test_formato_estandar_para_cero_enteros_y_valores_nulos(): void
    {
        $this->assertSame('0,00', NumeroControlCalidad::mostrar('-0.000001'));
        $this->assertSame('5,00', NumeroControlCalidad::mostrar('5'));
        $this->assertSame('—', NumeroControlCalidad::mostrar(null));
        $this->assertNull(NumeroControlCalidad::normalizar('1000000000000.000001'));
        $this->assertNull(NumeroControlCalidad::normalizar('3,6.1'));
    }

    private function datos(string $min, string $max): array
    {
        return ['area_id' => $this->area->id, 'nombre' => 'ÁCIDO ÚRICO', 'unidad' => 'MG/DL',
            'niveles' => [['nombre' => 'Medio', 'lote_id' => $this->lote->id, 'minimo' => $min, 'maximo' => $max]]];
    }
}
