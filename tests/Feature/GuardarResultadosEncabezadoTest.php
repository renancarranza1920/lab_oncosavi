<?php

namespace Tests\Feature;

use App\Filament\Resources\OrdenResource\Pages\IngresarResultados;
use App\Models\Cliente;
use App\Models\DetalleOrden;
use App\Models\Examen;
use App\Models\Orden;
use App\Models\Prueba;
use App\Models\Resultado;
use App\Models\TipoExamen;
use App\Models\User;
use Database\Seeders\RolesPermisosSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GuardarResultadosEncabezadoTest extends TestCase
{
    use RefreshDatabase;

    private User $laboratorista;
    private User $autorOriginal;
    private Orden $orden;
    private DetalleOrden $detalle;
    private array $pruebas;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed(RolesPermisosSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
        $this->laboratorista = User::factory()->create();
        $this->laboratorista->assignRole('Laboratorista');
        $this->autorOriginal = User::factory()->create();
        $this->autorOriginal->assignRole('Laboratorista');
        $cliente = Cliente::create([
            'nombre' => 'Paciente', 'apellido' => 'Ejemplo',
            'genero' => 'Femenino', 'fecha_nacimiento' => '1990-01-01',
        ]);
        $tipo = TipoExamen::create(['nombre' => 'Química']);
        $examen = Examen::create(['nombre' => 'Pruebas de ejemplo', 'tipo_examen_id' => $tipo->id, 'precio' => 20]);
        $this->pruebas = [
            Prueba::create(['nombre' => 'Glucosa', 'examen_id' => $examen->id, 'estado' => 'activo']),
            Prueba::create(['nombre' => 'Colesterol', 'examen_id' => $examen->id, 'estado' => 'activo']),
        ];
        $this->orden = Orden::create(['cliente_id' => $cliente->id, 'fecha' => now(), 'total' => 20, 'estado' => 'en proceso']);
        $this->detalle = DetalleOrden::create([
            'orden_id' => $this->orden->id, 'examen_id' => $examen->id,
            'nombre_examen' => $examen->nombre, 'precio_examen' => 20, 'status' => 'pendiente',
            'pruebas_snapshot' => array_map(fn (Prueba $prueba) => [
                'id' => $prueba->id, 'nombre' => $prueba->nombre,
                'tipo_conjunto' => null, 'valores_referencia' => [],
            ], $this->pruebas),
        ]);
        Resultado::create([
            'detalle_orden_id' => $this->detalle->id, 'prueba_id' => $this->pruebas[0]->id,
            'resultado' => '90', 'user_id' => $this->autorOriginal->id,
        ]);
        $this->actingAs($this->laboratorista);
    }

    public function test_guardar_desde_el_encabezado_conserva_autores_y_no_finaliza_la_orden(): void
    {
        $original = Resultado::sole()->getRawOriginal();
        $pagina = Livewire::test(IngresarResultados::class, ['record' => $this->orden]);
        $this->assertSame('guardarResultados', $pagina->instance()->getCachedHeaderActions()[0]->getName());
        $pagina->assertActionVisible('guardarResultados')
            ->set('data.resultados_examenes.'.$this->detalle->id.'.pruebas_unitarias.1.resultado', '180')
            ->callAction('guardarResultados')->assertHasNoFormErrors();

        $this->assertSame('en proceso', $this->orden->fresh()->estado);
        $this->assertSame($original, Resultado::where('prueba_id', $this->pruebas[0]->id)->sole()->getRawOriginal());
        $nuevo = Resultado::where('prueba_id', $this->pruebas[1]->id)->sole();
        $this->assertSame('180', $nuevo->resultado);
        $this->assertSame($this->laboratorista->id, $nuevo->user_id);

        $guardados = Resultado::orderBy('id')->get()->toArray();
        $this->travel(2)->seconds();
        $pagina->callAction('guardarResultados')->assertHasNoFormErrors();
        $this->assertSame($guardados, Resultado::orderBy('id')->get()->toArray());
    }

    public function test_guardar_desde_el_encabezado_se_oculta_y_rechaza_la_llamada_sin_permiso(): void
    {
        $pagina = Livewire::test(IngresarResultados::class, ['record' => $this->orden])
            ->set('data.resultados_examenes.'.$this->detalle->id.'.pruebas_unitarias.1.resultado', '180');
        $originales = Resultado::all()->toArray();
        Role::findByName('Laboratorista')->revokePermissionTo('ingresar_resultados_orden');
        $this->actingAs($this->laboratorista->fresh());

        $pagina->assertActionHidden('guardarResultados')
            ->call('mountAction', 'guardarResultados')
            ->assertSet('mountedActions', []);

        $this->assertSame($originales, Resultado::all()->toArray());
        $this->assertSame('en proceso', $this->orden->fresh()->estado);
        $pagina->call('save')->assertForbidden();
    }
}
