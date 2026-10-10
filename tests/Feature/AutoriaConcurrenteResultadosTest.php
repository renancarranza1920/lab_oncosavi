<?php

namespace Tests\Feature;

use App\Filament\Resources\OrdenResource\Pages\IngresarResultados;
use App\Models\Actividad;
use App\Models\Cliente;
use App\Models\DetalleOrden;
use App\Models\Examen;
use App\Models\GrupoEtario;
use App\Models\Orden;
use App\Models\Prueba;
use App\Models\Resultado;
use App\Models\TipoExamen;
use App\Models\User;
use Database\Seeders\RolesPermisosSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AutoriaConcurrenteResultadosTest extends TestCase
{
    use RefreshDatabase;

    private User $primero;

    private User $segundo;

    private Orden $orden;

    private GrupoEtario $grupo;

    private TipoExamen $tipo;

    private array $detalles;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('local');
        $this->seed(RolesPermisosSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
        $this->primero = User::factory()->create(['name' => 'Laboratorista primero']);
        $this->segundo = User::factory()->create(['name' => 'Laboratorista segundo']);
        $this->primero->assignRole('Laboratorista');
        $this->segundo->assignRole('Laboratorista');
        $this->grupo = GrupoEtario::create([
            'nombre' => 'Adultos', 'edad_min' => 18, 'edad_max' => 120,
            'unidad_tiempo' => 'años', 'genero' => 'Ambos', 'estado' => 'activo',
        ]);
        $cliente = Cliente::create([
            'nombre' => 'Paciente', 'apellido' => 'Concurrencia',
            'genero' => 'Femenino', 'fecha_nacimiento' => '1990-01-01',
        ]);
        $this->orden = Orden::create([
            'cliente_id' => $cliente->id, 'fecha' => now(), 'total' => 20, 'estado' => 'en proceso',
        ]);
        $this->tipo = TipoExamen::create(['nombre' => 'Química']);
        $this->detalles = [
            $this->crearDetalle('Glucosa', [['nombre' => 'Glucosa']]),
            $this->crearDetalle('Colesterol', [['nombre' => 'Colesterol']]),
        ];
    }

    public function test_dos_pantallas_abiertas_vacias_guardan_examenes_distintos_sin_borrar_ni_reasignar_el_otro(): void
    {
        $paginaPrimero = $this->pagina($this->primero);
        $paginaSegundo = $this->pagina($this->segundo);

        $this->actingAs($this->primero);
        $paginaPrimero->set($this->ruta(0).'.resultado', '90')->call('save')->assertHasNoFormErrors();
        $primeroAntes = $this->resultado(0)->getRawOriginal();

        $this->actingAs($this->segundo);
        $paginaSegundo->set($this->ruta(1).'.resultado', '180')->call('save')->assertHasNoFormErrors();

        $this->assertSame($primeroAntes, $this->resultado(0)->getRawOriginal());
        $this->assertSame('180', $this->resultado(1)->resultado);
        $this->assertSame($this->segundo->id, $this->resultado(1)->user_id);
        $this->assertDatabaseCount('resultados', 2);
    }

    public function test_una_fila_obsoleta_sin_editar_no_revierte_la_edicion_posterior_de_otro_usuario(): void
    {
        $paginaPrimero = $this->pagina($this->primero);
        $paginaPrimero->set($this->ruta(0).'.resultado', '90')->call('save')->assertHasNoFormErrors();
        $paginaSegundo = $this->pagina($this->segundo)->assertSet($this->ruta(0).'.resultado', '90');

        $this->actingAs($this->primero);
        $paginaPrimero->set($this->ruta(0).'.resultado', '95')->call('save')->assertHasNoFormErrors();
        $actualizado = $this->resultado(0)->getRawOriginal();

        $this->actingAs($this->segundo);
        $paginaSegundo->set($this->ruta(1).'.resultado', '180')->call('save')->assertHasNoFormErrors();

        $this->assertSame($actualizado, $this->resultado(0)->getRawOriginal());
        $this->assertSame('95', $this->resultado(0)->resultado);
        $this->assertSame($this->primero->id, $this->resultado(0)->user_id);
        $this->assertSame($this->segundo->id, $this->resultado(1)->user_id);
    }

    public function test_una_edicion_real_reasigna_solo_la_fila_editada_al_laboratorista_actual(): void
    {
        $this->pagina($this->primero)
            ->set($this->ruta(0).'.resultado', '90')
            ->set($this->ruta(1).'.resultado', '180')
            ->call('save')->assertHasNoFormErrors();
        $intacto = $this->resultado(1)->getRawOriginal();

        $this->pagina($this->segundo)
            ->set($this->ruta(0).'.resultado', '95')
            ->call('save')->assertHasNoFormErrors();

        $this->assertSame('95', $this->resultado(0)->resultado);
        $this->assertSame($this->segundo->id, $this->resultado(0)->user_id);
        $this->assertSame($intacto, $this->resultado(1)->getRawOriginal());
    }

    public function test_formato_numerico_equivalente_no_reescribe_pero_cambiar_alertar_atribuye_solo_esa_fila(): void
    {
        $this->pagina($this->primero)
            ->set($this->ruta(0).'.resultado', '1,234')
            ->set($this->ruta(1).'.resultado', '180')
            ->call('save')->assertHasNoFormErrors();
        $antes = Resultado::orderBy('id')->get()->toArray();
        $this->assertSame('1234', $this->resultado(0)->resultado);
        Storage::disk('public')->put($this->rutaParcial(), 'PDF parcial previo');
        $paginaSegundo = $this->pagina($this->segundo);
        $this->travel(2)->seconds();

        $paginaSegundo->set($this->ruta(0).'.resultado', '1,234')->call('save')->assertHasNoFormErrors();

        $this->assertSame($antes, Resultado::orderBy('id')->get()->toArray());
        $this->assertSame(0, $this->actualizaciones($this->resultado(0))->count());
        $this->assertSame('PDF parcial previo', Storage::disk('public')->get($this->rutaParcial()));
        $paginaSegundo->set($this->ruta(0).'.alertar', true)->call('save')->assertHasNoFormErrors();

        $this->assertTrue($this->resultado(0)->alertar);
        $this->assertSame('1234', $this->resultado(0)->resultado);
        $this->assertSame($this->segundo->id, $this->resultado(0)->user_id);
        $this->assertSame($antes[1], $this->resultado(1)->toArray());
        $this->assertSame(1, $this->actualizaciones($this->resultado(0))->count());
        Storage::disk('public')->assertMissing($this->rutaParcial());
    }

    public function test_editar_una_celda_de_matriz_preserva_la_otra_celda_con_valor_cero_y_su_autor(): void
    {
        $detalle = $this->crearDetalle('Hemograma', [
            ['nombre' => 'Serie roja, Hemoglobina', 'tipo_conjunto' => 'Hemograma'],
            ['nombre' => 'Serie roja, Eritrocitos', 'tipo_conjunto' => 'Hemograma'],
        ]);
        $base = 'data.resultados_examenes.'.$detalle->id.'.matrices.Hemograma.data.Serie roja.';
        $this->pagina($this->primero)
            ->set($base.'Hemoglobina.resultado', '0')
            ->set($base.'Eritrocitos.resultado', '5')
            ->call('save')->assertHasNoFormErrors();
        $resultados = Resultado::where('detalle_orden_id', $detalle->id)->orderBy('prueba_id')->get();
        $cero = $resultados[0];
        $antes = $cero->getRawOriginal();
        $this->assertSame('0', $cero->resultado);

        $this->pagina($this->segundo)->set($base.'Eritrocitos.resultado', '6')->call('save')->assertHasNoFormErrors();

        $this->assertSame($antes, $cero->fresh()->getRawOriginal());
        $this->assertSame('6', $resultados[1]->fresh()->resultado);
        $this->assertSame($this->segundo->id, $resultados[1]->fresh()->user_id);
        $this->assertDatabaseCount('resultados', 2);
    }

    public function test_externo_cero_conserva_autoria_sin_cambios_y_editar_metadatos_registra_bitacora(): void
    {
        $detalle = $this->crearDetalle('Examen referido', [], true);
        $base = 'data.resultados_examenes.'.$detalle->id.'.externos.0';
        $this->pagina($this->primero)->call('addExternalRow', $detalle->id)
            ->set($base.'.prueba_nombre', 'Prueba remitida')
            ->set($base.'.resultado', '0')
            ->set($base.'.valor_referencia', '0 - 1')
            ->set($base.'.unidades', 'mg/L')
            ->call('save')->assertHasNoFormErrors();
        $externo = Resultado::where('es_externo', true)->sole();
        $antes = $externo->getRawOriginal();
        $this->assertSame('0', $externo->resultado);
        $this->assertSame($this->primero->id, $externo->user_id);

        $paginaSegundo = $this->pagina($this->segundo);
        $paginaSegundo->set($this->ruta(1).'.resultado', '180')->call('save')->assertHasNoFormErrors();
        $this->assertSame($antes, $externo->fresh()->getRawOriginal());
        $this->assertSame(0, $this->actualizaciones($externo)->count());
        $interno = $this->resultado(1)->getRawOriginal();

        $paginaSegundo->set($base.'.prueba_nombre', 'Prueba remitida corregida')
            ->set($base.'.valor_referencia', 'Ausente')
            ->set($base.'.unidades', 'µg/L')
            ->set($base.'.alertar', true)
            ->call('save')->assertHasNoFormErrors();

        $actualizado = $externo->fresh();
        $this->assertSame('0', $actualizado->resultado);
        $this->assertSame('Prueba remitida corregida', $actualizado->prueba_nombre_snapshot);
        $this->assertSame('Ausente', $actualizado->valor_referencia_snapshot);
        $this->assertSame('µg/L', $actualizado->unidades_snapshot);
        $this->assertTrue($actualizado->alertar);
        $this->assertSame($this->segundo->id, $actualizado->user_id);
        $this->assertSame($interno, $this->resultado(1)->getRawOriginal());
        $actividad = $this->actualizaciones($externo)->sole();
        $this->assertSame($this->segundo->id, $actividad->causer_id);
        $this->assertSame('Prueba remitida', $actividad->properties['old']['prueba_nombre_snapshot']);
        $this->assertSame('Prueba remitida corregida', $actividad->properties['attributes']['prueba_nombre_snapshot']);
        $this->assertSame($this->primero->id, $actividad->properties['old']['user_id']);
        $this->assertSame($this->segundo->id, $actividad->properties['attributes']['user_id']);
    }

    public static function accionesConConflicto(): array
    {
        return ['guardar' => [false], 'completar' => [true]];
    }

    #[DataProvider('accionesConConflicto')]
    public function test_conflicto_en_fila_editada_revierte_todo_el_guardado_y_no_finaliza(bool $completar): void
    {
        $paginaPrimero = $this->pagina($this->primero);
        $paginaPrimero->set($this->ruta(1).'.resultado', '180')->call('save')->assertHasNoFormErrors();
        $paginaSegundo = $this->pagina($this->segundo);

        $this->actingAs($this->primero);
        $paginaPrimero->set($this->ruta(1).'.resultado', '190')->call('save')->assertHasNoFormErrors();
        $antes = Resultado::orderBy('id')->get()->toArray();
        $actividadesAntes = Actividad::where('subject_type', Resultado::class)->count();
        Storage::disk('public')->put($this->rutaParcial(), 'PDF parcial previo al conflicto');

        $this->actingAs($this->segundo);
        // El primer examen se guarda antes de encontrar el conflicto del segundo:
        // su inserción y su bitácora también deben revertirse en la transacción.
        $paginaSegundo->set($this->ruta(0).'.resultado', '90')->set($this->ruta(1).'.resultado', '185');
        if ($completar) {
            $paginaSegundo->callAction('completar');
        } else {
            $paginaSegundo->call('save');
        }
        $paginaSegundo->assertHasErrors(['data.resultados_examenes'])->assertNoRedirect();

        $this->assertSame($antes, Resultado::orderBy('id')->get()->toArray());
        $this->assertSame($actividadesAntes, Actividad::where('subject_type', Resultado::class)->count());
        $this->assertSame('en proceso', $this->orden->fresh()->estado);
        $this->assertSame('190', $this->resultado(1)->resultado);
        $this->assertSame($this->primero->id, $this->resultado(1)->user_id);
        $this->assertDatabaseCount('resultados', 1);
        $this->assertSame('PDF parcial previo al conflicto', Storage::disk('public')->get($this->rutaParcial()));
    }

    private function pagina(User $usuario): Testable
    {
        $this->actingAs($usuario);

        return Livewire::test(IngresarResultados::class, ['record' => $this->orden->fresh()]);
    }

    private function ruta(int $indice): string
    {
        return 'data.resultados_examenes.'.$this->detalles[$indice]->id.'.pruebas_unitarias.0';
    }

    private function resultado(int $indice): Resultado
    {
        return Resultado::where('detalle_orden_id', $this->detalles[$indice]->id)->sole();
    }

    private function rutaParcial(): string
    {
        return 'reportes/PACIENTE-CONCURRENCIA - '.$this->orden->id.' P.PDF';
    }

    private function actualizaciones(Resultado $resultado)
    {
        return Actividad::where('subject_type', Resultado::class)->where('subject_id', $resultado->id)->where('event', 'updated');
    }

    private function crearDetalle(string $nombre, array $pruebas, bool $externo = false): DetalleOrden
    {
        $examen = Examen::create([
            'nombre' => $nombre, 'tipo_examen_id' => $this->tipo->id, 'precio' => 10, 'es_externo' => $externo,
        ]);
        $snapshot = [];
        foreach ($pruebas as $datos) {
            $prueba = Prueba::create([
                'nombre' => $datos['nombre'], 'examen_id' => $examen->id,
                'tipo_conjunto' => $datos['tipo_conjunto'] ?? null, 'estado' => 'activo',
            ]);
            $snapshot[] = [
                'id' => $prueba->id, 'nombre' => $prueba->nombre, 'tipo_conjunto' => $prueba->tipo_conjunto,
                'valores_referencia' => [[
                    'grupo_etario_id' => $this->grupo->id, 'genero' => 'Ambos',
                    'valor_min' => 0, 'valor_max' => 200, 'operador' => 'rango',
                    'unidades' => 'mg/dL', 'descriptivo' => null, 'nota' => null,
                ]],
            ];
        }

        return DetalleOrden::create([
            'orden_id' => $this->orden->id, 'examen_id' => $examen->id,
            'nombre_examen' => $nombre, 'precio_examen' => 10, 'status' => 'pendiente',
            'pruebas_snapshot' => $snapshot,
        ]);
    }
}
