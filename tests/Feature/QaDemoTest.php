<?php

namespace Tests\Feature;

use App\QaDemo\QaDemoService;
use App\Models\User;
use App\Support\ControlCalidadPermissions;
use Carbon\Carbon;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class QaDemoTest extends TestCase
{
    use RefreshDatabase;

    private string $qaRoot;
    private User $laboratorista;

    protected function setUp(): void
    {
        parent::setUp();
        $this->qaRoot = storage_path('framework/testing/qa-demo-'.Str::uuid());
        config(['qa_demo.path' => $this->qaRoot, 'app.url' => 'https://applab.oncosavi.com']);
        DB::purge('qa_demo');
        $this->travelTo(Carbon::parse('2026-11-10 10:00:00', 'America/El_Salvador'));
        foreach (['access_admin_panel', ...ControlCalidadPermissions::all()] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $this->laboratorista = User::factory()->create(['name' => 'Daniela Martínez', 'nickname' => 'daniela.cci']);
        $this->laboratorista->givePermissionTo(['access_admin_panel', ...ControlCalidadPermissions::all()]);
        $this->actingAs($this->laboratorista);
    }

    protected function tearDown(): void
    {
        DB::purge('qa_demo');
        File::deleteDirectory($this->qaRoot);
        $this->travelBack();
        parent::tearDown();
    }

    public function test_la_ruta_esta_oculta_hasta_preparar_la_demo_y_no_crea_archivos_al_visitarla(): void
    {
        $this->get('/qa-quimica')->assertNotFound();
        $this->get('/qa-quimica/configuracion')->assertNotFound();
        $this->assertDirectoryDoesNotExist($this->qaRoot);

        File::ensureDirectoryExists($this->qaRoot);
        File::put($this->qaRoot.'/enabled.flag', 'enabled');
        $this->get('/qa-quimica')->assertStatus(503);
        $this->assertFileDoesNotExist($this->qaRoot.'/demo.sqlite');
    }

    public function test_preparar_es_idempotente_y_desactivar_no_borra_datos(): void
    {
        $qa = $this->prepare(true);
        $this->assertTrue($qa->ready());
        $this->assertTrue($qa->enabled());
        $this->assertNotEmpty($qa->catalog());
        $this->assertNotEmpty($qa->equipment());
        $this->assertNotEmpty($qa->runs());
        $before = [$qa->catalog(), $qa->equipment(), $qa->configurations(), $qa->runs()];

        $this->artisan('oncosavi:qa-demo-preparar', ['--catalogo-ejemplo' => true])->assertSuccessful();
        $this->assertSame($before, [$qa->catalog(), $qa->equipment(), $qa->configurations(), $qa->runs()]);

        $this->artisan('oncosavi:qa-demo-desactivar')->assertSuccessful();
        $this->assertFalse($qa->enabled());
        $this->get('/qa-quimica')->assertNotFound();
        $this->assertSame($before, [$qa->catalog(), $qa->equipment(), $qa->configurations(), $qa->runs()]);
    }

    public function test_la_configuracion_y_las_mediciones_solo_escriben_en_sqlite_privado(): void
    {
        Schema::create('qa_clinical_sentinel', function (Blueprint $table): void {
            $table->id();
            $table->string('value');
        });
        DB::table('qa_clinical_sentinel')->insert(['value' => 'Órdenes y pacientes intactos']);
        $clinicalTables = DB::select("select name from sqlite_master where type = 'table' order by name");
        $qa = $this->prepare();
        $watch = true;
        $clinicalQueries = [];
        DB::listen(function (QueryExecuted $event) use (&$watch, &$clinicalQueries): void {
            if ($watch && $event->connectionName !== 'qa_demo') {
                $clinicalQueries[] = $event->connectionName.': '.$event->sql;
            }
        });

        [$config, $catalog] = $this->configuration($qa);
        $run = $qa->recordRun($this->runData($config, $catalog));
        $qa->reviewRun($run, ['reviewer' => 'Rafael Hernández', 'decision' => 'reviewed', 'notes' => 'Lecturas verificadas.']);
        $qa->chartData(['configuration_id' => $config, 'catalog_id' => $catalog]);
        $qa->runs();
        $watch = false;

        $this->assertSame([], $clinicalQueries, implode("\n", $clinicalQueries));
        $this->assertSame('Órdenes y pacientes intactos', DB::table('qa_clinical_sentinel')->value('value'));
        $this->assertEquals($clinicalTables, DB::select("select name from sqlite_master where type = 'table' order by name"));
        $this->assertFileExists($this->qaRoot.'/demo.sqlite');
        $this->assertStringNotContainsString('/public/', realpath($this->qaRoot.'/demo.sqlite'));
    }

    public function test_los_ejemplos_ya_instalados_cambian_de_presentacion_sin_reescribir_el_historial(): void
    {
        $qa = $this->prepare(true);
        $db = $qa->connection();
        $configuration = $db->table('qa_configurations')->first();
        $data = json_decode($configuration->data_json, true);
        foreach ($data['analytes'] as &$analyte) {
            $analyte['method'] = 'Método ilustrativo: confirmar con el laboratorio';
        }
        unset($analyte);
        $legacyData = json_encode($data, JSON_UNESCAPED_UNICODE);
        $db->table('qa_configurations')->where('id', $configuration->id)->update(['data_json' => $legacyData]);
        $run = $db->table('qa_runs')->where('configuration_id', $configuration->id)->first();
        $snapshot = json_decode($run->configuration_snapshot, true);
        foreach ($snapshot['analytes'] as &$analyte) {
            $analyte['method'] = 'Método ilustrativo: confirmar con el laboratorio';
        }
        unset($analyte);
        $db->table('qa_runs')->where('id', $run->id)->update([
            'configuration_snapshot' => json_encode($snapshot, JSON_UNESCAPED_UNICODE),
            'notes' => 'Registro ilustrativo para revisar el módulo.',
        ]);
        $before = $db->table('qa_runs')->where('id', $run->id)->first();

        foreach (['/qa-quimica/mantenimiento', '/qa-quimica/mediciones/nueva'] as $url) {
            $this->get($url)->assertOk()->assertSee('Método del equipo')
                ->assertDontSee('confirmar con el laboratorio')->assertDontSee('PRUEBA ABIERTA');
        }
        $this->get('/qa-quimica/mediciones/'.$run->id)->assertOk()
            ->assertSee('Control habitual de química.')->assertDontSee('Registro ilustrativo para revisar el módulo.');
        $csv = $this->get('/qa-quimica/exportar.csv')->assertOk()->streamedContent();
        $this->assertStringContainsString('Control habitual de química.', $csv);
        $this->assertStringNotContainsString('Registro ilustrativo para revisar el módulo.', $csv);
        $this->get('/qa-quimica/informe.pdf')->assertOk();

        $this->assertEquals($before, $db->table('qa_runs')->where('id', $run->id)->first());
        $this->assertSame($legacyData, $db->table('qa_configurations')->where('id', $configuration->id)->value('data_json'));
    }

    public function test_guardar_nuevos_limites_crea_otra_version_y_preserva_la_evaluacion_historica(): void
    {
        $qa = $this->prepare();
        [$first, $catalog, $data] = $this->configuration($qa);
        $runId = $qa->recordRun($this->runData($first, $catalog, ['high' => '337']));
        $before = $qa->run($runId);
        $this->assertSame('outside', $before['comparison']);

        $data['analytes'][0]['targets']['high']['max'] = '350';
        $second = $qa->saveConfiguration($data);

        $this->assertNotSame($first, $second);
        $this->assertGreaterThan($qa->configuration($first)['version'], $qa->configuration($second)['version']);
        $this->assertFalse((bool) $qa->configuration($first)['active']);
        $this->assertTrue((bool) $qa->configuration($second)['active']);
        $after = $qa->run($runId);
        $this->assertSame($before['measurements'], $after['measurements']);
        $this->assertSame($before['configuration_snapshot'], $after['configuration_snapshot']);
        $this->assertSame('outside', $after['comparison']);
        $this->assertEquals(330, $this->measurement($after, 'high')['max']);

        $newRun = $qa->recordRun($this->runData($second, $catalog, ['high' => '337']));
        $this->assertSame('within', $qa->run($newRun)['comparison']);
    }

    public function test_no_acepta_limites_invertidos_ni_una_prueba_ajena_al_catalogo_privado(): void
    {
        $qa = $this->prepare();
        [, , $data] = $this->configuration($qa);
        $before = count($qa->configurations());
        $data['analytes'][0]['targets']['low']['min'] = '61';
        $this->assertInvalid(fn () => $qa->saveConfiguration($data));
        $this->assertCount($before, $qa->configurations());

        $data['analytes'][0]['targets']['low']['min'] = '54';
        $data['analytes'][0]['catalog_id'] = 99999999;
        $this->assertInvalid(fn () => $qa->saveConfiguration($data));
        $this->assertCount($before, $qa->configurations());
    }

    public function test_la_vigencia_se_evalua_en_la_fecha_de_medicion_y_usa_el_limite_del_frasco(): void
    {
        $qa = $this->prepare();
        [$config, $catalog, $data] = $this->configuration($qa);
        foreach ($data['levels'] as &$level) {
            $level['opened_on'] = '2026-11-01';
            $level['expires_on'] = '2027-01-31';
            $level['stability_days'] = 2;
        }
        unset($level);
        $short = $qa->saveConfiguration($data);
        $this->assertStringStartsWith('2026-11-03', (string) $qa->configuration($short)['levels']['high']['use_until']);

        $this->assertInvalid(fn () => $qa->recordRun($this->runData($short, $catalog)));
        $historical = $this->runData($short, $catalog);
        $historical['measured_at'] = '2026-11-02T08:05';
        $run = $qa->recordRun($historical);
        $this->assertSame('within', $qa->run($run)['comparison']);

        $beforeOpening = $this->runData($config, $catalog);
        $beforeOpening['measured_at'] = '2026-10-31T08:05';
        $this->assertInvalid(fn () => $qa->recordRun($beforeOpening));
    }

    public function test_conserva_coma_decimal_cero_y_hora_real_sin_confundirlos_con_datos_omitidos(): void
    {
        $qa = $this->prepare();
        [$config, $catalog] = $this->configuration($qa);
        $data = $this->runData($config, $catalog, ['low' => '59,4', 'medium' => '0', 'high' => '298.25']);
        $run = $qa->run($qa->recordRun($data));

        $this->assertSame('59,4', $this->measurement($run, 'low')['raw_value']);
        $this->assertEqualsWithDelta(59.4, $this->measurement($run, 'low')['value'], 0.000001);
        $this->assertSame('0', $this->measurement($run, 'medium')['raw_value']);
        $this->assertSame(0.0, (float) $this->measurement($run, 'medium')['value']);
        $this->assertSame('outside', $this->measurement($run, 'medium')['status']);
        $this->assertEqualsWithDelta(298.25, $this->measurement($run, 'high')['value'], 0.000001);
        $this->assertSame('outside', $run['comparison']);
        $this->assertSame('Daniela Martínez', $run['operator']);
        $this->assertStringContainsString('2026-11-09', $run['measured_at']);
        $this->assertNotSame($run['measured_at'], $run['captured_at']);
    }

    public function test_exige_nombre_declarado_y_rechaza_fecha_futura_o_valor_no_numerico(): void
    {
        $qa = $this->prepare();
        [$config, $catalog] = $this->configuration($qa);
        foreach (['operator' => '', 'measured_at' => '2026-11-12T08:05'] as $field => $value) {
            $data = $this->runData($config, $catalog);
            $data[$field] = $value;
            $this->assertInvalid(fn () => $qa->recordRun($data));
        }
        $this->assertInvalid(fn () => $qa->recordRun($this->runData($config, $catalog, ['high' => 'NEGATIVO'])));
        $this->assertSame([], $qa->runs());
    }

    public function test_las_lecturas_omitidas_necesitan_motivo_y_no_se_guardan_como_cero(): void
    {
        $qa = $this->prepare();
        [$config, $catalog] = $this->configuration($qa);
        $data = $this->runData($config, $catalog, ['low' => '']);
        $this->assertInvalid(fn () => $qa->recordRun($data));
        $data['missing_reason'] = 'No se procesó el nivel bajo; queda pendiente de revisión.';
        $run = $qa->run($qa->recordRun($data));
        $missing = $this->measurement($run, 'low');

        $this->assertNull($missing['value']);
        $this->assertSame('missing', $missing['status']);
        $this->assertSame('incomplete', $run['comparison']);
        $chart = $qa->chartData(['configuration_id' => $config, 'catalog_id' => $catalog]);
        $this->assertSame([], $chart['levels']['low']['points']);
        $this->assertCount(1, $chart['levels']['medium']['points']);
    }

    public function test_un_borrador_puede_completarse_pero_una_medicion_presentada_es_inmutable(): void
    {
        $qa = $this->prepare();
        [$config, $catalog] = $this->configuration($qa);
        $draft = $this->runData($config, $catalog, ['low' => '', 'medium' => '', 'high' => '298']);
        $id = $qa->recordRun($draft, true);
        $this->assertSame('draft', $qa->run($id)['status']);

        $data = $this->runData($config, $catalog);
        $this->assertSame($id, $qa->recordRun($data, false, $id));
        $saved = $qa->run($id);
        $this->assertSame('submitted', $saved['status']);
        $this->assertSame('within', $saved['comparison']);
        $this->assertInvalid(fn () => $qa->recordRun($this->runData($config, $catalog, ['high' => '310']), false, $id));
        $this->assertSame($saved['measurements'], $qa->run($id)['measurements']);
    }

    public function test_la_repeticion_guarda_solo_la_lectura_repetida_y_conserva_la_original(): void
    {
        $qa = $this->prepare();
        [$config, $catalog] = $this->configuration($qa);
        $originalId = $qa->recordRun($this->runData($config, $catalog, ['high' => '337']));
        $original = $qa->run($originalId);
        $repeat = $this->runData($config, $catalog);
        $repeat['values'] = [$catalog => ['high' => '302']];
        $repeat['kind'] = 'repeat';
        $repeat['parent_run_id'] = $originalId;
        $repeat['measured_at'] = '2026-11-09T08:12';
        $repeat['notes'] = 'Nueva medición posterior a la revisión del control.';
        $after = $qa->run($qa->recordRun($repeat));

        $this->assertSame($originalId, $after['parent_run_id']);
        $this->assertCount(1, $after['measurements']);
        $this->assertSame('high', $after['measurements'][0]['level']);
        $this->assertSame('302', $after['measurements'][0]['raw_value']);
        $this->assertSame('within', $after['comparison']);
        $this->assertSame($original['measurements'], $qa->run($originalId)['measurements']);

        $chart = $qa->chartData(['configuration_id' => $config, 'catalog_id' => $catalog]);
        $this->assertCount(2, $chart['levels']['high']['points']);
        $this->assertEquals([337, 302], array_column($chart['levels']['high']['points'], 'value'));
    }

    public function test_una_correccion_conserva_el_dato_anterior_y_no_admite_un_padre_de_otro_equipo(): void
    {
        $qa = $this->prepare();
        [$config, $catalog] = $this->configuration($qa);
        $original = $qa->recordRun($this->runData($config, $catalog, ['high' => '337']));
        $correction = $this->runData($config, $catalog);
        $correction['kind'] = 'correction';
        $correction['parent_run_id'] = $original;
        $correction['notes'] = 'Corrección de transcripción comprobada contra la impresión del equipo.';
        $correction['values'] = [$catalog => ['high' => '330']];
        $fixed = $qa->run($qa->recordRun($correction));
        $this->assertSame($original, $fixed['parent_run_id']);
        $this->assertSame('337', $this->measurement($qa->run($original), 'high')['raw_value']);
        $this->assertSame('330', $fixed['measurements'][0]['raw_value']);
        $this->assertSame($qa->run($original)['measured_at'], $fixed['measured_at']);
        $this->assertNotSame($fixed['measured_at'], $fixed['captured_at']);

        $wrongTime = $correction;
        $wrongTime['measured_at'] = '2026-11-09T08:13';
        $this->assertInvalid(fn () => $qa->recordRun($wrongTime));
        $this->assertSame(2, $qa->countRuns());

        [$otherConfig] = $this->configuration($qa);
        $correction['configuration_id'] = $otherConfig;
        $this->assertInvalid(fn () => $qa->recordRun($correction));
    }

    public function test_una_repeticion_vinculada_no_puede_anadir_otra_prueba_o_nivel_que_su_padre_no_midio(): void
    {
        $qa = $this->prepare();
        [, $glucose, $configurationData] = $this->configuration($qa);
        $creatinine = collect($qa->catalog())->firstWhere('name', 'CREATININA SERICA');
        $this->assertNotNull($creatinine);
        $configurationData['analytes'][] = [
            'catalog_id' => $creatinine['id'], 'method' => 'Método ilustrativo', 'unit' => 'mg/dL',
            'targets' => [
                'low' => ['target' => '0,8', 'min' => '0,64', 'max' => '0,96'],
                'medium' => ['target' => '1,8', 'min' => '1,53', 'max' => '2,07'],
                'high' => ['target' => '4,5', 'min' => '3,8', 'max' => '5,2'],
            ],
        ];
        $configuration = $qa->saveConfiguration($configurationData);
        $routine = $this->runData($configuration, $glucose, ['high' => '337']);
        $routine['values'][$creatinine['id']] = ['low' => '0,82', 'medium' => '1,78', 'high' => '4,48'];
        $original = $qa->recordRun($routine);

        $repeat = $this->runData($configuration, $glucose);
        $repeat['kind'] = 'repeat';
        $repeat['parent_run_id'] = $original;
        $repeat['measured_at'] = '2026-11-09T08:12';
        $repeat['values'] = [$glucose => ['high' => '302']];
        $glucoseRepeat = $qa->recordRun($repeat);

        $another = $repeat;
        $another['parent_run_id'] = $glucoseRepeat;
        $another['measured_at'] = '2026-11-09T08:20';
        $another['values'] = [$creatinine['id'] => ['high' => '4,5']];
        $this->assertInvalid(fn () => $qa->recordRun($another));
        $another['values'] = [$glucose => ['low' => '60']];
        $this->assertInvalid(fn () => $qa->recordRun($another));
        $this->assertSame(2, $qa->countRuns());
        $this->assertCount(1, $qa->run($glucoseRepeat)['measurements']);
        $this->assertSame('high', $qa->run($glucoseRepeat)['measurements'][0]['level']);
    }

    public function test_revisar_solo_agrega_la_revision_sin_cambiar_mediciones_ni_sus_limites(): void
    {
        $qa = $this->prepare();
        [$config, $catalog] = $this->configuration($qa);
        $id = $qa->recordRun($this->runData($config, $catalog));
        $before = $qa->run($id);
        $this->assertInvalid(fn () => $qa->reviewRun($id, ['reviewer' => '', 'decision' => 'reviewed', 'notes' => 'Revisión.']));
        $qa->reviewRun($id, ['reviewer' => 'Rafael Hernández', 'decision' => 'followup', 'notes' => 'Verificar la documentación del control.']);
        $qa->reviewRun($id, ['reviewer' => 'Rafael Hernández', 'decision' => 'reviewed', 'notes' => 'Documentación y lecturas verificadas.']);
        $after = $qa->run($id);

        $this->assertCount(2, $after['reviews']);
        $this->assertSame('reviewed', $after['review_status']);
        $this->assertSame($before['measurements'], $after['measurements']);
        $this->assertSame($before['configuration_snapshot'], $after['configuration_snapshot']);
        $this->assertSame($before['operator'], $after['operator']);
        $this->assertSame($before['captured_at'], $after['captured_at']);
        $reviewedAt = Carbon::parse($after['reviews'][1]['reviewed_at'])->format('d/m/Y H:i');
        $this->get('/qa-quimica/mediciones/'.$id)->assertOk()->assertSee($reviewedAt);
    }

    public function test_los_eventos_se_vinculan_al_equipo_y_a_las_mediciones_anteriores_y_posteriores(): void
    {
        $qa = $this->prepare();
        [$config, $catalog] = $this->configuration($qa);
        $equipment = $qa->configuration($config)['equipment_id'];
        $event = $qa->recordEvent([
            'equipment_id' => $equipment, 'type' => 'calibration',
            'occurred_at' => '2026-11-09T08:00', 'operator' => 'Rafael Hernández',
            'notes' => 'Evento registrado según el procedimiento del equipo; no ejecutado desde Oncosavi.',
        ]);
        foreach (['before_event' => '2026-11-09T07:45', 'after_event' => '2026-11-09T08:12'] as $kind => $when) {
            $data = $this->runData($config, $catalog);
            $data['kind'] = $kind;
            $data['event_id'] = $event;
            $data['measured_at'] = $when;
            $run = $qa->run($qa->recordRun($data));
            $this->assertSame($event, $run['event_id']);
            $this->assertSame($kind, $run['kind']);
        }
        [$other] = $this->configuration($qa);
        $wrong = $this->runData($other, $catalog);
        $wrong['kind'] = 'after_event';
        $wrong['event_id'] = $event;
        $this->assertInvalid(fn () => $qa->recordRun($wrong));
    }

    public function test_las_graficas_separan_versiones_y_respetan_las_fechas_del_filtro(): void
    {
        $qa = $this->prepare();
        [$first, $catalog, $data] = $this->configuration($qa);
        $oldData = $this->runData($first, $catalog);
        $oldData['measured_at'] = '2026-11-06T08:05';
        $oldRun = $qa->recordRun($oldData);
        $current = $qa->recordRun($this->runData($first, $catalog));
        $data['levels']['high']['lot'] = 'QCQ-2612H';
        $data['analytes'][0]['targets']['high']['target'] = '310';
        $second = $qa->saveConfiguration($data);
        $new = $qa->recordRun($this->runData($second, $catalog, ['high' => '310']));

        $chart = $qa->chartData(['configuration_id' => $first, 'catalog_id' => $catalog, 'from' => '2026-11-09', 'to' => '2026-11-09']);
        $this->assertSame($first, $chart['configuration']['id']);
        $this->assertEquals(300, $chart['levels']['high']['target']);
        $this->assertEquals([$current], array_column($chart['levels']['high']['points'], 'run_id'));
        $this->assertNotContains($oldRun, array_column($chart['levels']['high']['points'], 'run_id'));
        $this->assertNotContains($new, array_column($chart['levels']['high']['points'], 'run_id'));
        $next = $qa->chartData(['configuration_id' => $second, 'catalog_id' => $catalog]);
        $this->assertEquals(310, $next['levels']['high']['target']);
        $this->assertEquals([$new], array_column($next['levels']['high']['points'], 'run_id'));
    }

    public function test_un_filtro_de_grafica_no_puede_presentar_la_configuracion_de_otro_equipo(): void
    {
        $qa = $this->prepare();
        [$first, $catalog] = $this->configuration($qa);
        [$other] = $this->configuration($qa);
        $otherEquipment = $qa->configuration($other)['equipment_id'];
        $qa->recordRun($this->runData($first, $catalog));
        $this->assertInvalid(fn () => $qa->chartData([
            'configuration_id' => $first, 'equipment_id' => $otherEquipment, 'catalog_id' => $catalog,
        ]));
        $this->assertSame(0, $qa->countRuns(['equipment_id' => $otherEquipment]));
        $this->assertSame(1, $qa->countRuns(['configuration_id' => $first]));
    }

    public function test_el_limite_de_grafica_informa_el_total_sin_ocultar_que_hay_mas_registros(): void
    {
        $qa = $this->prepare();
        [$config, $catalog] = $this->configuration($qa);
        $data = $this->runData($config, $catalog);
        $qa->connection()->transaction(function () use ($qa, $data): void {
            for ($i = 0; $i < 501; $i++) {
                $qa->recordRun($data);
            }
        });
        $this->assertSame(501, $qa->countRuns(['configuration_id' => $config]));
        $this->assertCount(500, $qa->runs(['configuration_id' => $config]));
        $chart = $qa->chartData(['configuration_id' => $config, 'catalog_id' => $catalog]);
        $this->assertSame(501, $chart['total_runs']);
        $this->assertTrue($chart['truncated']);
        $this->assertCount(500, $chart['levels']['high']['points']);
    }

    public function test_el_personal_autorizado_usa_las_cinco_pantallas_con_su_sesion_principal(): void
    {
        $qa = $this->prepare();
        [$config, $catalog] = $this->configuration($qa);
        $before = [config('session.driver'), config('session.cookie'), config('session.files'), config('cache.default')];
        $urls = [
            '/qa-quimica', '/qa-quimica/mantenimiento', '/qa-quimica/mediciones',
            '/qa-quimica/mediciones/nueva?configuration_id='.$config,
            '/qa-quimica/graficas?configuration_id='.$config.'&catalog_id='.$catalog,
        ];
        foreach ($urls as $url) {
            $response = $this->get($url)->assertOk();
            $this->assertStringContainsString('noindex', $response->headers->get('X-Robots-Tag'));
            $this->assertStringContainsString('nofollow', $response->headers->get('X-Robots-Tag'));
            $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
            $response->assertDontSee('action="https://applab.oncosavi.com/admin/login"', false);
            $this->assertSame($before, [config('session.driver'), config('session.cookie'), config('session.files'), config('cache.default')]);
        }
        $this->get('/qa-quimica/mediciones/999999999')->assertNotFound();
        $this->assertAuthenticatedAs($this->laboratorista);
    }

    public function test_la_captura_autenticada_guarda_solo_datos_cci_y_no_cambia_el_catalogo_clinico(): void
    {
        $qa = $this->prepare();
        [$config, $catalog] = $this->configuration($qa);
        $clinical = [DB::table('pruebas')->count(), DB::table('ordens')->count(), DB::table('clientes')->count()];
        $before = [config('database.default'), config('session.driver'), config('session.cookie'), config('cache.default')];
        $this->get('/qa-quimica')->assertOk();
        $this->get('/qa-quimica/mantenimiento')->assertOk();
        $this->post('/qa-quimica/mediciones', $this->runData($config, $catalog)+['action' => 'submit'])->assertRedirect();
        $this->assertCount(1, $qa->runs());
        $this->assertSame($clinical, [DB::table('pruebas')->count(), DB::table('ordens')->count(), DB::table('clientes')->count()]);
        $this->assertSame($before, [config('database.default'), config('session.driver'), config('session.cookie'), config('cache.default')]);
    }

    public function test_el_detalle_escapa_notas_y_la_exportacion_solo_contiene_datos_qa(): void
    {
        $qa = $this->prepare();
        [$config, $catalog] = $this->configuration($qa);
        $data = $this->runData($config, $catalog);
        $data['notes'] = '<script>alert("nota")</script> & texto conservado';
        $id = $qa->recordRun($data);
        $this->get('/qa-quimica/mediciones/'.$id)->assertOk()
            ->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert("nota")</script>', false);

        $csv = $this->get('/qa-quimica/exportar.csv?configuration_id='.$config)->assertOk();
        $body = $csv->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $body);
        $this->assertStringContainsString('GLUCOSA EN AYUNA', $body);
        $this->assertStringContainsString('Daniela Martínez', $body);
        $this->assertStringNotContainsString('cliente_id', $body);

        $pdf = $this->get('/qa-quimica/informe.pdf?configuration_id='.$config)->assertOk();
        $this->assertStringStartsWith('application/pdf', $pdf->headers->get('Content-Type'));
        $content = $pdf->getContent();
        if ($content === false && $pdf->baseResponse instanceof \Symfony\Component\HttpFoundation\StreamedResponse) {
            $content = $pdf->streamedContent();
        }
        $this->assertStringStartsWith('%PDF-', $content);
    }

    public function test_los_valores_de_texto_no_se_exportan_como_formulas_de_excel(): void
    {
        $qa = $this->prepare();
        [$config, $catalog] = $this->configuration($qa, ['equipment_name' => '=1+1']);
        $qa->recordRun($this->runData($config, $catalog));
        $csv = $this->get('/qa-quimica/exportar.csv?configuration_id='.$config)->assertOk()->streamedContent();
        $this->assertStringContainsString("'=1+1", $csv);
    }

    private function prepare(bool $examples = false): QaDemoService
    {
        $options = ['--catalogo-ejemplo' => true];
        if (! $examples) {
            $options['--sin-ejemplos'] = true;
        }
        $this->artisan('oncosavi:qa-demo-preparar', $options)->assertSuccessful();

        return app(QaDemoService::class);
    }

    private function configuration(QaDemoService $qa, array $overrides = []): array
    {
        $catalog = collect($qa->catalog())->first(fn (array $row) => $row['name'] === 'GLUCOSA EN AYUNA');
        $this->assertNotNull($catalog, 'El catálogo ilustrativo debe incluir la prueba de glucosa del catálogo existente.');
        $equipment = $qa->createEquipment([
            'name' => $overrides['equipment_name'] ?? 'Química '.str_pad((string) (count($qa->equipment()) + 1), 2, '0', STR_PAD_LEFT),
            'code' => 'Q-'.Str::upper(Str::random(7)), 'model' => 'Mindray BS-240 (demostración)',
            'notes' => 'Equipo ficticio para la prueba aislada.', 'active' => true,
        ]);
        $levels = [];
        foreach (['low' => 'L', 'medium' => 'M', 'high' => 'H'] as $name => $suffix) {
            $levels[$name] = [
                'lot' => 'QCQ-2611'.$suffix, 'product' => 'Suero de control multianalito',
                'expires_on' => '2027-01-31', 'opened_on' => '2026-11-01', 'stability_days' => 30,
            ];
        }
        $data = [
            'equipment_id' => $equipment, 'configured_by' => 'Rafael Hernández',
            'label' => 'Controles de química noviembre', 'levels' => $levels,
            'analytes' => [[
                'catalog_id' => $catalog['id'], 'method' => 'Hexoquinasa (ilustrativo)', 'unit' => 'mg/dL',
                'targets' => [
                    'low' => ['target' => '60', 'min' => '54', 'max' => '66'],
                    'medium' => ['target' => '110', 'min' => '99', 'max' => '121'],
                    'high' => ['target' => '300', 'min' => '270', 'max' => '330'],
                ],
            ]],
        ];

        return [$qa->saveConfiguration($data), $catalog['id'], $data];
    }

    private function runData(int $config, int $catalog, array $values = []): array
    {
        return [
            'configuration_id' => $config, 'measured_at' => '2026-11-09T08:05',
            'operator' => 'Daniela Martínez', 'kind' => 'routine',
            'notes' => '', 'missing_reason' => '',
            'values' => [$catalog => array_replace(['low' => '59,4', 'medium' => '109,2', 'high' => '298'], $values)],
        ];
    }

    private function measurement(array $run, string $level): array
    {
        return collect($run['measurements'])->firstWhere('level', $level);
    }

    private function assertInvalid(callable $action): void
    {
        try {
            $action();
            $this->fail('Se esperaba una validación que impidiera guardar datos inválidos.');
        } catch (ValidationException $error) {
            $this->assertNotEmpty($error->errors());
        }
    }
}
