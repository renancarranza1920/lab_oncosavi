<?php

namespace Tests\Feature;

use App\Filament\Resources\OrdenResource\Pages\IngresarResultados;
use App\Filament\Resources\OrdenResource\Pages\ListOrdens;
use App\Models\Cliente;
use App\Models\DetalleOrden;
use App\Models\Examen;
use App\Models\GrupoEtario;
use App\Models\Muestra;
use App\Models\Orden;
use App\Models\Prueba;
use App\Models\Resultado;
use App\Models\TipoExamen;
use App\Models\User;
use App\Models\ValorReferencia;
use Database\Seeders\RolesPermisosSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class SincronizarReferenciasCatalogoTest extends TestCase
{
    use RefreshDatabase;

    private User $operador;

    private User $autor;

    private GrupoEtario $adultos;

    private GrupoEtario $mayores;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('local');
        $this->seed(RolesPermisosSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
        $this->operador = User::factory()->create(['name' => 'Operador de catálogo']);
        $this->operador->assignRole('admin');
        $this->autor = User::factory()->create(['name' => 'Laboratorista original']);
        $this->autor->assignRole('Laboratorista');
        $this->actingAs($this->operador);
        $this->adultos = GrupoEtario::create([
            'nombre' => 'ADULTOS', 'edad_min' => 18, 'edad_max' => 59,
            'unidad_tiempo' => 'años', 'genero' => 'Ambos', 'estado' => 'activo',
        ]);
        $this->mayores = GrupoEtario::create([
            'nombre' => 'ADULTOS MAYORES', 'edad_min' => 60, 'edad_max' => 120,
            'unidad_tiempo' => 'años', 'genero' => 'Ambos', 'estado' => 'activo',
        ]);
    }

    public function test_sincronizar_ggt_actualiza_la_referencia_del_resultado_y_de_ambos_pdfs_sin_otro_guardado(): void
    {
        [$orden, $detalle, $prueba, $resultado] = $this->crearCasoGgt();
        $datosClinicos = $this->datosClinicos($resultado);
        $muestras = $detalle->fresh()->muestras_recibidas;
        $recepcion = $orden->fresh()->only(['fecha_toma_muestra', 'toma_muestra_user_id']);
        $ruta = 'data.resultados_examenes.'.$detalle->id.'.pruebas_unitarias.0';
        $pagina = Livewire::test(IngresarResultados::class, ['record' => $orden])
            ->assertSet($ruta.'.valor_referencia', 'N/A');

        $this->referencia($prueba, $this->mayores, 38);
        $parcialAnterior = $orden->reporteGuardadoPath(true);
        Storage::disk('public')->put($parcialAnterior, '%PDF-1.4 reporte anterior con N/A');
        Activity::query()->delete();
        $pagina->callAction('reconstruir')->assertHasNoActionErrors();

        Storage::disk('public')->assertMissing($parcialAnterior);
        $this->assertSame('MUJERES ≤ 38', $resultado->fresh()->valor_referencia_snapshot);
        $this->assertSame('U/L', $resultado->fresh()->unidades_snapshot);
        $this->assertSame($datosClinicos, $this->datosClinicos($resultado));
        $this->assertSame($muestras, $detalle->fresh()->muestras_recibidas);
        $this->assertEquals($recepcion, $orden->fresh()->only(['fecha_toma_muestra', 'toma_muestra_user_id']));
        $this->assertTrue(Activity::query()
            ->where('subject_type', Orden::class)->where('subject_id', $orden->id)
            ->where('causer_id', $this->operador->id)->exists());

        $capturados = [];
        View::composer('pdf.reporte_resultados', function ($vista) use (&$capturados): void {
            $capturados = $vista->getData();
        });
        $paginaActualizada = Livewire::test(IngresarResultados::class, ['record' => $orden->fresh()])
            ->assertSet($ruta.'.valor_referencia', 'MUJERES ≤ 38')
            ->assertSet($ruta.'.resultado', '14.52');
        // La sincronización ya guardó la referencia: no hay un save() intermedio.
        $paginaActualizada->call('generarPdfParcial')
            ->assertFileDownloaded($orden->reporteGuardadoFileName(true));
        $this->assertReferenciaGgtEnReporte($capturados);
        $this->assertStringStartsWith('%PDF-', Storage::disk('public')->get($orden->reporteGuardadoPath(true)));

        $orden->update(['estado' => 'finalizado']);
        Livewire::test(ListOrdens::class)->set('activeTab', 'finalizado')
            ->callTableAction('generarReporte', $orden->fresh())
            ->assertFileDownloaded($orden->reporteGuardadoFileName());
        $this->assertReferenciaGgtEnReporte($capturados);
        $this->assertStringStartsWith('%PDF-', Storage::disk('public')->get($orden->reporteGuardadoPath()));
        $this->assertSame($datosClinicos, $this->datosClinicos($resultado));
    }

    public function test_solo_la_sincronizacion_explicita_actualiza_una_referencia_valida_y_repetirla_no_cambia_su_autor(): void
    {
        [$orden, , $prueba, $resultado] = $this->crearCasoGgt();
        $referencia = $this->referencia($prueba, $this->mayores, 38);
        $clinicos = $this->datosClinicos($resultado);
        Livewire::test(IngresarResultados::class, ['record' => $orden])->callAction('reconstruir')
            ->assertHasNoActionErrors();
        $this->assertSame('MUJERES ≤ 38', $resultado->fresh()->valor_referencia_snapshot);

        $referencia->update(['valor_max' => 41]);
        Livewire::test(IngresarResultados::class, ['record' => $orden->fresh()]);
        $this->assertSame('MUJERES ≤ 38', $resultado->fresh()->valor_referencia_snapshot);
        $this->travel(2)->seconds();
        Livewire::test(IngresarResultados::class, ['record' => $orden->fresh()])->callAction('reconstruir')
            ->assertHasNoActionErrors();
        $this->assertSame('MUJERES ≤ 41', $resultado->fresh()->valor_referencia_snapshot);
        $this->assertSame($clinicos, $this->datosClinicos($resultado));

        $antesDeRepetir = $resultado->fresh()->getRawOriginal();
        Livewire::test(IngresarResultados::class, ['record' => $orden->fresh()])->callAction('reconstruir')
            ->assertHasNoActionErrors();
        $this->assertSame($antesDeRepetir, $resultado->fresh()->getRawOriginal());
    }

    public function test_sincronizar_incluye_matrices_y_conserva_externos_e_historicos_sin_crear_resultados(): void
    {
        [$orden, $detalle, $prueba] = $this->crearCasoGgt();
        $this->referencia($prueba, $this->mayores, 38);
        $externo = Resultado::create([
            'detalle_orden_id' => $detalle->id, 'prueba_id' => null, 'resultado' => '8',
            'es_externo' => true, 'prueba_nombre_snapshot' => 'Prueba añadida manualmente',
            'valor_referencia_snapshot' => 'Referencia externa propia', 'unidades_snapshot' => 'otra unidad',
            'user_id' => $this->autor->id,
        ]);
        $inactiva = Prueba::create(['nombre' => 'Prueba histórica', 'examen_id' => $prueba->examen_id, 'estado' => 'inactivo']);
        $historico = Resultado::create([
            'detalle_orden_id' => $detalle->id, 'prueba_id' => $inactiva->id, 'resultado' => '17',
            'prueba_nombre_snapshot' => $inactiva->nombre, 'valor_referencia_snapshot' => '1 - 20',
            'unidades_snapshot' => 'U/L', 'user_id' => $this->autor->id,
        ]);
        $matriz = Prueba::create([
            'nombre' => 'Fila, Columna', 'examen_id' => $prueba->examen_id,
            'tipo_conjunto' => 'Panel', 'estado' => 'activo',
        ]);
        $this->referencia($matriz, $this->mayores, 38);
        $celda = Resultado::create([
            'detalle_orden_id' => $detalle->id, 'prueba_id' => $matriz->id, 'resultado' => '0',
            'prueba_nombre_snapshot' => $matriz->nombre, 'valor_referencia_snapshot' => 'N/A',
            'unidades_snapshot' => 'U/L', 'user_id' => $this->autor->id, 'alertar' => true,
        ]);
        $sinResultado = Prueba::create(['nombre' => 'Prueba todavía sin resultado', 'examen_id' => $prueba->examen_id, 'estado' => 'activo']);
        $externoAntes = $externo->fresh()->getRawOriginal();
        $historicoAntes = $historico->fresh()->getRawOriginal();
        $celdaAntes = $this->datosClinicos($celda);

        Livewire::test(IngresarResultados::class, ['record' => $orden])->callAction('reconstruir')
            ->assertHasNoActionErrors();

        $this->assertSame($externoAntes, $externo->fresh()->getRawOriginal());
        $this->assertSame($historicoAntes, $historico->fresh()->getRawOriginal());
        $this->assertSame($celdaAntes, $this->datosClinicos($celda));
        $this->assertSame('MUJERES ≤ 38', $celda->fresh()->valor_referencia_snapshot);
        $this->assertDatabaseMissing('resultados', ['detalle_orden_id' => $detalle->id, 'prueba_id' => $sinResultado->id]);
        $this->assertDatabaseCount('resultados', 4);
        $snapshot = collect($detalle->fresh()->pruebas_snapshot)->keyBy('id');
        $this->assertTrue($snapshot->has($matriz->id));
        $this->assertSame('Panel', $snapshot[$matriz->id]['tipo_conjunto']);
        $this->assertFalse($snapshot->has($inactiva->id));
    }

    public function test_una_ventana_anterior_no_revierte_la_referencia_sincronizada_al_editar_un_resultado(): void
    {
        [$orden, $detalle, $prueba, $resultado] = $this->crearCasoGgt();
        $editor = User::factory()->create(['name' => 'Laboratorista con ventana abierta']);
        $editor->assignRole('Laboratorista');
        $this->actingAs($editor);
        $ruta = 'data.resultados_examenes.'.$detalle->id.'.pruebas_unitarias.0';
        $ventanaAnterior = Livewire::test(IngresarResultados::class, ['record' => $orden])
            ->assertSet($ruta.'.resultado', '14.52')
            ->assertSet($ruta.'.valor_referencia', 'N/A');

        $this->actingAs($this->operador);
        $this->referencia($prueba, $this->mayores, 38);
        Livewire::test(IngresarResultados::class, ['record' => $orden->fresh()])
            ->callAction('reconstruir')->assertHasNoActionErrors();
        $this->assertSame('MUJERES ≤ 38', $resultado->fresh()->valor_referencia_snapshot);

        $this->actingAs($editor);
        $ventanaAnterior->set($ruta.'.resultado', '15.00')->call('save')->assertHasNoFormErrors();

        $this->assertSame('15.00', $resultado->fresh()->resultado);
        $this->assertSame($editor->id, $resultado->fresh()->user_id);
        $this->assertSame('MUJERES ≤ 38', $resultado->fresh()->valor_referencia_snapshot);
        $this->assertSame('U/L', $resultado->fresh()->unidades_snapshot);
        $this->assertSame($prueba->nombre, $resultado->fresh()->prueba_nombre_snapshot);
    }

    public function test_el_servidor_rechaza_sincronizar_sin_permiso_y_conserva_los_datos(): void
    {
        [$orden, $detalle, $prueba, $resultado] = $this->crearCasoGgt();
        $this->referencia($prueba, $this->mayores, 38);
        $antes = [$detalle->fresh()->getRawOriginal(), $resultado->fresh()->getRawOriginal()];
        $usuario = User::factory()->create();
        $usuario->givePermissionTo(['access_admin_panel', 'view_any_orden', 'view_orden', 'ingresar_resultados_orden']);
        $this->actingAs($usuario);

        Livewire::test(IngresarResultados::class, ['record' => $orden])
            ->assertActionHidden('reconstruir')
            ->call('sincronizarConCatalogo')->assertForbidden();

        $this->assertSame($antes, [$detalle->fresh()->getRawOriginal(), $resultado->fresh()->getRawOriginal()]);
    }

    public static function estadosCerrados(): array
    {
        return [['finalizado'], ['cancelado']];
    }

    #[DataProvider('estadosCerrados')]
    public function test_no_se_puede_sincronizar_una_orden_cerrada_aunque_se_llame_al_metodo_directamente(string $estado): void
    {
        [$orden, $detalle, $prueba, $resultado] = $this->crearCasoGgt();
        $this->referencia($prueba, $this->mayores, 38);
        $orden->update(['estado' => $estado]);
        $antes = [$detalle->fresh()->getRawOriginal(), $resultado->fresh()->getRawOriginal()];

        Livewire::test(IngresarResultados::class, ['record' => $orden])
            ->assertActionHidden('reconstruir')
            ->call('sincronizarConCatalogo')->assertForbidden();

        $this->assertSame($antes, [$detalle->fresh()->getRawOriginal(), $resultado->fresh()->getRawOriginal()]);
    }

    private function crearCasoGgt(): array
    {
        $cliente = Cliente::create([
            'nombre' => 'Paciente', 'apellido' => 'Referencia', 'genero' => 'Femenino',
            'fecha_nacimiento' => now()->subYears(69)->subMonth()->toDateString(),
        ]);
        $orden = Orden::create([
            'cliente_id' => $cliente->id, 'fecha' => now(), 'total' => 10, 'estado' => 'en proceso',
            'fecha_toma_muestra' => now()->subDays(2)->startOfSecond(), 'toma_muestra_user_id' => $this->autor->id,
        ]);
        $tipo = TipoExamen::create(['nombre' => 'Química sanguínea']);
        $examen = Examen::create(['nombre' => 'GAMMA GLUTAMIL', 'tipo_examen_id' => $tipo->id, 'precio' => 10]);
        $prueba = Prueba::create(['nombre' => 'GAMMA GLUTAMIL TRANSPEPTIDASA (GGT)', 'examen_id' => $examen->id, 'estado' => 'activo']);
        $referenciaAdulta = $this->referencia($prueba, $this->adultos, 55);
        $detalle = DetalleOrden::create([
            'orden_id' => $orden->id, 'examen_id' => $examen->id, 'nombre_examen' => $examen->nombre,
            'precio_examen' => 10, 'status' => 'quimica_sanguinea',
            'pruebas_snapshot' => [[
                'id' => $prueba->id, 'nombre' => $prueba->nombre, 'tipo_conjunto' => null,
                'tipo_prueba_id' => null, 'valores_referencia' => [$referenciaAdulta->only([
                    'grupo_etario_id', 'genero', 'valor_min', 'valor_max', 'operador', 'unidades', 'descriptivo', 'nota',
                ])],
            ]],
        ]);
        $muestra = Muestra::create(['nombre' => 'Suero']);
        $examen->muestras()->attach($muestra);
        $detalle->forceFill(['muestras_recibidas' => [$muestra->id]])->save();
        $resultado = Resultado::create([
            'detalle_orden_id' => $detalle->id, 'prueba_id' => $prueba->id, 'resultado' => '14.52',
            'prueba_nombre_snapshot' => $prueba->nombre, 'valor_referencia_snapshot' => 'N/A',
            'unidades_snapshot' => 'U/L', 'user_id' => $this->autor->id, 'alertar' => false,
            'created_at' => now()->subDays(3)->startOfSecond(), 'updated_at' => now()->subDays(3)->startOfSecond(),
        ]);

        return [$orden, $detalle, $prueba, $resultado];
    }

    private function referencia(Prueba $prueba, GrupoEtario $grupo, float $maximo): ValorReferencia
    {
        return $prueba->valoresReferencia()->create([
            'grupo_etario_id' => $grupo->id, 'genero' => 'Femenino', 'operador' => '<=',
            'valor_min' => null, 'valor_max' => $maximo, 'unidades' => 'U/L', 'descriptivo' => 'MUJERES',
        ]);
    }

    private function datosClinicos(Resultado $resultado): array
    {
        return Arr::except($resultado->fresh()->getRawOriginal(), [
            'prueba_nombre_snapshot', 'valor_referencia_snapshot', 'unidades_snapshot',
        ]);
    }

    private function assertReferenciaGgtEnReporte(array $datos): void
    {
        $prueba = collect($datos['grupos_por_usuario'])
            ->flatMap(fn ($grupo) => collect($grupo['datos'])->flatten(1))
            ->flatMap(fn ($examen) => $examen['pruebas_unitarias'] ?? [])
            ->where('nombre', 'GAMMA GLUTAMIL TRANSPEPTIDASA (GGT)')->sole();
        $this->assertSame('14.52', $prueba['resultado']);
        $this->assertSame('MUJERES ≤ 38', $prueba['referencia']);
        $this->assertSame('U/L', $prueba['unidades']);
        $this->assertSame($this->autor->id, $prueba['user_id']);
        $html = view('pdf.reporte_resultados', $datos)->render();
        $this->assertStringContainsString('MUJERES ≤ 38 U/L', $html);
        $this->assertStringNotContainsString('N/A U/L', $html);
    }
}
