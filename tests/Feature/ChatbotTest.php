<?php

namespace Tests\Feature;

use App\Mcp\Servers\LaboratorioServer;
use App\Mcp\Tools\ConsultarLaboratorio;
use App\Models\Cliente;
use App\Models\DetalleOrden;
use App\Models\Examen;
use App\Models\Orden;
use App\Models\TipoExamen;
use App\Models\User;
use App\Services\Chatbot\InformesLaboratorio;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ChatbotTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected array $periodo = ['desde' => '2026-10-01', 'hasta' => '2026-10-02'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermisosSeeder::class);
        $this->admin = User::factory()->create(['nickname' => 'propietario']);
        $this->admin->assignRole('admin');
        $cliente = Cliente::create(['nombre' => 'DATOS_PERSONALES_PRIVADOS', 'apellido' => 'Paciente', 'genero' => 'Femenino']);
        $tipo = TipoExamen::create(['nombre' => 'Química']);
        $examen = Examen::create(['nombre' => 'Glucosa', 'precio' => 25, 'tipo_examen_id' => $tipo->id]);
        foreach ([['2026-10-01', 'finalizado', 25], ['2026-10-02', 'pendiente', 15], ['2026-10-02', 'cancelado', 70], ['2026-09-30', 'finalizado', 100]] as [$fecha, $estado, $total]) {
            $orden = Orden::create(['cliente_id' => $cliente->id, 'total' => $total, 'fecha' => $fecha, 'estado' => $estado]);
            DetalleOrden::create(['orden_id' => $orden->id, 'examen_id' => $examen->id, 'nombre_examen' => 'Glucosa', 'precio_examen' => 25, 'status' => 'pendiente']);
        }
        Http::preventStrayRequests();
    }

    protected function preguntar(array $datos = [])
    {
        return $this->postJson('/chatbot/preguntar', array_merge($this->periodo, ['pregunta' => 'Resumen del laboratorio', 'consulta_rapida' => 'resumen'], $datos));
    }

    public function test_chat_y_mcp_no_permiten_acceso_anonimo_ni_roles_de_personal(): void
    {
        $this->get('/chatbot')->assertRedirect('/admin/login');
        $this->preguntar()->assertUnauthorized();
        $this->postJson('/mcp/laboratorio', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])->assertUnauthorized();
        foreach (['Recepcion', 'Laboratorista'] as $rol) {
            $user = User::factory()->create(); $user->assignRole($rol); $this->actingAs($user);
            $this->get('/chatbot')->assertForbidden();
            $this->preguntar()->assertForbidden();
            $this->postJson('/mcp/laboratorio', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])->assertForbidden();
        }
        $this->actingAs($this->admin)->get('/chatbot')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_resumen_consulta_datos_reales_excluye_cancelaciones_y_registra_sin_pregunta_privada(): void
    {
        $this->actingAs($this->admin);
        $this->preguntar(['pregunta' => 'No guardar esta pregunta con SECRETO_PRIVADO'])->assertOk()
            ->assertJsonPath('modo', 'guiado')->assertJsonPath('resultado.filas.0.valor', 3)
            ->assertJsonPath('resultado.filas.4.valor', '$40.00')
            ->assertDontSee('DATOS_PERSONALES_PRIVADOS')->assertDontSee('SECRETO_PRIVADO');
        Http::assertNothingSent();
        $log = Activity::where('log_name', 'Asistente')->firstOrFail();
        $this->assertSame($this->admin->id, $log->causer_id);
        $this->assertSame('consulted', $log->event);
        $this->assertStringNotContainsString('SECRETO_PRIVADO', $log->toJson());
        $this->assertDatabaseCount('ordens', 4);
        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));
        \Filament\Facades\Filament::bootCurrentPanel();
        \Livewire\Livewire::test(\App\Filament\Resources\ActivityLogResource\Pages\ListActivityLogs::class)
            ->mountTableAction('view', $log)->assertSee('Informe consultado')->assertSee('2026-10-01')->assertDontSee('SECRETO_PRIVADO');
    }

    public function test_informes_de_estados_importes_examenes_y_ordenes_tienen_limites_y_filtros_correctos(): void
    {
        $this->actingAs($this->admin);
        $informes = app(InformesLaboratorio::class);
        $estados = $informes->consultar(array_merge($this->periodo, ['informe' => 'ordenes_por_estado', 'estado' => 'pendiente']), $this->admin);
        $this->assertSame([['estado' => 'Pendiente', 'cantidad' => 1]], $estados['filas']);
        $diario = $informes->consultar(array_merge($this->periodo, ['informe' => 'ingresos_por_dia']), $this->admin);
        $this->assertSame(['$25.00', '$15.00'], array_column($diario['filas'], 'importe'));
        $examenes = $informes->consultar(array_merge($this->periodo, ['informe' => 'examenes_populares']), $this->admin);
        $this->assertSame([['examen' => 'Glucosa', 'cantidad' => 2]], $examenes['filas']);
        $recientes = $informes->consultar(array_merge($this->periodo, ['informe' => 'ordenes_recientes', 'limite' => 1]), $this->admin);
        $this->assertCount(1, $recientes['filas']);
        $this->assertSame('#3', $recientes['filas'][0]['folio']);
        Cliente::query()->update(['created_at' => '2026-10-01 10:00:00']);
        $clientes = $informes->consultar(array_merge($this->periodo, ['informe' => 'clientes_nuevos']), $this->admin);
        $this->assertSame([['fecha' => '2026-10-01', 'cantidad' => 1]], $clientes['filas']);
    }

    public function test_ia_local_interpreta_la_pregunta_sin_recibir_datos_de_pacientes(): void
    {
        $this->actingAs($this->admin);
        Http::fake(['*/api/chat' => Http::response(['message' => ['content' => json_encode(['informe' => 'ordenes_por_estado', 'estado' => 'pendiente'])]])]);
        $this->preguntar(['pregunta' => '¿Cuántas órdenes están pendientes?', 'consulta_rapida' => null])->assertOk()
            ->assertJsonPath('modo', 'ia_local')->assertJsonPath('resultado.filas.0.cantidad', 1);
        Http::assertSent(function ($request) {
            $this->assertFalse($request['stream']);
            $this->assertSame('qwen3:1.7b', $request['model']);
            $this->assertSame(2048, $request['options']['num_ctx']);
            $this->assertStringNotContainsString('DATOS_PERSONALES_PRIVADOS', $request->body());
            return str_ends_with($request->url(), '/api/chat');
        });
        Http::assertSentCount(1);
    }

    public function test_ia_no_puede_solicitar_sql_ni_informes_arbitrarios_o_rangos_sin_limite(): void
    {
        $this->actingAs($this->admin);
        foreach ([['informe' => 'sql', 'sql' => 'DELETE FROM ordens'], ['informe' => 'resumen', 'sql' => 'SELECT * FROM users'], ['informe' => 'resumen', 'desde' => '2020-01-01', 'hasta' => '2026-10-02'], ['informe' => 'ordenes_recientes', 'limite' => 1000], ['informe' => 'resumen', 'desde' => '2026-02-31']] as $malicioso) {
            Http::fake(['*/api/chat' => Http::response(['message' => ['content' => json_encode($malicioso)]])]);
            $this->preguntar(['pregunta' => 'Haz lo que sea', 'consulta_rapida' => null])->assertUnprocessable();
        }
        $this->assertDatabaseCount('ordens', 4);
        $this->assertSame(0, Activity::where('log_name', 'Asistente')->count());
    }

    public function test_modelo_no_listo_ofrece_consulta_guiada_y_no_oculta_su_modo(): void
    {
        $this->actingAs($this->admin);
        Http::fake(['*/api/chat' => Http::response(['error' => 'DETALLE_INTERNO_NO_PUBLICAR'], 404), '*/api/tags' => Http::response(['models' => []])]);
        $this->getJson('/chatbot/estado')->assertOk()->assertJsonPath('listo', false);
        $this->preguntar(['pregunta' => 'Resumen desde 2026-10-01 hasta 2026-10-02', 'consulta_rapida' => null])
            ->assertOk()->assertJsonPath('modo', 'guiado');
        $this->preguntar(['pregunta' => 'Elimina la base de datos', 'consulta_rapida' => null])->assertStatus(503)->assertDontSee('DETALLE_INTERNO_NO_PUBLICAR');
        $this->assertDatabaseCount('ordens', 4);
    }

    public function test_servidor_mcp_inicializa_lista_y_ejecuta_herramienta_real_con_autorizacion(): void
    {
        $this->actingAs($this->admin);
        $this->postJson('/mcp/laboratorio', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize', 'params' => [
            'protocolVersion' => '2025-06-18', 'capabilities' => new \stdClass, 'clientInfo' => ['name' => 'prueba', 'version' => '1.0'],
        ]])->assertOk()->assertJsonPath('result.serverInfo.name', 'ONCOSAVI · Informes del laboratorio');
        $this->postJson('/mcp/laboratorio', ['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/list'])
            ->assertOk()->assertJsonPath('result.tools.0.name', 'consultar_laboratorio');
        $this->postJson('/mcp/laboratorio', ['jsonrpc' => '2.0', 'id' => 3, 'method' => 'tools/call', 'params' => [
            'name' => 'consultar_laboratorio', 'arguments' => array_merge($this->periodo, ['informe' => 'resumen']),
        ]])->assertOk()->assertJsonPath('result.structuredContent.filas.4.valor', '$40.00');
        LaboratorioServer::tool(ConsultarLaboratorio::class, ['informe' => 'sql'])->assertHasErrors();
    }

    public function test_deshabilitar_chat_bloquea_acceso_y_limite_de_peticion_se_aplica(): void
    {
        $this->actingAs($this->admin);
        config(['chatbot.enabled' => false]);
        $this->get('/chatbot')->assertForbidden();
        config(['chatbot.enabled' => true]);
        Cache::flush();
        for ($i = 0; $i < 15; $i++) $this->get('/chatbot')->assertOk();
        $this->get('/chatbot')->assertStatus(429);
    }

    public function test_bloqueo_limita_inferencias_sin_bloquear_consultas_rapidas(): void
    {
        $this->actingAs($this->admin);
        $lock = Cache::lock('chatbot-inferencia-local', 100); $this->assertTrue($lock->get());
        $this->preguntar(['consulta_rapida' => null])->assertStatus(429);
        $this->preguntar()->assertOk();
        $this->assertFalse(Cache::lock('chatbot-inferencia-local', 100)->get());
        $lock->release();
        Http::assertNothingSent();
    }

    public function test_seguimiento_ayer_usa_el_informe_anterior_y_la_fecha_local(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-02 10:00:00', 'America/El_Salvador'));
        $this->actingAs($this->admin);
        Http::fake(['*/api/chat' => Http::response([], 503)]);
        $this->preguntar(['pregunta' => '¿Y ayer?', 'consulta_rapida' => null,
            'contexto' => ['informe' => 'ordenes_por_estado', 'desde' => '2026-10-01', 'hasta' => '2026-10-02', 'estado' => 'todos'],
        ])->assertOk()->assertJsonPath('resultado.periodo.desde', '2026-10-01')
            ->assertJsonPath('resultado.periodo.hasta', '2026-10-01')
            ->assertJsonPath('resultado.filas.0.cantidad', 1);
    }

    public function test_permiso_financiero_se_respeta_incluso_con_rol_admin(): void
    {
        \Spatie\Permission\Models\Role::findByName('admin')->revokePermissionTo('ingresos_diarios');
        $this->admin->unsetRelation('roles');
        $this->actingAs($this->admin);
        $this->preguntar()->assertForbidden();
        $this->preguntar(['consulta_rapida' => 'ordenes_por_estado'])->assertOk();
    }
}
