<?php

namespace Tests\Feature;

use App\ControlCalidad\Models\Area;
use App\ControlCalidad\Models\Lote;
use App\ControlCalidad\Models\Producto;
use App\ControlCalidad\Models\Registro;
use App\ControlCalidad\Services\ControlCalidadService;
use App\ControlCalidad\Services\ExportadorControlCalidad;
use App\Models\User;
use App\Support\ControlCalidadPermissions as Cci;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ControlCalidadSupervisionExportTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    private string $token;

    private $prueba;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-11 10:00:00', 'America/El_Salvador'));
        $this->token = str_repeat('v', 43);
        config(['control_calidad.supervision_token_hash' => hash('sha256', $this->token)]);
        foreach (['access_admin_panel', ...Cci::all()] as $name) {
            Permission::findOrCreate($name, 'web');
        }
        $this->actor = User::factory()->create(['name' => 'Daniela Martínez']);
        $this->actor->givePermissionTo(['access_admin_panel', ...Cci::all()]);
        $area = Area::create(['nombre' => 'Hematología', 'equipo' => 'Mindray BC-20']);
        $product = Producto::create(['nombre' => 'Control hematológico']);
        $lot = Lote::create(['producto_id' => $product->id, 'nombre' => 'Control bajo', 'numero' => 'BC-2611', 'caduca_el' => '2027-01-31']);
        $this->prueba = app(ControlCalidadService::class)->guardarPrueba([
            'area_id' => $area->id, 'nombre' => 'HEMOGLOBINA', 'unidad' => 'g/dL',
            'niveles' => [['nombre' => 'Bajo', 'lote_id' => $lot->id, 'minimo' => '5.7', 'maximo' => '6.7']],
        ], $this->actor);
        foreach (['2026-11-10 07:45:00' => '6,95', '2026-11-11 08:00:00' => '6,2'] as $fecha => $valor) {
            $registro = app(ControlCalidadService::class)->guardar(['area_id' => $area->id,
                'resultados' => [['nivel_id' => $this->prueba->niveles->sole()->id, 'valor' => $valor]]], $this->actor);
            $registro->update(['fecha_hora' => $fecha]);
        }
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    public function test_el_enlace_solo_muestra_datos_y_no_otorga_sesion_ni_acciones(): void
    {
        $before = $this->snapshot();
        $url = route('cci.supervision', ['token' => $this->token], false);
        $this->get($url)->assertOk()->assertSee('HEMOGLOBINA')->assertSee('6,95')->assertSee('Fuera de rango')
            ->assertSee('6,2')->assertSee('Daniela Martínez')->assertSee('Ver registro')->assertSee('Exportar PDF')
            ->assertSee('Gráficos de control')->assertSee('data-chart')->assertDontSee('Editar')->assertDontSee('Eliminar')->assertDontSee('livewire.js')->assertDontSee($this->actor->email)
            ->assertHeader('Referrer-Policy', 'no-referrer')->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
        $this->assertGuest();
        $this->post($url, ['valor' => '999'])->assertStatus(405);
        $this->get(route('cci.exportar', ['tipo' => 'historial', 'token' => $this->token]))->assertRedirect('/admin/login');
        $this->assertEquals($before, $this->snapshot());
    }

    public function test_supervision_rechaza_tokens_incorrectos_y_fechas_invalidas_y_excluye_eliminados(): void
    {
        $this->get(route('cci.supervision', ['token' => str_repeat('x', 43)]))->assertNotFound();
        $this->get(route('cci.supervision', ['token' => $this->token, 'desde' => '2026-11-11', 'hasta' => '2026-11-01']))->assertStatus(422);
        $this->get(route('cci.supervision', ['token' => $this->token, 'desde' => '2026-11-11', 'q' => 'HEMOGLOBINA']))
            ->assertOk()->assertSee('6,2')->assertDontSee('6,95');
        Registro::oldest('fecha_hora')->first()->delete();
        $this->get(route('cci.supervision', ['token' => $this->token]))->assertOk()->assertDontSee('6,95');
    }

    public function test_supervision_exporta_solo_los_registros_seleccionados_con_sus_graficos_y_sin_iniciar_sesion(): void
    {
        $before = $this->snapshot();
        $primero = Registro::oldest('fecha_hora')->first();
        $segundo = Registro::latest('fecha_hora')->first();
        $pdf = app(ExportadorControlCalidad::class)->generarSupervision(['ids' => [$primero->id], 'incluir_graficos' => 1], $this->token);
        $texto = $pdf->getDomPDF()->getDom()->textContent;
        $this->assertStringContainsString('#'.$primero->id.' ·', $texto);
        $this->assertStringNotContainsString('#'.$segundo->id.' ·', $texto);
        $pdf->render();
        $this->assertSame(2, $pdf->getDomPDF()->getCanvas()->get_page_count());
        $url = route('cci.supervision.pdf', ['token' => $this->token, 'ids' => [$primero->id], 'incluir_graficos' => 1]);
        $this->get($url)->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->post($url)->assertStatus(405);
        $this->assertGuest();
        $this->assertEquals($before, $this->snapshot());
    }

    public function test_la_exportacion_publica_valida_enlace_seleccion_y_filtros_y_no_incluye_eliminados(): void
    {
        $registro = Registro::oldest('fecha_hora')->first();
        $this->get(route('cci.supervision.pdf', ['token' => str_repeat('x', 43)]))->assertNotFound();
        foreach ([['ids' => [$registro->id], 'desde' => '2026-11-11'], ['ids' => [99999]], ['ids' => [$registro->id, $registro->id]], ['ids' => 'todos'], ['desde' => '2026-12-01', 'hasta' => '2026-01-01']] as $params) {
            $this->getJson(route('cci.supervision.pdf', ['token' => $this->token, ...$params]))->assertUnprocessable();
        }
        $registro->delete();
        $this->getJson(route('cci.supervision.pdf', ['token' => $this->token, 'ids' => [$registro->id]]))->assertUnprocessable();
        $this->assertGuest();
    }

    public function test_supervision_busca_al_laboratorista_y_conserva_los_filtros_del_historial(): void
    {
        $this->get(route('cci.supervision', ['token' => $this->token, 'q' => 'Daniela', 'area_id' => $this->prueba->area_id, 'laboratorista_id' => $this->actor->id]))
            ->assertOk()->assertSee('6,95')->assertSee('6,2')->assertSee('Daniela Martínez');
    }

    public function test_exportar_exige_sesion_permiso_y_permiso_de_graficos(): void
    {
        $reader = User::factory()->create();
        $reader->givePermissionTo(['access_admin_panel', Cci::VIEW, Cci::HISTORY]);
        $url = route('cci.exportar', ['tipo' => 'historial']);
        $this->get($url)->assertRedirect('/admin/login');
        $this->actingAs($reader)->get($url)->assertForbidden();
        $reader->givePermissionTo(Cci::EXPORT);
        $this->get($url)->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->get(route('cci.exportar', ['tipo' => 'historial', 'incluir_graficos' => 1]))->assertForbidden();
        $this->get(route('cci.exportar', ['tipo' => 'catalogo']))->assertForbidden();
        $this->get(route('cci.exportar', ['tipo' => 'nivel', 'nivel_id' => $this->prueba->niveles->sole()->id]))->assertForbidden();
        $reader->revokePermissionTo(Cci::HISTORY);
        $reader->givePermissionTo(Cci::REGISTER);
        $this->get(route('cci.exportar', ['tipo' => 'registros']))->assertForbidden();
    }

    public function test_pdf_exporta_los_datos_filtrados_y_anade_graficos_sin_modificar_registros(): void
    {
        $before = $this->snapshot();
        $params = ['desde' => '2026-11-11', 'hasta' => '2026-11-11'];
        $sin = app(ExportadorControlCalidad::class)->generar('historial', $params, $this->actor);
        $sin->render();
        $this->assertSame(1, $sin->getDomPDF()->getCanvas()->get_page_count());
        $con = app(ExportadorControlCalidad::class)->generar('historial', $params + ['incluir_graficos' => 1], $this->actor);
        $con->render();
        $this->assertSame(2, $con->getDomPDF()->getCanvas()->get_page_count());
        $this->assertStringStartsWith('%PDF', $con->output());
        $this->assertEquals($before, $this->snapshot());
    }

    public function test_todas_las_secciones_exportan_y_registro_eliminado_no_se_descarga(): void
    {
        $this->actingAs($this->actor);
        foreach ([['resumen'], ['registros'], ['historial'], ['catalogo', ['seccion' => 'areas']],
            ['catalogo', ['seccion' => 'productos']], ['catalogo', ['seccion' => 'pruebas']],
            ['graficos', ['prueba_id' => $this->prueba->id]], ['nivel', ['nivel_id' => $this->prueba->niveles->sole()->id]],
            ['registro', ['registro_id' => Registro::first()->id]]] as $case) {
            $response = $this->get(route('cci.exportar', ['tipo' => $case[0], ...($case[1] ?? [])]));
            $response->assertOk()->assertHeader('content-type', 'application/pdf');
        }
        $registro = Registro::first();
        $registro->delete();
        $this->get(route('cci.exportar', ['tipo' => 'registro', 'registro_id' => $registro->id]))->assertNotFound();
    }

    public function test_la_presentacion_se_descarga_solo_por_el_personal_autorizado_con_el_enlace_correcto(): void
    {
        $url = route('cci.presentacion', ['acceso' => $this->token]);
        $this->get($url)->assertRedirect('/admin/login');
        $reader = User::factory()->create();
        $reader->givePermissionTo(['access_admin_panel', Cci::VIEW, Cci::EXPORT]);
        $this->actingAs($reader)->get($url)->assertForbidden();
        $this->actingAs($this->actor)->get(route('cci.presentacion', ['acceso' => str_repeat('x', 43)]))->assertNotFound();
        $this->get($url)->assertOk()->assertHeader('content-type', 'application/pdf');
        $pdf = app(\App\ControlCalidad\Services\PresentacionControlCalidad::class)->generar(route('cci.supervision', ['token' => $this->token]));
        $pdf->render();
        $this->assertSame(13, $pdf->getDomPDF()->getCanvas()->get_page_count());
    }

    public function test_el_boton_del_historial_conserva_los_filtros_y_la_busqueda_de_la_tabla(): void
    {
        $this->actingAs($this->actor);
        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));
        \Filament\Facades\Filament::bootCurrentPanel();
        $page = \Livewire\Livewire::test(\App\ControlCalidad\Filament\Pages\Historial::class)
            ->filterTable('area_id', $this->prueba->area_id)
            ->filterTable('fecha_hora', ['desde' => '2026-11-11', 'hasta' => '2026-11-11'])
            ->searchTable('Mindray');
        $params = $page->instance()->parametrosExportacion();
        $this->assertSame('2026-11-11', $params['desde']);
        $this->assertSame('2026-11-11', $params['hasta']);
        $this->assertSame((string) $this->prueba->area_id, (string) $params['area_id']);
        $this->assertSame('Mindray', $params['q']);
    }

    private function snapshot(): array
    {
        $rows = [];
        foreach (['cci_areas', 'cci_niveles', 'cci_registros', 'cci_resultados', 'users', 'clientes', 'ordens', 'roles', 'permissions'] as $table) {
            $rows[$table] = DB::table($table)->orderBy('id')->get()->toArray();
        }

        return $rows;
    }
}
