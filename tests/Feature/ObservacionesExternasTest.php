<?php

namespace Tests\Feature;

use App\Filament\Resources\OrdenResource\Pages\IngresarResultados;
use App\Filament\Resources\OrdenResource\Pages\ListOrdens;
use App\Models\Cliente;
use App\Models\DetalleOrden;
use App\Models\Examen;
use App\Models\Orden;
use App\Models\Resultado;
use App\Models\TipoExamen;
use App\Models\User;
use Database\Seeders\RolesPermisosSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Livewire\Livewire;
use Tests\TestCase;

class ObservacionesExternasTest extends TestCase
{
    use RefreshDatabase;

    private User $operadora;

    private User $autor;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('local');
        $this->seed(RolesPermisosSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
        $this->operadora = User::factory()->create(['name' => 'Operadora de demostración']);
        $this->operadora->assignRole('admin');
        $this->autor = User::factory()->create(['name' => 'Laboratorista original']);
        $this->autor->assignRole('Laboratorista');
        $this->actingAs($this->operadora);
    }

    public function test_anadir_observaciones_se_asocia_a_la_fila_elegida_y_solo_se_guarda_al_confirmar(): void
    {
        [$orden, $detalle] = $this->crearOrdenExterna();
        $base = $this->ruta($detalle);
        $pagina = Livewire::test(IngresarResultados::class, ['record' => $orden])
            ->assertSee('Añadir fila de resultados')
            ->assertSee('Añadir fila de observaciones')
            ->call('addExternalRow', $detalle->id)
            ->set($base.'.0.prueba_nombre', 'ANTICOAGULANTE LÚPICO')
            ->set($base.'.0.resultado', 'NEGATIVO')
            ->set($base.'.0.valor_referencia', 'Negativo')
            ->call('addExternalRow', $detalle->id)
            ->set($base.'.1.prueba_nombre', 'ANA-8')
            ->set($base.'.1.resultado', '10,5')
            ->set($base.'.1.valor_referencia', '< 40')
            ->call('addExternalObservationRow', $detalle->id)
            ->assertSet($base.'.1.mostrar_observaciones', true)
            ->set($base.'.1.observaciones', 'Observación del segundo resultado')
            ->call('addExternalObservationRow', $detalle->id, 0)
            ->assertSet($base.'.0.mostrar_observaciones', true)
            ->set($base.'.0.observaciones', "Pruebas basales\nTP del paciente: 13.3 segs.");

        $this->assertCount(2, $pagina->get($base));
        $this->assertDatabaseCount('resultados', 0);

        $pagina->call('save')->assertHasNoFormErrors();
        $filas = $orden->resultados()->orderBy('id')->get();
        $this->assertCount(2, $filas);
        $this->assertSame('NEGATIVO', $filas[0]->resultado);
        $this->assertSame("Pruebas basales\nTP del paciente: 13.3 segs.", $filas[0]->observaciones);
        $this->assertSame('10,5', $filas[1]->resultado);
        $this->assertSame('Observación del segundo resultado', $filas[1]->observaciones);
        $this->assertSame([$detalle->id], $filas->pluck('detalle_orden_id')->unique()->all());

        Livewire::test(IngresarResultados::class, ['record' => $orden->fresh()])
            ->assertSet($base.'.0.observaciones', $filas[0]->observaciones)
            ->assertSet($base.'.0.mostrar_observaciones', true)
            ->assertSet($base.'.1.observaciones', $filas[1]->observaciones)
            ->assertSet($base.'.1.resultado', '10,5');
    }

    public function test_pdf_parcial_y_final_mantienen_cada_observacion_debajo_de_su_resultado_y_escapan_el_texto(): void
    {
        [$orden, $detalle] = $this->crearOrdenExterna();
        $notaPrimera = "TP del paciente: 13.3 segs.\n\nInterpretación: texto <script>sin ejecutar</script> & literal.\nFIN DE OBSERVACIÓN LÚPICO";
        $notaSegunda = "Observación de ANA\nSEGUNDA LÍNEA ANA";
        $this->crearResultado($detalle, 'ANTICOAGULANTE LÚPICO', 'NEGATIVO', $notaPrimera);
        $this->crearResultado($detalle, 'ANA-8', '10,5', $notaSegunda);
        $antes = $orden->resultados()->orderBy('id')->get()->map(fn ($resultado) => $resultado->getRawOriginal())->all();
        $capturados = [];
        View::composer('pdf.reporte_resultados', function ($vista) use (&$capturados): void {
            $capturados = $vista->getData();
        });

        Livewire::test(IngresarResultados::class, ['record' => $orden])
            ->call('generarPdfParcial')->assertFileDownloaded($orden->reporteGuardadoFileName(true));
        $this->comprobarObservacionesEnPdf($capturados, $notaPrimera, $notaSegunda);
        $this->assertStringStartsWith('%PDF-', Storage::disk('public')->get($orden->reporteGuardadoPath(true)));

        $orden->update(['estado' => 'finalizado']);
        Livewire::test(ListOrdens::class)->set('activeTab', 'finalizado')
            ->callTableAction('generarReporte', $orden->fresh(), ['incluir_firmas' => false])
            ->assertFileDownloaded($orden->reporteGuardadoFileName());
        $this->comprobarObservacionesEnPdf($capturados, $notaPrimera, $notaSegunda);
        $this->assertStringStartsWith('%PDF-', Storage::disk('public')->get($orden->reporteGuardadoPath()));
        $this->assertSame($antes, $orden->resultados()->orderBy('id')->get()->map(fn ($resultado) => $resultado->getRawOriginal())->all());
        $this->assertNull($orden->fresh()->observaciones);
    }

