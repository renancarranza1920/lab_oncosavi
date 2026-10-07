<?php

namespace Tests\Feature;

use App\Filament\Resources\OrdenResource\Pages\IngresarResultados;
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
use Livewire\Livewire;
use Tests\TestCase;

class ReferenciasResultadosTest extends TestCase
{
    use RefreshDatabase;

    private GrupoEtario $grupo;

    private User $autor;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed(RolesPermisosSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
        $this->autor = User::factory()->create();
        $this->autor->assignRole('admin');
        $this->actingAs($this->autor);
        $this->grupo = GrupoEtario::create([
            'nombre' => 'Adultos', 'edad_min' => 18, 'edad_max' => 120,
            'unidad_tiempo' => 'años', 'genero' => 'Ambos', 'estado' => 'activo',
        ]);
    }

    public function test_guarda_psa_de_una_orden_existente_sin_repetir_rangos_ni_modificar_su_snapshot_original(): void
    {
        $rangos = [
            $this->referencia('NORMAL O BAJO RIESGO', 0, 4),
            $this->referencia('MEDIANO RIESGO', 4, 10),
            $this->referencia('ALTO RIESGO', 10, null, '>'),
        ];
        $repetidos = [];
        foreach (range(1, 4) as $bloque) {
            foreach ($rangos as $rango) {
                if ($bloque === 2) {
                    $rango['descriptivo'] = mb_convert_case($rango['descriptivo'], MB_CASE_TITLE, 'UTF-8');
                }
                $repetidos[] = $rango;
            }
        }
        // Esta referencia de otro género no debe incorporarse a los resultados.
        $repetidos[] = $this->referencia('Referencia femenina', 0, 2, 'rango', ['genero' => 'Femenino']);
        [$orden, $detalle, $prueba] = $this->crearOrden($repetidos);
        $snapshotOriginal = $detalle->pruebas_snapshot;
        $esperado = 'NORMAL O BAJO RIESGO 0 - 4<br>MEDIANO RIESGO 4 - 10<br>ALTO RIESGO > 10';
        $ruta = 'data.resultados_examenes.'.$detalle->id.'.pruebas_unitarias.0';

        $pagina = Livewire::test(IngresarResultados::class, ['record' => $orden])
            ->assertSet($ruta.'.valor_referencia', $esperado)
            ->set($ruta.'.resultado', '5.6')
            ->call('save')->assertHasNoFormErrors();

        $resultado = Resultado::sole();
        $this->assertSame($esperado, $resultado->valor_referencia_snapshot);
        $this->assertSame('5.6', $resultado->resultado);
        $this->assertSame($prueba->id, $resultado->prueba_id);
        $this->assertSame($this->autor->id, $resultado->user_id);
        $pagina->set($ruta.'.resultado', '6.1')->call('save')->assertHasNoFormErrors();
        $this->assertSame('6.1', $resultado->fresh()->resultado);
        $this->assertSame($esperado, $resultado->fresh()->valor_referencia_snapshot);
        $this->assertSame($snapshotOriginal, $detalle->fresh()->pruebas_snapshot);
        $this->assertDatabaseCount('resultados', 1);
    }

