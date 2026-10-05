<?php

namespace Tests\Feature;

use App\Models\Actividad;
use App\Models\Cliente;
use App\Models\DetalleOrden;
use App\Models\Examen;
use App\Models\Orden;
use App\Models\RegistroSoporte;
use App\Models\Resultado;
use App\Models\TipoExamen;
use App\Models\User;
use App\Services\RepararAutoriaResultados;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RepararAutoriaResultadosTest extends TestCase
{
    use RefreshDatabase;

    private User $soporte;

    private Orden $orden;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->soporte = User::factory()->create();
        $this->soporte->assignRole(Role::findOrCreate('super_admin', 'web'));
        $cliente = Cliente::create(['nombre' => 'Paciente', 'apellido' => 'Prueba', 'genero' => 'Femenino']);
        $this->orden = Orden::create(['cliente_id' => $cliente->id, 'fecha' => now(), 'estado' => 'finalizado', 'total' => 10]);
    }

    private function resultado(Orden $orden, User $autor): Resultado
    {
        $this->actingAs($autor);
        $tipo = TipoExamen::firstOrCreate(['nombre' => 'Química']);
        $examen = Examen::create(['nombre' => 'Electrolitos', 'precio' => 10, 'tipo_examen_id' => $tipo->id]);
        $detalle = DetalleOrden::create(['orden_id' => $orden->id, 'examen_id' => $examen->id, 'nombre_examen' => $examen->nombre, 'precio_examen' => 10, 'status' => 'completado']);

        return Resultado::create([
            'detalle_orden_id' => $detalle->id, 'resultado' => '140', 'user_id' => $autor->id,
            'prueba_nombre_snapshot' => 'Sodio', 'valor_referencia_snapshot' => '135 - 145',
            'unidades_snapshot' => 'mmol/L', 'alertar' => false,
        ])->fresh();
    }

    private function sobrescribir(Resultado $resultado, array $cambios = []): void
    {
        $this->actingAs($this->soporte);
        $this->travel(2)->seconds();
        $resultado->update(array_replace(['user_id' => $this->soporte->id], $cambios));
    }

    public function test_recupera_cada_autor_desde_bitacora_privada_sin_cambiar_valores_fechas_u_otras_ordenes(): void
    {
        $autorUno = User::factory()->create(['name' => 'Laboratorista uno']);
        $autorDos = User::factory()->create(['name' => 'Laboratorista dos']);
        $uno = $this->resultado($this->orden, $autorUno);
        $dos = $this->resultado($this->orden, $autorDos);
        $otro = $this->resultado(Orden::create(['cliente_id' => $this->orden->cliente_id, 'fecha' => now(), 'estado' => 'en proceso', 'total' => 10]), $autorUno);
        $intacto = $this->resultado($this->orden, $autorDos);
        foreach ([$uno, $dos, $otro] as $resultado) {
            $this->sobrescribir($resultado);
        }
        $antes = Resultado::orderBy('id')->get()->mapWithKeys(fn ($r) => [$r->id => $r->getRawOriginal()])->all();
        $this->assertSame(3, RegistroSoporte::where('datos->subject_type', Resultado::class)->count());
        $privado = RegistroSoporte::where('datos->subject_id', $uno->id)->where('datos->subject_type', Resultado::class)->sole();
        $this->assertNull(Actividad::findOrFail($privado->activity_id)->subject_id);

        $this->artisan('oncosavi:reparar-autoria-resultados', ['orden' => $this->orden->id, '--desde-usuario' => $this->soporte->id])
            ->expectsOutputToContain('Solo revisión')->assertSuccessful();
        $this->assertSame($antes, Resultado::orderBy('id')->get()->mapWithKeys(fn ($r) => [$r->id => $r->getRawOriginal()])->all());
        Storage::disk('local')->assertDirectoryEmpty('reparaciones');

        $this->artisan('oncosavi:reparar-autoria-resultados', ['orden' => $this->orden->id, '--desde-usuario' => $this->soporte->id, '--ejecutar' => true])
            ->expectsOutputToContain('Autorías restauradas: 2')->assertSuccessful();
        $despues = Resultado::orderBy('id')->get()->mapWithKeys(fn ($r) => [$r->id => $r->getRawOriginal()])->all();
        $esperado = $antes;
        $esperado[$uno->id]['user_id'] = $autorUno->id;
        $esperado[$dos->id]['user_id'] = $autorDos->id;
        $this->assertSame($esperado, $despues);
        $this->assertSame($antes[$otro->id], $otro->fresh()->getRawOriginal());
        $this->assertSame($antes[$intacto->id], $intacto->fresh()->getRawOriginal());

        $archivos = Storage::disk('local')->allFiles('reparaciones');
        $this->assertCount(1, $archivos);
        $respaldo = json_decode(Storage::disk('local')->get($archivos[0]), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame([$antes[$uno->id], $antes[$dos->id]], $respaldo['resultados_antes']);
        $this->assertSame([$autorUno->id, $autorDos->id], array_column($respaldo['cambios_previstos'], 'autor_id'));
        $this->assertSame(0600, fileperms(Storage::disk('local')->path($archivos[0])) & 0777);
        $auditorias = Actividad::where('event', 'autoria_restaurada')->orderBy('id')->get();
        $this->assertCount(2, $auditorias);
        $this->assertSame($privado->activity_id, $auditorias[0]->properties['actividad_evidencia_id']);
        $this->assertSame($this->soporte->id, $auditorias[0]->properties['old']['user_id']);
        $this->assertSame($autorUno->id, $auditorias[0]->properties['attributes']['user_id']);
        $this->assertNull($auditorias[0]->causer_id);
        $this->artisan('oncosavi:reparar-autoria-resultados', ['orden' => $this->orden->id, '--desde-usuario' => $this->soporte->id, '--ejecutar' => true])
            ->expectsOutputToContain('Autorías restauradas: 0')->assertSuccessful();
        $this->assertCount(1, Storage::disk('local')->allFiles('reparaciones'));
        $this->assertSame(2, Actividad::where('event', 'autoria_restaurada')->count());
    }

    public function test_recupera_tambien_cambios_de_autoria_en_bitacora_ordinaria(): void
    {
        $this->soporte->removeRole('super_admin');
        $autor = User::factory()->create();
        $resultado = $this->resultado($this->orden, $autor);
        $this->sobrescribir($resultado);
        $this->assertSame(0, RegistroSoporte::count());
        app(RepararAutoriaResultados::class)->ejecutar($this->orden->id, $this->soporte->id);
        $this->assertSame($autor->id, $resultado->fresh()->user_id);
    }

    public function test_recupera_autor_despues_de_descargas_repetidas_que_solo_cambiaron_fecha(): void
    {
        $autor = User::factory()->create();
        $resultado = $this->resultado($this->orden, $autor);
        $this->sobrescribir($resultado);
        $evidencia = RegistroSoporte::where('datos->subject_type', Resultado::class)->sole()->activity_id;
        $this->sobrescribir($resultado);
        $this->sobrescribir($resultado);
        $antes = $resultado->fresh()->getRawOriginal();
        $plan = app(RepararAutoriaResultados::class)->revisar($this->orden->id, $this->soporte->id);
        $this->assertSame([], $plan['bloqueados']);
        $this->assertSame($evidencia, $plan['cambios'][0]['actividad_id']);
        app(RepararAutoriaResultados::class)->ejecutar($this->orden->id, $this->soporte->id);
        $antes['user_id'] = $autor->id;
        $this->assertSame($antes, $resultado->fresh()->getRawOriginal());
    }

    public function test_registro_de_creacion_ausente_o_de_otro_resultado_bloquea_la_reparacion(): void
    {
        $resultado = $this->resultado($this->orden, User::factory()->create());
        $this->sobrescribir($resultado);
        $creacion = Actividad::where('subject_type', Resultado::class)->where('subject_id', $resultado->id)->where('event', 'created')->sole();
        $propiedades = $creacion->properties->all();
        $propiedades['attributes']['created_at'] = now()->subYear()->toJSON();
        $creacion->update(['properties' => $propiedades]);
        $plan = app(RepararAutoriaResultados::class)->revisar($this->orden->id, $this->soporte->id);
        $this->assertCount(1, $plan['bloqueados']);
        $this->assertSame([], $plan['cambios']);
        $creacion->delete();
        $plan = app(RepararAutoriaResultados::class)->revisar($this->orden->id, $this->soporte->id);
        $this->assertCount(1, $plan['bloqueados']);
        $this->assertSame([], $plan['cambios']);
    }

    public function test_ids_reutilizados_no_recuperan_autores_de_resultados_anteriores_al_reinicio(): void
    {
        $viejo = $this->resultado($this->orden, User::factory()->create());
        $this->sobrescribir($viejo);
        DB::table('resultados')->delete();
        DB::table('sqlite_sequence')->where('name', 'resultados')->delete();
        $this->travel(1)->days();
        $autor = User::factory()->create();
        $nuevo = $this->resultado($this->orden, $autor);
        $this->assertSame($viejo->id, $nuevo->id);
        $this->sobrescribir($nuevo);
        app(RepararAutoriaResultados::class)->ejecutar($this->orden->id, $this->soporte->id);
        $this->assertSame($autor->id, $nuevo->fresh()->user_id);
    }

    public function test_cambio_clinico_impide_reparar_toda_la_orden_incluso_los_resultados_recuperables(): void
    {
        $autor = User::factory()->create();
        $bueno = $this->resultado($this->orden, $autor);
        $clinico = $this->resultado($this->orden, $autor);
        $this->sobrescribir($bueno);
        $this->sobrescribir($clinico, ['resultado' => '160']);
        $antes = Resultado::orderBy('id')->get()->toArray();
        $this->artisan('oncosavi:reparar-autoria-resultados', ['orden' => $this->orden->id, '--desde-usuario' => $this->soporte->id, '--ejecutar' => true])
            ->expectsOutputToContain('Revisión incompleta')->assertFailed();
        $this->assertSame($antes, Resultado::orderBy('id')->get()->toArray());
        $this->assertSame(0, Actividad::where('event', 'autoria_restaurada')->count());
        Storage::disk('local')->assertDirectoryEmpty('reparaciones');
    }

    public function test_sin_bitacora_no_se_adivina_el_autor_por_nombre(): void
    {
        $resultado = $this->resultado($this->orden, User::factory()->create(['name' => 'Roberto Calderon']));
        $this->sobrescribir($resultado);
        Actividad::where('subject_type', Resultado::class)->delete();
        RegistroSoporte::where('datos->subject_type', Resultado::class)->delete();
        $this->artisan('oncosavi:reparar-autoria-resultados', ['orden' => $this->orden->id, '--desde-usuario' => $this->soporte->id, '--ejecutar' => true])
            ->expectsOutputToContain('Revisión incompleta')->assertFailed();
        $this->assertSame($this->soporte->id, $resultado->fresh()->user_id);
    }

    public function test_cambios_posteriores_sin_bitacora_impiden_reasignar_el_resultado(): void
    {
        $resultado = $this->resultado($this->orden, User::factory()->create());
        $this->sobrescribir($resultado);
        Resultado::whereKey($resultado->id)->toBase()->update(['resultado' => '190']);
        $plan = app(RepararAutoriaResultados::class)->revisar($this->orden->id, $this->soporte->id);
        $this->assertCount(1, $plan['bloqueados']);
        $this->assertSame([], $plan['cambios']);
        $this->assertStringContainsString('contenido actual', $plan['bloqueados'][0]['motivo']);
    }

    public function test_un_guardado_clinico_posterior_de_soporte_no_recupera_al_creador_original(): void
    {
        $resultado = $this->resultado($this->orden, User::factory()->create());
        $this->sobrescribir($resultado);
        $this->sobrescribir($resultado, ['resultado' => '145']);
        $plan = app(RepararAutoriaResultados::class)->revisar($this->orden->id, $this->soporte->id);
        $this->assertCount(1, $plan['bloqueados']);
        $this->assertSame([], $plan['cambios']);
    }

    public function test_si_no_se_puede_respaldar_no_se_modifica_ningun_resultado(): void
    {
        $resultado = $this->resultado($this->orden, User::factory()->create());
        $this->sobrescribir($resultado);
        $antes = $resultado->fresh()->getRawOriginal();
        Storage::shouldReceive('disk')->with('local')->once()->andReturn(new class
        {
            public function put($ruta, $datos): bool
            {
                return false;
            }
        });
        $this->artisan('oncosavi:reparar-autoria-resultados', ['orden' => $this->orden->id, '--desde-usuario' => $this->soporte->id, '--ejecutar' => true])
            ->expectsOutputToContain('No se pudo guardar el respaldo')->assertFailed();
        $this->assertSame($antes, $resultado->fresh()->getRawOriginal());
        $this->assertSame(0, Actividad::where('event', 'autoria_restaurada')->count());
    }

    public function test_fallo_en_bitacora_revierte_todas_las_autorias_con_respaldo_conservado(): void
    {
        $autor = User::factory()->create();
        foreach (range(1, 2) as $numero) {
            $this->sobrescribir($this->resultado($this->orden, $autor));
        }
        $antes = Resultado::orderBy('id')->get()->toArray();
        $cantidad = 0;
        Event::listen('eloquent.creating: '.Actividad::class, function ($actividad) use (&$cantidad): void {
            if ($actividad->event === 'autoria_restaurada' && ++$cantidad === 2) {
                throw new \RuntimeException('Fallo simulado en bitácora.');
            }
        });
        try {
            $this->artisan('oncosavi:reparar-autoria-resultados', ['orden' => $this->orden->id, '--desde-usuario' => $this->soporte->id, '--ejecutar' => true])
                ->expectsOutputToContain('Fallo simulado en bitácora')->assertFailed();
        } finally {
            Event::forget('eloquent.creating: '.Actividad::class);
        }
        $this->assertSame($antes, Resultado::orderBy('id')->get()->toArray());
        $this->assertSame(0, Actividad::where('event', 'autoria_restaurada')->count());
        $this->assertCount(1, Storage::disk('local')->allFiles('reparaciones'));
    }
}