    public function test_una_observacion_sin_resultado_no_crea_una_fila_clinica_ni_habilita_el_parcial(): void
    {
        [$orden, $detalle] = $this->crearOrdenExterna();
        $base = $this->ruta($detalle).'.0';
        $pagina = Livewire::test(IngresarResultados::class, ['record' => $orden])
            ->call('addExternalRow', $detalle->id)
            ->call('addExternalObservationRow', $detalle->id)
            ->set($base.'.observaciones', 'Esta nota todavía no tiene un resultado asociado')
            ->call('save')->assertHasNoFormErrors();

        $this->assertDatabaseCount('resultados', 0);
        $metodo = new \ReflectionMethod(IngresarResultados::class, 'getExamenesCompletosParcial');
        $this->assertEmpty($metodo->invoke($pagina->instance()));
        $this->assertSame('en proceso', $orden->fresh()->estado);
    }

    public function test_guardar_sin_editar_preserva_autor_fecha_y_observacion_y_quitarla_conserva_el_resultado_cero(): void
    {
        [$orden, $detalle] = $this->crearOrdenExterna();
        $resultado = $this->crearResultado($detalle, 'Resultado externo cero', '0', '0');
        $antes = $resultado->fresh()->getRawOriginal();
        $base = $this->ruta($detalle).'.0';
        $pagina = Livewire::test(IngresarResultados::class, ['record' => $orden])
            ->assertSet($base.'.resultado', '0')
            ->assertSet($base.'.observaciones', '0')
            ->assertSet($base.'.mostrar_observaciones', true);
        $this->travel(2)->seconds();
        $pagina->call('save')->assertHasNoFormErrors();
        $this->assertSame($antes, $resultado->fresh()->getRawOriginal());

        $parcial = $orden->reporteGuardadoPath(true);
        Storage::disk('public')->put($parcial, 'PDF anterior con observación');
        $pagina->call('removeExternalObservationRow', $detalle->id, 0)
            ->assertSet($base.'.observaciones', '')
            ->assertSet($base.'.mostrar_observaciones', false);
        $this->assertSame('0', $resultado->fresh()->observaciones);
        Storage::disk('public')->assertExists($parcial);

        $pagina->call('save')->assertHasNoFormErrors();
        $this->assertSame('', (string) $resultado->fresh()->observaciones);
        $this->assertSame('0', $resultado->fresh()->resultado);
        $this->assertSame($this->operadora->id, $resultado->fresh()->user_id);
        $this->assertDatabaseCount('resultados', 1);
        Storage::disk('public')->assertMissing($parcial);

        $pagina->call('removeExternalRow', $detalle->id, 0);
        $this->assertDatabaseMissing('resultados', ['id' => $resultado->id]);
        $this->assertDatabaseCount('resultados', 0);
    }

    public function test_una_pantalla_obsoleta_no_sobrescribe_la_observacion_editada_por_otro_usuario(): void
    {
        [$orden, $detalle] = $this->crearOrdenExterna();
        $resultado = $this->crearResultado($detalle, 'Resultado referido', '10.5', 'Nota inicial');
        $base = $this->ruta($detalle).'.0';
        $primera = Livewire::test(IngresarResultados::class, ['record' => $orden]);
        $this->actingAs($this->autor);
        $segunda = Livewire::test(IngresarResultados::class, ['record' => $orden]);

        $this->actingAs($this->operadora);
        $primera->set($base.'.observaciones', 'Nota corregida por la primera pantalla')
            ->call('save')->assertHasNoFormErrors();
        $actualizado = $resultado->fresh()->getRawOriginal();

        $this->actingAs($this->autor);
        $segunda->set($base.'.resultado', '11.5')->call('save')
            ->assertHasErrors(['data.resultados_examenes']);

        $this->assertSame($actualizado, $resultado->fresh()->getRawOriginal());
        $this->assertSame('10.5', $resultado->fresh()->resultado);
    }

