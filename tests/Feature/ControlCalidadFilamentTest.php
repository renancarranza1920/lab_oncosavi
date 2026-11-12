<?php

namespace Tests\Feature;

use App\ControlCalidad\Filament\Pages\Graficos;
use App\ControlCalidad\Filament\Pages\Historial;
use App\ControlCalidad\Filament\Pages\Mantenimiento;
use App\ControlCalidad\Filament\Pages\Registro as RegistroPagina;
use App\ControlCalidad\Filament\Pages\Resumen;
use App\ControlCalidad\Filament\Widgets\GraficoNivel;
use App\ControlCalidad\Models\Area;
use App\ControlCalidad\Models\Lote;
use App\ControlCalidad\Models\Nivel;
use App\ControlCalidad\Models\Producto;
use App\ControlCalidad\Models\Prueba;
use App\ControlCalidad\Models\Registro;
use App\ControlCalidad\Models\Resultado;
use App\ControlCalidad\Services\ControlCalidadService;
use App\Models\User;
use App\Support\ControlCalidadPermissions as Cci;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ControlCalidadFilamentTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    private ?\Closure $restoreRepeaters = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->restoreRepeaters = Repeater::fake();
        $this->travelTo(Carbon::parse('2026-11-10 16:00:00', 'UTC'));
        foreach (['access_admin_panel', ...Cci::all()] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $this->actor = $this->usuario(Cci::all(), 'Daniela Martínez');
        $this->actingAs($this->actor);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
    }

    protected function tearDown(): void
    {
        if ($this->restoreRepeaters !== null) {
            ($this->restoreRepeaters)();
        }
        $this->travelBack();
        parent::tearDown();
    }

    public function test_qa_conserva_sus_cinco_paginas_dentro_del_menu_general_de_oncosavi(): void
    {
        $expected = [Resumen::class, Mantenimiento::class, RegistroPagina::class, Graficos::class, Historial::class];
        $panel = Filament::getPanel('admin');
        $this->assertFalse($panel->hasTopNavigation());
        foreach (['view_any_orden', 'view_orden'] as $permission) {
            $this->actor->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        foreach ($expected as $page) {
            $this->assertTrue(is_subclass_of($page, \Filament\Pages\Page::class));
            $this->assertContains($page, $panel->getPages());
            $this->get($page::getUrl(panel: 'admin'))->assertOk()
                ->assertSee('data-group-label="QA · Control de calidad"', false)
                ->assertSee(\App\Filament\Resources\OrdenResource::getUrl(panel: 'admin'));
        }
        $this->assertCount(17, Cci::all());
        foreach (['/qa-quimica/exportar.csv', '/qa-quimica/informe.pdf', '/qa-quimica/eventos'] as $url) {
            $this->get($url)->assertNotFound();
        }
    }

    public function test_los_enlaces_anteriores_redirigen_al_menu_general_y_conservan_los_filtros(): void
    {
        foreach (['' => Resumen::class, '/mantenimiento' => Mantenimiento::class,
            '/registro' => RegistroPagina::class, '/graficos' => Graficos::class, '/historial' => Historial::class] as $path => $page) {
            $this->get('/control-calidad-interno'.$path.'?seccion=productos')
                ->assertRedirect($page::getUrl(parameters: ['seccion' => 'productos'], panel: 'admin'));
        }
        $this->get('/control-calidad-interno/no-existe')->assertNotFound();
    }

    public function test_la_sesion_y_los_permisos_se_aplican_a_las_cinco_direcciones(): void
    {
        $this->app['auth']->guard('web')->logout();
        foreach ([Resumen::class, Mantenimiento::class, RegistroPagina::class, Graficos::class, Historial::class] as $page) {
            $this->get($page::getUrl(panel: 'admin'))->assertRedirect('/admin/login');
        }
        $reader = $this->usuario([Cci::VIEW], 'Rafael Hernández');
        $this->actingAs($reader);
        $this->get(Resumen::getUrl(panel: 'admin'))->assertOk();
        foreach ([Mantenimiento::class, RegistroPagina::class, Graficos::class, Historial::class] as $page) {
            $this->get($page::getUrl(panel: 'admin'))->assertForbidden();
        }
        $withoutGeneral = $this->usuario([Cci::REGISTER], 'María Hernández');
        $this->actingAs($withoutGeneral)->get(RegistroPagina::getUrl(panel: 'admin'))->assertForbidden();
        $reader->revokePermissionTo('access_admin_panel');
        $this->actingAs($reader->refresh())->get(Resumen::getUrl(panel: 'admin'))->assertForbidden();
    }

    #[DataProvider('cantidadesNiveles')]
    public function test_las_pruebas_tienen_niveles_manuales_y_las_graficas_conservan_cada_nivel(int $count): void
    {
        [$area, $prueba, $niveles, $data] = $this->controles($count);
        $record = app(ControlCalidadService::class)->guardar($data, $this->actor);
        $this->assertCount($count, $prueba->fresh()->niveles);
        $this->assertCount($count, $record->resultados);
        $component = Livewire::test(Graficos::class)
            ->fillForm(['area_id' => $area->id, 'prueba_id' => $prueba->id, 'periodo' => 'mensual'])
            ->call('aplicarFiltros')->assertHasNoFormErrors();
        $this->assertCount($count, $component->instance()->getNiveles());
        foreach ($niveles as $nivel) {
            $widget = Livewire::test(GraficoNivel::class, ['nivelId' => $nivel->id, 'desde' => '2026-11-01', 'hasta' => '2026-11-30']);
            $method = new \ReflectionMethod($widget->instance(), 'getData');
            $chart = $method->invoke($widget->instance());
            $this->assertNotEmpty($chart['labels']);
            $this->assertNotEmpty($chart['datasets']);
        }
    }

    public static function cantidadesNiveles(): array
    {
        return ['uno' => [1], 'tres' => [3], 'cuatro' => [4]];
    }

    #[DataProvider('periodosGraficos')]
    public function test_los_periodos_de_graficos_se_calculan_en_el_servidor_sin_fechas_libres(string $periodo, string $desde, string $hasta, int $lecturas): void
    {
        [$area, $prueba, $niveles, $data] = $this->controles(1);
        foreach (['2026-10-31 23:59:59', '2026-11-08 23:59:59', '2026-11-09 00:00:00', '2026-11-10 00:00:00',
            '2026-11-10 23:59:59', '2026-11-15 23:59:59', '2026-11-16 00:00:00', '2026-12-01 00:00:00'] as $fecha) {
            $registro = app(ControlCalidadService::class)->guardar($data, $this->actor);
            $registro->update(['fecha_hora' => Carbon::parse($fecha, 'America/El_Salvador')]);
        }

        $component = Livewire::test(Graficos::class)
            ->assertFormFieldDoesNotExist('desde')->assertFormFieldDoesNotExist('hasta')
            ->fillForm(['area_id' => $area->id, 'prueba_id' => $prueba->id, 'periodo' => $periodo])
            ->set('data.desde', '2000-01-01')->set('data.hasta', '2040-01-01')
            ->call('aplicarFiltros')->assertHasNoFormErrors();
        $filtros = $component->instance()->filtros;
        $this->assertSame($desde, $filtros['desde']);
        $this->assertSame($hasta, $filtros['hasta']);
        $this->assertSame($periodo, $filtros['periodo']);
        $widget = Livewire::test(GraficoNivel::class, ['nivelId' => $niveles[0]->id, 'desde' => $filtros['desde'], 'hasta' => $filtros['hasta']]);
        $chart = (new \ReflectionMethod($widget->instance(), 'getData'))->invoke($widget->instance());
        $this->assertCount($lecturas, $chart['labels']);
    }

    public static function periodosGraficos(): array
    {
        return [
            'diario' => ['diario', '2026-11-10', '2026-11-10', 2],
            'semanal' => ['semanal', '2026-11-09', '2026-11-15', 4],
            'mensual' => ['mensual', '2026-11-01', '2026-11-30', 6],
        ];
    }

    public function test_graficos_solo_acepta_diario_semanal_y_mensual(): void
    {
        [$area, $prueba] = $this->controles(1);
        Livewire::test(Graficos::class)->assertSet('data.periodo', 'mensual')
            ->assertFormFieldExists('periodo', fn ($field): bool => $field->getOptions() === [
                'diario' => 'Diario', 'semanal' => 'Semanal', 'mensual' => 'Mensual',
            ])->fillForm(['area_id' => $area->id, 'prueba_id' => $prueba->id, 'periodo' => 'anual'])
            ->call('aplicarFiltros')->assertHasFormErrors(['periodo']);
    }

    public function test_graficos_marca_solo_los_puntos_fuera_y_sombrea_los_limites_guardados_en_cada_medicion(): void
    {
        [$area, $prueba, $niveles, $data] = $this->controles(1);
        $service = app(ControlCalidadService::class);
        foreach (['5,6', '5,7', '6,7', '6,8'] as $value) {
            $data['resultados'][0]['valor'] = $value;
            $service->guardar($data, $this->actor);
        }
        $niveles[0]->valorControlActual->update(['minimo' => 7, 'maximo' => 9, 'esperado' => 8]);
        $data['resultados'][0]['valor'] = '8';
        $service->guardar($data, $this->actor);

        $widget = Livewire::test(GraficoNivel::class, ['nivelId' => $niveles[0]->id, 'desde' => '2026-11-01', 'hasta' => '2026-11-30']);
        $chart = (new \ReflectionMethod($widget->instance(), 'getData'))->invoke($widget->instance());
        $this->assertCount(5, $chart['labels']);
        $series = $chart['datasets'];
        $this->assertSame(['crossRot', 'circle', 'circle', 'crossRot', 'circle'], $series[0]['pointStyle']);
        $this->assertSame(['#dc2626', '#2563eb', '#2563eb', '#dc2626', '#2563eb'], $series[0]['pointBorderColor']);
        $this->assertSame([5.6, 5.7, 6.7, 6.8, null], $series[0]['data']);
        $this->assertSame(['target' => 3], $series[2]['fill']);
        $this->assertSame([5.7, 5.7, 5.7, 5.7, null], $series[2]['data']);
        $this->assertSame([6.7, 6.7, 6.7, 6.7, null], $series[3]['data']);
        $this->assertSame(['target' => 7], $series[6]['fill']);
        $this->assertSame([null, null, null, null, 7.0], $series[6]['data']);
        $this->assertSame([null, null, null, null, 9.0], $series[7]['data']);
        $this->assertFalse($series[2]['spanGaps']);
    }

    public function test_el_formulario_filament_guarda_coma_y_cero_con_fecha_y_actor_del_servidor(): void
    {
        [$area, $prueba, $niveles, $data] = $this->controles(3);
        $data['resultados'][0]['valor'] = '6,3';
        $data['resultados'][1]['valor'] = '0';
        $data['resultados'][2]['valor'] = '18.9';

        Livewire::test(RegistroPagina::class)
            ->assertFormFieldDoesNotExist('laboratorista_id')
            ->assertFormComponentDoesNotExist('fecha_automatica')
            ->assertDontSee('Fecha y hora del registro')
            ->fillForm($data)->call('guardar')->assertHasNoFormErrors()
            ->assertSee('Fecha y hora del registro')->assertSee('10/11/2026 10:00');

        $record = Registro::query()->sole();
        $this->assertSame($area->id, (int) $record->area_id);
        $this->assertSame($this->actor->id, (int) $record->registrado_por_id);
        $this->assertSame($this->actor->id, (int) $record->laboratorista_id);
        $this->assertSame('2026-11-10 16:00:00', $record->fecha_hora->utc()->format('Y-m-d H:i:s'));
        $values = $record->resultados->keyBy('nivel_id');
        $this->assertSame(6.3, (float) $values[$niveles[0]->id]->valor);
        $this->assertSame('6,3', $values[$niveles[0]->id]->valor_original);
        $this->assertSame(0.0, (float) $values[$niveles[1]->id]->valor);
        $this->assertNull($record->observaciones);
    }

    public function test_un_resultado_mal_escrito_muestra_el_error_en_el_formulario_sin_guardar(): void
    {
        [$area, $prueba, $niveles, $data] = $this->controles(1);
        $data['resultados'][0]['valor'] = '10,5,3';

        Livewire::test(RegistroPagina::class)->fillForm($data)->call('guardar')
            ->assertHasFormErrors(['resultados.0.valor' => 'regex']);
        $this->assertSame(0, Registro::count());
    }

    public function test_mantenimiento_crea_area_producto_lote_y_prueba_con_niveles_desde_las_acciones_filament(): void
    {
        $page = Livewire::test(Mantenimiento::class)
            ->callTableAction('crear', data: ['nombre' => 'Inmunología', 'equipo' => 'Inmunología 02'])
            ->assertHasNoTableActionErrors();
        $area = Area::query()->sole();
        $this->assertSame('Inmunología', $area->nombre);
        $this->assertSame(['areas' => 'Áreas', 'productos' => 'Productos', 'pruebas' => 'Pruebas'], $page->instance()->secciones());
        $page->call('cambiarSeccion', 'productos')
            ->callTableAction('crear', data: [
                'producto_nombre' => 'BC-3D Hematology Control', 'numero' => 'BC-2611', 'caduca_el' => '2027-01-31',
            ])->assertHasNoTableActionErrors();
        $this->assertSame('BC-3D Hematology Control', Producto::query()->sole()->nombre);
        $lot = Lote::query()->sole();
        $levels = [];
        foreach ([[6.2, 5.7, 6.7], [13.8, 13.1, 14.5], [18.9, 17.9, 19.9], [24, 23, 25]] as $i => [$target, $min, $max]) {
            $levels[] = ['nombre' => 'Nivel '.($i + 1), 'lote_id' => $lot->id,
                'esperado' => str_replace('.', ',', (string) $target), 'minimo' => $min, 'maximo' => $max];
        }
        $page->call('cambiarSeccion', 'pruebas')
            ->callTableAction('crear', data: ['area_id' => $area->id, 'nombre' => 'HEMOGLOBINA', 'unidad' => 'g/dL', 'niveles' => $levels])
            ->assertHasNoTableActionErrors();
        $prueba = Prueba::query()->sole();
        $this->assertCount(4, $prueba->niveles);
        $this->assertSame(6.2, (float) $prueba->niveles->first()->valorControlActual->esperado);
    }

    public function test_productos_reutiliza_el_nombre_y_editar_un_lote_conserva_los_otros_y_las_lecturas(): void
    {
        [$area, $prueba, $niveles, $data] = $this->controles(1);
        $registro = app(ControlCalidadService::class)->guardar($data, $this->actor);
        $original = $registro->resultados()->sole()->getAttributes();
        $page = Livewire::test(Mantenimiento::class)->call('cambiarSeccion', 'productos');
        foreach (['C-2611-A', 'C-2611-B'] as $numero) {
            $page->callTableAction('crear', data: [
                'producto_nombre' => 'Control multianalito', 'numero' => $numero, 'caduca_el' => '2027-02-28',
            ])->assertHasNoTableActionErrors();
        }
        $producto = Producto::where('nombre', 'Control multianalito')->sole();
        $this->assertSame(2, $producto->lotes()->count());
        $loteA = $producto->lotes()->where('numero', 'C-2611-A')->sole();
        $loteB = $producto->lotes()->where('numero', 'C-2611-B')->sole();
        $otroOriginal = $loteB->getAttributes();
        $page->callTableAction('editar', $loteA, [
            'producto_nombre' => 'Control multianalito alternativo', 'numero' => 'C-2611-A', 'caduca_el' => '2027-03-31',
        ])->assertHasNoTableActionErrors();

        $this->assertSame('Control multianalito alternativo', $loteA->fresh()->producto->nombre);
        $this->assertSame($otroOriginal, $loteB->fresh()->getAttributes());
        $this->assertSame($original, $registro->resultados()->sole()->getAttributes());
        $this->assertSame($producto->id, $loteB->fresh()->producto_id);
    }

    public function test_productos_rechaza_el_numero_de_lote_duplicado_dentro_del_mismo_producto(): void
    {
        $page = Livewire::test(Mantenimiento::class)->call('cambiarSeccion', 'productos');
        $data = ['producto_nombre' => 'Control multianalito', 'numero' => 'C-2611', 'caduca_el' => '2027-02-28'];
        $page->callTableAction('crear', data: $data)->assertHasNoTableActionErrors();
        $page->callTableAction('crear', data: $data)->assertHasTableActionErrors(['numero']);

        $this->assertSame(1, Producto::count());
        $this->assertSame(1, Lote::count());
    }

    public function test_las_referencias_y_la_autoria_no_se_reemplazan_por_campos_enviados_por_el_navegador(): void
    {
        [$area, $prueba, $niveles, $data] = $this->controles(1);
        $otraPersona = $this->usuario([Cci::VIEW, Cci::REGISTER], 'Ana López');
        $data += ['fecha_hora' => '2040-01-01 01:00:00', 'registrado_por_id' => 99999, 'actualizado_por_id' => 99999];
        $data['laboratorista_id'] = $otraPersona->id;
        $data['resultados'][0] += ['esperado' => '9999', 'minimo' => '-9999', 'maximo' => '99999', 'prueba_nombre' => 'Prueba suplantada'];
        $record = app(ControlCalidadService::class)->guardar($data, $this->actor);
        $result = $record->resultados->sole();
        $this->assertSame($this->actor->id, (int) $record->registrado_por_id);
        $this->assertSame($this->actor->id, (int) $record->laboratorista_id);
        $this->assertSame('2026-11-10 16:00:00', $record->fecha_hora->utc()->format('Y-m-d H:i:s'));
        $this->assertSame($prueba->nombre, $result->prueba_nombre);
        $this->assertSame(6.2, (float) $result->esperado);
        $nivel = $niveles[0];
        $nivel->valorControlActual->update(['esperado' => 6.5, 'maximo' => 7]);
        $this->assertSame(6.2, (float) $result->fresh()->esperado);
    }

    public function test_no_acepta_lecturas_de_otra_area_ni_niveles_repetidos(): void
    {
        [$area, $prueba, $niveles, $data] = $this->controles(1);
        [$otraArea, $otraPrueba, $otrosNiveles] = $this->controles(1, 'Química', 'Química 01');
        $data['resultados'][0]['nivel_id'] = $otrosNiveles[0]->id;
        $this->invalido(fn () => app(ControlCalidadService::class)->guardar($data, $this->actor));
        $this->assertSame(0, Registro::count());
        $data['resultados'] = [['nivel_id' => $niveles[0]->id, 'valor' => '6,3'], ['nivel_id' => $niveles[0]->id, 'valor' => '6,4']];
        $this->invalido(fn () => app(ControlCalidadService::class)->guardar($data, $this->actor));
        $this->assertSame(0, Registro::count());
    }

    public function test_editar_y_eliminar_requieren_sus_permisos_y_conservan_el_historial(): void
    {
        [$area, $prueba, $niveles, $data] = $this->controles(1);
        $record = app(ControlCalidadService::class)->guardar($data, $this->actor);
        $original = $record->only(['fecha_hora', 'area_id', 'registrado_por_id']);
        $capture = $this->usuario([Cci::VIEW, Cci::REGISTER, Cci::HISTORY], 'Ana López');
        $this->actingAs($capture);
        Livewire::test(Historial::class)
            ->assertTableActionHidden('editar', $record)
            ->assertTableActionHidden('eliminar', $record);
        $this->prohibido(fn () => app(ControlCalidadService::class)->guardar($data, $capture, $record));
        $this->prohibido(fn () => app(ControlCalidadService::class)->eliminar($record, $capture));
        $this->assertNull($record->fresh()->observaciones);
        $this->assertNotSoftDeleted($record);

        $editor = $this->usuario([Cci::VIEW, Cci::HISTORY, Cci::EDIT, Cci::DELETE], 'Rafael Hernández');
        $this->actingAs($editor);
        $editData = $data;
        $editData['observaciones'] = 'Lectura corregida de la hoja de control.';
        $editData['resultados'][0]['valor'] = '6,4';
        $editData['laboratorista_id'] = 999999;
        Livewire::test(Historial::class)->callTableAction('editar', $record, $editData)->assertHasNoTableActionErrors();
        $record->refresh();
        $this->assertEquals($original, $record->only(['fecha_hora', 'area_id', 'registrado_por_id']));
        $this->assertSame('Lectura corregida de la hoja de control.', $record->observaciones);
        $this->assertSame($editor->id, (int) $record->actualizado_por_id);
        $this->assertSame($this->actor->id, (int) $record->laboratorista_id);
        $this->assertSame(6.4, (float) $record->resultados()->sole()->valor);
        Livewire::test(Historial::class)->callTableAction('eliminar', $record)->assertHasNoTableActionErrors();
        $this->assertSoftDeleted($record);
        $this->assertSame(1, Resultado::where('registro_id', $record->id)->count());
    }

    public function test_no_puede_guardar_desde_un_componente_abierto_despues_de_retirarle_el_permiso(): void
    {
        [$area, $prueba, $niveles, $data] = $this->controles(1);
        $component = Livewire::test(RegistroPagina::class)->fillForm($data);
        $this->actor->revokePermissionTo(Cci::REGISTER);
        $this->actor->unsetRelation('permissions');
        $component->call('guardar')->assertForbidden();
        $this->assertSame(0, Registro::count());
    }

    public function test_historial_solo_filtra_area_laboratorista_y_fechas_y_no_incluye_eliminados(): void
    {
        [$area, $prueba, $niveles, $data] = $this->controles(1);
        $visible = app(ControlCalidadService::class)->guardar($data, $this->actor);
        $eliminado = app(ControlCalidadService::class)->guardar($data, $this->actor);
        app(ControlCalidadService::class)->eliminar($eliminado, $this->actor);

        $page = Livewire::test(Historial::class)->assertCanSeeTableRecords([$visible])
            ->assertCanNotSeeTableRecords([$eliminado]);
        $filters = $page->instance()->getTable()->getFilters();
        $this->assertSame(['area_id', 'laboratorista_id', 'fecha_hora'], array_keys($filters));
        $this->assertSame(['desde', 'hasta'], array_map(fn ($field): string => $field->getName(), $filters['fecha_hora']->getFormSchema()));
    }

    public function test_el_modulo_no_escribe_en_los_catalogos_o_resultados_clinicos(): void
    {
        $patient = DB::table('clientes')->insertGetId([
            'NumeroExp' => 'CCI-HISTORICO-01', 'nombre' => 'Paciente', 'apellido' => 'Histórico', 'genero' => 'Femenino', 'estado' => 'Activo', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('ordens')->insert([
            'cliente_id' => $patient, 'estado' => 'pendiente', 'total' => 20, 'fecha' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $before = [];
        foreach (['clientes', 'ordens', 'resultados', 'pruebas', 'examens', 'valor_referencias'] as $table) {
            $before[$table] = DB::table($table)->orderBy('id')->get()->toArray();
        }
        [$area, $prueba, $niveles, $data] = $this->controles(3);
        $record = app(ControlCalidadService::class)->guardar($data, $this->actor);
        $data['observaciones'] = 'Control registrado.';
        app(ControlCalidadService::class)->guardar($data, $this->actor, $record);
        app(ControlCalidadService::class)->eliminar($record->fresh(), $this->actor);
        foreach ($before as $table => $rows) {
            $this->assertEquals($rows, DB::table($table)->orderBy('id')->get()->toArray(), $table);
        }
    }

    public function test_un_editor_no_puede_usar_un_modal_de_historial_despues_de_perder_el_acceso_a_esa_pagina(): void
    {
        [$area, $prueba, $niveles, $data] = $this->controles(1);
        $record = app(ControlCalidadService::class)->guardar($data, $this->actor);
        $editor = $this->usuario([Cci::VIEW, Cci::HISTORY, Cci::EDIT], 'Rafael Hernández');
        $this->actingAs($editor);
        $component = Livewire::test(Historial::class)->mountTableAction('editar', $record)
            ->setTableActionData($data + ['observaciones' => 'No se debe guardar.']);
        $editor->revokePermissionTo(Cci::HISTORY);
        $editor->unsetRelation('permissions');

        $component->call('callMountedTableAction')->assertForbidden();
        $this->assertNull($record->fresh()->observaciones);
    }

    private function usuario(array $permissions, string $name): User
    {
        $user = User::factory()->create(['name' => $name]);
        $user->givePermissionTo(['access_admin_panel', ...$permissions]);

        return $user;
    }

    private function controles(int $count, string $areaName = 'Hematología', string $equipment = 'Hematología 01'): array
    {
        $area = Area::create(['nombre' => $areaName, 'equipo' => $equipment]);
        $product = Producto::firstOrCreate(['nombre' => 'BC-3D Hematology Control']);
        $lot = Lote::firstOrCreate(['producto_id' => $product->id, 'numero' => 'BC-2611-'.$product->id], ['nombre' => 'Control de noviembre', 'caduca_el' => '2027-01-31']);
        $levels = [];
        $specs = [[6.2, 5.7, 6.7], [13.8, 13.1, 14.5], [18.9, 17.9, 19.9], [24, 23, 25]];
        for ($i = 0; $i < $count; $i++) {
            $levels[] = ['nombre' => 'Nivel '.($i + 1), 'orden' => $i + 1, 'lote_id' => $lot->id,
                'esperado' => $specs[$i][0], 'minimo' => $specs[$i][1], 'maximo' => $specs[$i][2]];
        }
        $prueba = app(ControlCalidadService::class)->guardarPrueba([
            'area_id' => $area->id, 'nombre' => 'HEMOGLOBINA', 'unidad' => 'g/dL', 'niveles' => $levels,
        ], $this->actor);
        $niveles = $prueba->niveles()->orderBy('orden')->get();
        $data = ['area_id' => $area->id, 'laboratorista_id' => $this->actor->id, 'observaciones' => null,
            'resultados' => $niveles->map(fn (Nivel $nivel, int $i): array => ['nivel_id' => $nivel->id, 'valor' => (string) $specs[$i][0]])->all()];

        return [$area, $prueba, $niveles, $data];
    }

    private function invalido(callable $callback): void
    {
        try {
            $callback();
        } catch (ValidationException $exception) {
            $this->assertNotEmpty($exception->errors());

            return;
        }
        $this->fail('La captura inválida debe rechazarse sin guardar datos.');
    }

    private function prohibido(callable $callback): void
    {
        try {
            $callback();
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());

            return;
        }
        $this->fail('La acción debe rechazar al usuario sin permiso.');
    }
}