    public function test_guarda_y_actualiza_referencias_legitimas_largas_incluyendo_resultados_externos(): void
    {
        $referencias = [];
        foreach (['A', 'B', 'C'] as $indice => $categoria) {
            $referencias[] = $this->referencia('Categoría '.$categoria.': '.str_repeat('Referencia ampliada ', 5), $indice, $indice + 1);
        }
        [$orden, $detalle] = $this->crearOrden($referencias);
        $ruta = 'data.resultados_examenes.'.$detalle->id;
        $pagina = Livewire::test(IngresarResultados::class, ['record' => $orden]);
        $referenciaCompleta = $pagina->get($ruta.'.pruebas_unitarias.0.valor_referencia');
        $this->assertGreaterThan(255, mb_strlen($referenciaCompleta));
        $this->assertCount(3, explode('<br>', $referenciaCompleta));

        $pagina->set($ruta.'.pruebas_unitarias.0.resultado', '2.5')->call('save')->assertHasNoFormErrors();
        $resultado = Resultado::sole();
        $this->assertSame($referenciaCompleta, $resultado->valor_referencia_snapshot);
        $referenciaExterna = str_repeat('Referencia externa válida. ', 20);
        $pagina->call('addExternalRow', $detalle->id)
            ->set($ruta.'.externos.0.prueba_nombre', 'Prueba externa')
            ->set($ruta.'.externos.0.resultado', '8')
            ->set($ruta.'.externos.0.valor_referencia', $referenciaExterna)
            ->set($ruta.'.externos.0.unidades', 'ng/mL')
            ->call('save')->assertHasNoFormErrors();
        $externo = Resultado::where('es_externo', true)->sole();
        $this->assertSame($referenciaExterna, $externo->valor_referencia_snapshot);
        $this->assertSame($referenciaCompleta, $resultado->fresh()->valor_referencia_snapshot);
        $pagina->set($ruta.'.pruebas_unitarias.0.resultado', '3.5')
            ->set($ruta.'.externos.0.valor_referencia', $referenciaExterna.'Actualizada.')
            ->call('save')->assertHasNoFormErrors();
        $this->assertSame('3.5', $resultado->fresh()->resultado);
        $this->assertSame($referenciaCompleta, $resultado->fresh()->valor_referencia_snapshot);
        $this->assertSame($referenciaExterna.'Actualizada.', $externo->fresh()->valor_referencia_snapshot);
        $this->assertDatabaseCount('resultados', 2);
    }

    public function test_conserva_prioridad_del_grupo_y_genero_especificos_antes_de_deduplicar(): void
    {
        $todasEdades = GrupoEtario::create([
            'nombre' => 'Todas las edades', 'edad_min' => 0, 'edad_max' => 120,
            'unidad_tiempo' => 'años', 'genero' => 'Ambos', 'estado' => 'activo',
        ]);
        $exacta = $this->referencia('Referencia masculina adulta', 1, 5, 'rango', ['genero' => 'Masculino']);
        [$orden, $detalle] = $this->crearOrden([
            $this->referencia('Referencia general adulta', 0, 4),
            $this->referencia('Todas las edades', 0, 10, 'rango', ['grupo_etario_id' => $todasEdades->id, 'genero' => 'Masculino']),
            $exacta,
            array_replace($exacta, ['descriptivo' => 'REFERENCIA MASCULINA ADULTA']),
        ]);

        Livewire::test(IngresarResultados::class, ['record' => $orden])
            ->assertSet('data.resultados_examenes.'.$detalle->id.'.pruebas_unitarias.0.valor_referencia', 'Referencia masculina adulta 1 - 5');
    }

    private function referencia(string $descripcion, ?float $min, ?float $max, string $operador = 'rango', array $campos = []): array
    {
        return array_replace([
            'grupo_etario_id' => $this->grupo->id, 'genero' => 'Ambos',
            'valor_min' => $min, 'valor_max' => $max, 'operador' => $operador,
            'unidades' => 'ng/mL', 'descriptivo' => $descripcion, 'nota' => null,
        ], $campos);
    }

    private function crearOrden(array $referencias): array
    {
        $cliente = Cliente::create(['nombre' => 'Paciente', 'apellido' => 'PSA', 'genero' => 'Masculino', 'fecha_nacimiento' => '1990-01-01']);
        $orden = Orden::create(['cliente_id' => $cliente->id, 'fecha' => now(), 'total' => 20, 'estado' => 'en proceso']);
        $tipo = TipoExamen::create(['nombre' => 'Marcadores tumorales']);
        $examen = Examen::create(['nombre' => 'PSA TOTAL', 'tipo_examen_id' => $tipo->id, 'precio' => 20]);
        $prueba = Prueba::create(['nombre' => 'ANTIGENO PROSTATICO TOTAL (PSA TOTAL)', 'examen_id' => $examen->id, 'estado' => 'activo']);
        $detalle = DetalleOrden::create([
            'orden_id' => $orden->id, 'examen_id' => $examen->id,
            'nombre_examen' => $examen->nombre, 'precio_examen' => 20, 'status' => 'pendiente',
            'pruebas_snapshot' => [[
                'id' => $prueba->id, 'nombre' => $prueba->nombre, 'tipo_conjunto' => null,
                'valores_referencia' => $referencias,
            ]],
        ]);

        return [$orden, $detalle, $prueba];
    }
}