    public function test_las_acciones_de_observaciones_exigen_permiso_y_un_detalle_de_la_orden_actual(): void
    {
        [$orden, $detalle] = $this->crearOrdenExterna();
        [, $ajeno] = $this->crearOrdenExterna();
        $this->crearResultado($detalle, 'Resultado referido', '1', 'Nota conservada');
        $antes = Resultado::sole()->getRawOriginal();

        Livewire::test(IngresarResultados::class, ['record' => $orden])
            ->call('addExternalObservationRow', $ajeno->id, 0)->assertNotFound();
        Livewire::test(IngresarResultados::class, ['record' => $orden])
            ->call('removeExternalObservationRow', $ajeno->id, 0)->assertNotFound();

        $pagina = Livewire::test(IngresarResultados::class, ['record' => $orden]);
        $consulta = User::factory()->create();
        $consulta->givePermissionTo(['access_admin_panel', 'view_any_orden', 'view_orden']);
        $this->actingAs($consulta);
        $pagina->call('addExternalObservationRow', $detalle->id, 0)->assertForbidden();
        $this->assertSame($antes, Resultado::sole()->getRawOriginal());
    }

    private function crearOrdenExterna(): array
    {
        $cliente = Cliente::create([
            'nombre' => 'Paciente', 'apellido' => 'Observaciones externas',
            'genero' => 'Femenino', 'fecha_nacimiento' => '1990-01-01',
        ]);
        $orden = Orden::create(['cliente_id' => $cliente->id, 'fecha' => now(), 'total' => 10, 'estado' => 'en proceso']);
        $tipo = TipoExamen::create(['nombre' => 'Exámenes referidos']);
        $examen = Examen::create(['nombre' => 'Panel externo', 'tipo_examen_id' => $tipo->id, 'precio' => 10, 'es_externo' => true]);
        $detalle = DetalleOrden::create([
            'orden_id' => $orden->id, 'examen_id' => $examen->id,
            'nombre_examen' => $examen->nombre, 'precio_examen' => 10, 'status' => 'extra',
        ]);

        return [$orden, $detalle];
    }

    private function crearResultado(DetalleOrden $detalle, string $nombre, string $valor, string $observaciones): Resultado
    {
        return Resultado::create([
            'detalle_orden_id' => $detalle->id, 'prueba_id' => null,
            'prueba_nombre_snapshot' => $nombre, 'resultado' => $valor,
            'valor_referencia_snapshot' => 'Negativo', 'unidades_snapshot' => '',
            'observaciones' => $observaciones, 'es_externo' => true,
            'user_id' => $this->autor->id, 'alertar' => false,
            'created_at' => now()->subDays(2)->startOfSecond(),
            'updated_at' => now()->subDays(2)->startOfSecond(),
        ]);
    }

    private function ruta(DetalleOrden $detalle): string
    {
        return 'data.resultados_examenes.'.$detalle->id.'.externos';
    }

    private function comprobarObservacionesEnPdf(array $datos, string $primera, string $segunda): void
    {
        $filas = collect($datos['grupos_por_usuario'])
            ->flatMap(fn ($grupo) => collect($grupo['datos'])->flatten(1))
            ->flatMap(fn ($examen) => $examen['pruebas_unitarias'] ?? []);
        $this->assertSame($primera, $filas->where('nombre', 'ANTICOAGULANTE LÚPICO')->sole()['observaciones']);
        $this->assertSame($segunda, $filas->where('nombre', 'ANA-8')->sole()['observaciones']);
        $this->assertSame('10,5', $filas->where('nombre', 'ANA-8')->sole()['resultado']);
        $html = view('pdf.reporte_resultados', $datos)->render();
        $this->assertStringNotContainsString('<script>sin ejecutar</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;sin ejecutar&lt;/script&gt;', $html);
        $this->assertStringContainsString('FIN DE OBSERVACIÓN LÚPICO', $html);
        $this->assertStringContainsString('SEGUNDA LÍNEA ANA', $html);
        $this->assertStringContainsString('10,5', $html);
        $nombrePrimero = strpos($html, 'ANTICOAGULANTE LÚPICO');
        $notaPrimera = strpos($html, 'TP del paciente: 13.3 segs.');
        $nombreSegundo = strpos($html, 'ANA-8');
        $notaSegunda = strpos($html, 'Observación de ANA');
        $this->assertNotFalse($nombrePrimero);
        $this->assertNotFalse($notaPrimera);
        $this->assertNotFalse($nombreSegundo);
        $this->assertNotFalse($notaSegunda);
        $this->assertLessThan($notaPrimera, $nombrePrimero);
        $this->assertLessThan($nombreSegundo, $notaPrimera);
        $this->assertLessThan($notaSegunda, $nombreSegundo);
    }
}
