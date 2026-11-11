<?php

namespace Tests\Feature;

use App\Models\User;
use App\QaDemo\QaDemoCatalogImporter;
use App\QaDemo\QaDemoService;
use App\Support\ControlCalidadPermissions as Cci;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ControlCalidadInternoTest extends TestCase
{
    use RefreshDatabase;

    private string $qaRoot;
    private QaDemoService $qa;
    private User $operator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->qaRoot = storage_path('framework/testing/cci-'.Str::uuid());
        config(['qa_demo.path' => $this->qaRoot, 'app.url' => 'https://applab.oncosavi.com']);
        DB::purge('qa_demo');
        $this->travelTo(Carbon::parse('2026-11-10 10:00:00', 'America/El_Salvador'));
        foreach (['access_admin_panel', ...Cci::all()] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $this->operator = $this->user(Cci::all(), 'Daniela Martínez');
        $this->actingAs($this->operator);
        $this->artisan('oncosavi:qa-demo-preparar', ['--catalogo-ejemplo' => true, '--sin-ejemplos' => true])->assertSuccessful();
        $this->qa = app(QaDemoService::class);
    }

    protected function tearDown(): void
    {
        DB::purge('qa_demo');
        File::deleteDirectory($this->qaRoot);
        $this->travelBack();
        parent::tearDown();
    }

    public function test_la_url_independiente_ya_no_permite_consultas_ni_capturas_anonimas(): void
    {
        [$configuration, $catalog, $values] = $this->configuration(3);
        $this->app['auth']->guard('web')->logout();

        foreach (['', '/mantenimiento', '/mediciones/nueva', '/mediciones', '/graficas', '/exportar.csv', '/informe.pdf'] as $suffix) {
            $this->get('/qa-quimica'.$suffix)->assertRedirect('/admin/login');
        }
        $this->post('/qa-quimica/mediciones', $this->runData($configuration, $catalog, $values))->assertRedirect('/admin/login');
        $this->assertSame(0, $this->qa->countRuns());
    }

    public function test_el_permiso_general_no_otorga_acciones_y_las_urls_no_evaden_los_permisos(): void
    {
        [$configuration, $catalog, $values] = $this->configuration(3);
        $run = $this->qa->recordRun($this->runData($configuration, $catalog, $values));
        $before = $this->qa->run($run);
        $reader = $this->user([Cci::VIEW], 'Rafael Hernández');
        $this->actingAs($reader);

        $this->get('/qa-quimica')->assertOk()->assertSee('Control de Calidad Interno');
        foreach (['/mantenimiento', '/mediciones/nueva', '/mediciones', '/graficas', '/exportar.csv', '/informe.pdf', '/mediciones/'.$run.'/editar'] as $suffix) {
            $this->get('/qa-quimica'.$suffix)->assertForbidden();
        }
        $this->post('/qa-quimica/equipos', ['area' => 'Hematología', 'name' => 'Hematología 02', 'code' => 'H-02', 'model' => 'BC-20'])->assertForbidden();
        $this->post('/qa-quimica/mediciones', $this->runData($configuration, $catalog, $values))->assertForbidden();
        $this->put('/qa-quimica/mediciones/'.$run, $this->runData($configuration, $catalog, $values))->assertForbidden();
        $this->delete('/qa-quimica/mediciones/'.$run, ['reason' => 'No autorizado'])->assertForbidden();
        $this->post('/qa-quimica/mediciones/'.$run.'/revision', ['decision' => 'reviewed', 'notes' => 'No autorizado'])->assertForbidden();
        $this->assertEquals($before, $this->qa->run($run));

        $withoutModule = $this->user([Cci::REGISTER], 'María Hernández');
        $this->actingAs($withoutModule)->get('/qa-quimica')->assertForbidden();
        $this->post('/qa-quimica/mediciones', $this->runData($configuration, $catalog, $values))->assertForbidden();
    }

    public function test_cci_no_otorga_acceso_a_usuarios_sin_permiso_del_panel(): void
    {
        $this->operator->revokePermissionTo('access_admin_panel');
        $this->operator->unsetRelation('permissions');
        $this->actingAs($this->operator)->get('/qa-quimica')->assertForbidden();
    }

    #[DataProvider('levelCounts')]
    public function test_configura_y_registra_cualquier_cantidad_de_niveles_sin_convertirlos_en_tres(int $count): void
    {
        [$configuration, $catalog, $values] = $this->configuration($count);
        $config = $this->qa->configuration($configuration);
        $this->assertCount($count, $config['levels']);
        $this->assertSame('Hematología', $config['equipment']['area']);
        $this->post('/qa-quimica/mediciones', $this->runData($configuration, $catalog, $values))->assertRedirect();
        $run = $this->qa->runs()[0];
        $this->assertCount($count, $run['measurements']);
        $chart = $this->qa->chartData(['configuration_id' => $configuration, 'catalog_id' => $catalog]);
        $this->assertCount($count, $chart['levels']);
        foreach ($values as $level => $value) {
            $this->assertSame('Nivel '.substr($level, 6), $chart['levels'][$level]['label']);
            $this->assertCount(1, $chart['levels'][$level]['points']);
        }
        $this->get('/qa-quimica/graficas?configuration_id='.$configuration.'&catalog_id='.$catalog)->assertOk();
        $csv = $this->get('/qa-quimica/exportar.csv?configuration_id='.$configuration)->assertOk()->streamedContent();
        $this->assertStringContainsString('Nivel '.$count, $csv);
        $this->get('/qa-quimica/informe.pdf?configuration_id='.$configuration.'&catalog_id='.$catalog)->assertOk();
    }

    public static function levelCounts(): array
    {
        return ['un nivel' => [1], 'tres niveles' => [3], 'cuatro niveles' => [4]];
    }

    public function test_crear_pruebas_y_lotes_en_cci_no_modifica_el_catalogo_de_pacientes(): void
    {
        $clinical = [DB::table('pruebas')->count(), DB::table('examens')->count(), DB::table('valor_referencias')->count()];
        $this->post('/qa-quimica/productos', ['name' => 'BC-3D Hematology Control'])->assertRedirect();
        $product = collect($this->qa->products())->firstWhere('name', 'BC-3D Hematology Control');
        $this->assertNotNull($product);
        $this->post('/qa-quimica/lotes', ['product_id' => $product['id'], 'name' => 'Nivel normal noviembre', 'lot' => 'BC2611N', 'expires_on' => '2027-02-28'])->assertRedirect();
        $this->post('/qa-quimica/pruebas', ['name' => 'LEUCOCITOS CONTROL', 'exam_name' => 'HEMOGRAMA CONTROL', 'unit' => '10^9/L', 'area' => 'Hematología'])->assertRedirect();
        $test = collect($this->qa->catalog())->firstWhere('name', 'LEUCOCITOS CONTROL');
        $this->assertNotNull($test);
        $this->assertTrue($test['is_local']);
        $this->assertSame($clinical, [DB::table('pruebas')->count(), DB::table('examens')->count(), DB::table('valor_referencias')->count()]);
        $this->artisan('oncosavi:qa-demo-preparar', ['--catalogo-ejemplo' => true, '--sin-ejemplos' => true])->assertSuccessful();
        $this->assertNotNull(collect($this->qa->catalog())->firstWhere('id', $test['id']));
    }

    public function test_la_fecha_y_el_usuario_de_captura_se_obtienen_del_servidor(): void
    {
        [$configuration, $catalog, $values] = $this->configuration(3);
        $data = $this->runData($configuration, $catalog, $values);
        $data['operator'] = 'Persona suplantada';
        $data['actor_id'] = 999999;
        $data['actor_name'] = 'Actor suplantado';
        $data['measured_at'] = '2040-01-01T00:00';
        $this->post('/qa-quimica/mediciones', $data)->assertRedirect();
        $run = $this->qa->runs()[0];
        $this->assertSame('Daniela Martínez', $run['operator']);
        $this->assertSame($this->operator->id, (int) $run['operator_user_id']);
        $this->assertSame($this->operator->id, (int) $run['created_by']);
        $this->assertSame('2026-11-10 10:00:00', Carbon::parse($run['measured_at'])->format('Y-m-d H:i:s'));
    }

    public function test_importa_quimica_y_hematologia_solo_como_catalogo_sin_copiar_referencias_clinicas(): void
    {
        $ids = [];
        foreach (['Química sanguínea', 'Hematología', 'Coprología'] as $area) {
            $type = DB::table('tipo_examens')->insertGetId(['nombre' => $area]);
            $exam = DB::table('examens')->insertGetId(['nombre' => 'Examen '.$area, 'tipo_examen_id' => $type, 'precio' => 15, 'es_externo' => false]);
            $test = DB::table('pruebas')->insertGetId(['nombre' => 'Prueba '.$area, 'examen_id' => $exam, 'estado' => 'activo']);
            DB::table('valor_referencias')->insert(['prueba_id' => $test, 'operador' => 'rango', 'valor_min' => 999, 'valor_max' => 9999, 'unidades' => 'U/L']);
            $ids[$area] = $test;
        }
        $before = DB::table('valor_referencias')->orderBy('id')->get()->toArray();
        $rows = app(QaDemoCatalogImporter::class)->read();
        $this->assertEqualsCanonicalizing([$ids['Química sanguínea'], $ids['Hematología']], array_column($rows, 'source_id'));
        foreach ($rows as $row) {
            $this->assertSame('U/L', $row['unit']);
            $this->assertArrayNotHasKey('valor_min', $row);
            $this->assertArrayNotHasKey('valor_max', $row);
        }
        $this->qa->importCatalog($rows);
        $this->assertNotNull(collect($this->qa->catalog())->firstWhere('source_id', $ids['Hematología']));
        $this->assertNull(collect($this->qa->catalog())->firstWhere('source_id', $ids['Coprología']));
        $this->assertEquals($before, DB::table('valor_referencias')->orderBy('id')->get()->toArray());
    }

    public function test_solo_el_permiso_de_asignacion_permite_seleccionar_otro_laboratorista(): void
    {
        [$configuration, $catalog, $values] = $this->configuration(1);
        $other = $this->user([Cci::VIEW, Cci::REGISTER], 'Carolina Méndez');
        $this->operator->revokePermissionTo(Cci::ASSIGN_OPERATOR);
        $this->operator->unsetRelation('permissions');
        $data = $this->runData($configuration, $catalog, $values) + ['operator_user_id' => $other->id];
        $this->post('/qa-quimica/mediciones', $data)->assertForbidden();
        $this->assertSame(0, $this->qa->countRuns());

        $this->operator->givePermissionTo(Cci::ASSIGN_OPERATOR);
        $this->operator->unsetRelation('permissions');
        $this->post('/qa-quimica/mediciones', $data)->assertRedirect();
        $run = $this->qa->runs()[0];
        $this->assertSame('Carolina Méndez', $run['operator']);
        $this->assertSame($other->id, (int) $run['operator_user_id']);
        $this->assertSame($this->operator->id, (int) $run['created_by']);
    }

    public function test_editar_conserva_el_original_en_auditoria_y_rechaza_una_revision_obsoleta(): void
    {
        [$configuration, $catalog, $values] = $this->configuration(3);
        $id = $this->qa->recordRun($this->runData($configuration, $catalog, $values));
        $before = $this->qa->run($id);
        $data = $this->runData($configuration, $catalog, $values);
        $data['values'][$catalog]['level_3'] = '16,5';
        $data['notes'] = 'Corrección de la lectura transcrita.';
        $data['expected_revision'] = $before['revision'];
        $this->put('/qa-quimica/mediciones/'.$id, $data)->assertRedirect();
        $after = $this->qa->run($id);
        $this->assertSame($before['measured_at'], $after['measured_at']);
        $this->assertSame($before['revision'] + 1, $after['revision']);
        $this->assertSame($this->operator->id, (int) $after['updated_by']);
        $this->assertSame('16,5', collect($after['measurements'])->firstWhere('level', 'level_3')['raw_value']);
        $this->assertCount(1, $after['audit']);
        $this->assertStringContainsString('15,25', json_encode($after['audit'], JSON_UNESCAPED_UNICODE));

        $data['values'][$catalog]['level_3'] = '18';
        $this->putJson('/qa-quimica/mediciones/'.$id, $data)->assertUnprocessable();
        $this->assertEquals($after, $this->qa->run($id));
    }

    public function test_eliminar_retira_el_control_de_graficas_pero_conserva_el_registro_y_su_autoria(): void
    {
        [$configuration, $catalog, $values] = $this->configuration(1);
        $id = $this->qa->recordRun($this->runData($configuration, $catalog, $values));
        $before = $this->qa->run($id);
        $this->delete('/qa-quimica/mediciones/'.$id, ['reason' => 'Captura duplicada.', 'expected_revision' => $before['revision']])->assertRedirect();
        $after = $this->qa->run($id);
        $this->assertNotNull($after['deleted_at']);
        $this->assertSame('Captura duplicada.', $after['deleted_reason']);
        $this->assertSame($this->operator->id, (int) $after['deleted_by']);
        $this->assertSame($before['measurements'], $after['measurements']);
        $this->assertCount(1, $after['audit']);
        $this->assertSame(0, $this->qa->countRuns());
        $this->assertSame(1, $this->qa->countRuns(['include_deleted' => true]));
        $chart = $this->qa->chartData(['configuration_id' => $configuration, 'catalog_id' => $catalog]);
        $this->assertSame([], $chart['levels']['level_1']['points']);
    }

    public function test_editar_no_permite_cambiar_el_linaje_ni_crear_un_ciclo_con_un_padre_forjado(): void
    {
        [$configuration, $catalog, $values] = $this->configuration(1);
        $originalId = $this->qa->recordRun($this->runData($configuration, $catalog, $values));
        $original = $this->qa->run($originalId);
        $repeatData = $this->runData($configuration, $catalog, ['level_1' => '5,4']);
        $repeatData['kind'] = 'repeat';
        $repeatData['parent_run_id'] = $originalId;
        $repeatData['measured_at'] = '2026-11-10T09:10';
        $repeatId = $this->qa->recordRun($repeatData);

        foreach ([$originalId, $repeatId] as $forgedParent) {
            $before = $this->qa->run($originalId);
            $data = $this->runData($configuration, $catalog, ['level_1' => '5,5']);
            $data['kind'] = 'correction';
            $data['parent_run_id'] = $forgedParent;
            $data['event_id'] = 999999;
            $data['notes'] = 'Lectura corregida sin cambiar el registro de origen.';
            $data['expected_revision'] = $before['revision'];
            $this->put('/qa-quimica/mediciones/'.$originalId, $data)->assertRedirect();
            $after = $this->qa->run($originalId);
            $this->assertSame('routine', $after['kind']);
            $this->assertNull($after['parent_run_id']);
            $this->assertNull($after['event_id']);
            $this->assertSame($original['configuration_snapshot'], $after['configuration_snapshot']);
            $this->assertSame('5,5', $after['measurements'][0]['raw_value']);
            $this->assertSame('routine', $after['audit'][0]['before']['kind']);
            $this->assertNull($after['audit'][0]['before']['parent_run_id']);
            $this->assertSame($before['measurements'], $after['audit'][0]['measurements']);
        }

        $repeat = $this->qa->run($repeatId);
        $data = $this->runData($configuration, $catalog, ['level_1' => '5,6']);
        $data['kind'] = 'routine';
        $data['parent_run_id'] = $repeatId;
        $data['expected_revision'] = $repeat['revision'];
        $this->put('/qa-quimica/mediciones/'.$repeatId, $data)->assertRedirect();
        $after = $this->qa->run($repeatId);
        $this->assertSame('repeat', $after['kind']);
        $this->assertSame($originalId, $after['parent_run_id']);
        $this->assertSame($repeat['measured_at'], $after['measured_at']);
        $this->assertSame($repeat['measurements'], $after['audit'][0]['measurements']);
    }

    public function test_el_editor_conserva_la_autoria_historica_aunque_el_laboratorista_ya_no_tenga_permiso(): void
    {
        [$configuration, $catalog, $values] = $this->configuration(1);
        $this->post('/qa-quimica/mediciones', $this->runData($configuration, $catalog, $values))->assertRedirect();
        $before = $this->qa->runs()[0];
        $this->operator->revokePermissionTo(Cci::REGISTER);
        $this->operator->unsetRelation('permissions');
        $editor = $this->user([Cci::VIEW, Cci::EDIT], 'Rafael Hernández');
        $this->actingAs($editor);
        $data = $this->runData($configuration, $catalog, ['level_1' => '5,5']);
        $data['operator_user_id'] = $this->operator->id;
        $data['expected_revision'] = $before['revision'];
        $this->put('/qa-quimica/mediciones/'.$before['id'], $data)->assertRedirect();
        $after = $this->qa->run($before['id']);
        $this->assertSame('Daniela Martínez', $after['operator']);
        $this->assertSame($this->operator->id, (int) $after['operator_user_id']);
        $this->assertSame($editor->id, (int) $after['updated_by']);
        $this->assertSame($before['measured_at'], $after['measured_at']);
        $this->assertSame($before['created_by'], $after['created_by']);
    }

    public function test_una_revision_no_aprueba_lecturas_que_otro_usuario_acaba_de_cambiar(): void
    {
        [$configuration, $catalog, $values] = $this->configuration(1);
        $id = $this->qa->recordRun($this->runData($configuration, $catalog, $values));
        $initial = $this->qa->run($id);
        $data = $this->runData($configuration, $catalog, ['level_1' => '5,5']);
        $data['expected_revision'] = $initial['revision'];
        $this->put('/qa-quimica/mediciones/'.$id, $data)->assertRedirect();
        $edited = $this->qa->run($id);
        $review = ['decision' => 'reviewed', 'notes' => 'Lecturas revisadas.', 'reviewer' => 'Persona suplantada', 'expected_revision' => $initial['revision']];
        $this->postJson('/qa-quimica/mediciones/'.$id.'/revision', $review)->assertUnprocessable();
        $this->assertSame([], $this->qa->run($id)['reviews']);

        $review['expected_revision'] = $edited['revision'];
        $this->post('/qa-quimica/mediciones/'.$id.'/revision', $review)->assertRedirect();
        $reviewed = $this->qa->run($id);
        $this->assertSame($edited['revision'] + 1, $reviewed['revision']);
        $this->assertSame('Daniela Martínez', $reviewed['reviews'][0]['reviewer']);
        $data['expected_revision'] = $edited['revision'];
        $this->putJson('/qa-quimica/mediciones/'.$id, $data)->assertUnprocessable();
        $this->assertEquals($reviewed, $this->qa->run($id));
    }

    public function test_actualizar_una_base_anterior_conserva_json_y_lecturas_y_es_idempotente(): void
    {
        [$configuration, $catalog, $values] = $this->configuration(3);
        $id = $this->qa->recordRun($this->runData($configuration, $catalog, $values));
        $db = $this->qa->connection();
        foreach (['qa_configurations' => 'data_json', 'qa_runs' => 'configuration_snapshot'] as $table => $column) {
            $query = $db->table($table)->where('id', $table === 'qa_runs' ? $id : $configuration);
            $legacy = json_decode($query->value($column), true);
            unset($legacy['equipment']['area']);
            foreach ($legacy['levels'] as &$level) {
                unset($level['lot_id']);
            }
            unset($level);
            $query->update([$column => json_encode($legacy, JSON_UNESCAPED_UNICODE)]);
        }
        $db->statement('DROP TABLE qa_run_audits');
        $db->statement('DROP TABLE qa_lots');
        $db->statement('DROP TABLE qa_products');
        $db->statement('ALTER TABLE qa_equipment DROP COLUMN area');
        $db->statement('ALTER TABLE qa_catalog DROP COLUMN is_local');
        foreach (['operator_user_id', 'created_by', 'updated_by', 'deleted_by', 'deleted_at', 'deleted_reason', 'revision'] as $column) {
            $db->statement('ALTER TABLE qa_runs DROP COLUMN '.$column);
        }
        $db->table('qa_meta')->where('key', 'schema_version')->update(['value' => '1']);
        $db->table('qa_meta')->where('key', 'lots_catalog_initialized')->delete();
        $before = (array) $db->table('qa_runs')->where('id', $id)->first();
        $configBefore = $db->table('qa_configurations')->where('id', $configuration)->value('data_json');
        $this->assertFalse($this->qa->ready());

        $this->qa->initialize();
        $this->assertTrue($this->qa->ready());
        $after = (array) $db->table('qa_runs')->where('id', $id)->first();
        foreach ($before as $key => $value) {
            $this->assertSame($value, $after[$key], $key);
        }
        $this->assertSame($configBefore, $db->table('qa_configurations')->where('id', $configuration)->value('data_json'));
        $this->assertCount(1, $this->qa->products());
        $this->assertCount(3, $this->qa->lots());
        $this->qa->initialize();
        $this->assertEquals($after, (array) $db->table('qa_runs')->where('id', $id)->first());
        $this->assertCount(3, $this->qa->lots());
    }

    private function user(array $permissions, string $name): User
    {
        $user = User::factory()->create(['name' => $name, 'nickname' => 'cci.'.Str::lower(Str::random(8))]);
        $user->givePermissionTo(['access_admin_panel', ...$permissions]);

        return $user;
    }

    private function configuration(int $count): array
    {
        $equipment = $this->qa->createEquipment(['area' => 'Hematología', 'code' => 'H-'.Str::upper(Str::random(6)), 'name' => 'Hematología 01', 'model' => 'Mindray BC-20']);
        $product = $this->qa->createProduct(['name' => 'BC-3D Control '.Str::upper(Str::random(5))]);
        $catalog = $this->qa->createTest(['name' => 'LEUCOCITOS CONTROL '.Str::upper(Str::random(5)), 'exam_name' => 'HEMOGRAMA CONTROL', 'unit' => '10^9/L', 'area' => 'Hematología']);
        $levels = $targets = $values = [];
        for ($i = 1; $i <= $count; $i++) {
            $key = 'level_'.$i;
            $lot = $this->qa->createLot(['product_id' => $product, 'name' => 'Nivel '.$i, 'lot' => 'BC2611-'.$i, 'expires_on' => '2027-02-28']);
            $levels[$key] = ['label' => 'Nivel '.$i, 'lot_id' => $lot, 'opened_on' => '2026-11-01', 'stability_days' => 30];
            $targets[$key] = ['target' => $i * 5, 'min' => $i * 5 - 1, 'max' => $i * 5 + 1];
            $values[$key] = str_replace('.', ',', (string) ($i * 5 + .25));
        }
        $configuration = $this->qa->saveConfiguration(['equipment_id' => $equipment, 'label' => 'Control hematológico noviembre', 'configured_by' => 'Daniela Martínez', 'levels' => $levels, 'analytes' => [['catalog_id' => $catalog, 'method' => 'Impedancia', 'unit' => '10^9/L', 'targets' => $targets]]]);

        return [$configuration, $catalog, $values];
    }

    private function runData(int $configuration, int $catalog, array $values): array
    {
        return ['configuration_id' => $configuration, 'measured_at' => '2026-11-10T09:00', 'operator' => 'Daniela Martínez', 'kind' => 'routine', 'values' => [$catalog => $values], 'notes' => '', 'missing_reason' => '', 'action' => 'submit'];
    }
}
