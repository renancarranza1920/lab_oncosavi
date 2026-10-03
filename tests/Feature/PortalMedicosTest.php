<?php

namespace Tests\Feature;

use App\Filament\Resources\MedicoResource\Pages\ListMedicos;
use App\Models\Cliente;
use App\Models\Medico;
use App\Models\Orden;
use App\Models\User;
use App\Services\AccesoMedicoService;
use Database\Seeders\RolesPermisosSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class PortalMedicosTest extends TestCase
{
    use RefreshDatabase;

    private function medico(bool $todos = false): Medico
    {
        $medico = Medico::create(['nombre' => 'Dra. Prueba']);
        $medico->forceFill(['portal_activo' => true, 'portal_todos_pacientes' => $todos, 'password' => 'ClavePrueba2026'])->save();

        return $medico;
    }

    private function entrar(Medico $medico): void
    {
        $this->post('/expediente/ingresar', ['usuario' => $medico->usuario_portal, 'password' => 'ClavePrueba2026'])
            ->assertRedirect('/expediente');
    }

    private function paciente(string $nombre = 'Ana', array $datos = []): Cliente
    {
        return Cliente::create($datos + ['nombre' => $nombre, 'apellido' => 'Paciente', 'genero' => 'Femenino']);
    }

    private function orden(Medico $medico, Cliente $paciente, array $datos = []): Orden
    {
        return Orden::create($datos + ['cliente_id' => $paciente->id, 'medico_id' => $medico->id, 'fecha' => '2026-10-02', 'total' => 25, 'estado' => 'finalizado']);
    }

    public function test_el_acceso_requiere_credenciales_de_un_medico_habilitado_y_no_da_acceso_admin(): void
    {
        $medico = $this->medico(true);
        $this->get('/expediente')->assertRedirect('/expediente/ingresar');
        $this->get('/expediente/pacientes/1')->assertRedirect('/expediente/ingresar');
        $this->get('/expediente/ordenes/1/pdf')->assertRedirect('/expediente/ingresar');
        $this->get('/expediente/ingresar')->assertOk()->assertSee('Consulta de expedientes');
        $this->post('/expediente/ingresar', ['usuario' => $medico->usuario_portal, 'password' => 'incorrecta'])->assertSessionHasErrors('usuario');
        $this->entrar($medico);
        $this->get('/expediente')->assertOk();
        $this->assertAuthenticatedAs($medico, 'medico');
        $this->assertGuest('web');
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->post('/expediente/salir')->assertRedirect('/expediente/ingresar');
        $this->get('/expediente')->assertRedirect('/expediente/ingresar');
    }

    public function test_usuarios_del_admin_no_sustituyen_el_login_medico(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get('/expediente')->assertRedirect('/expediente/ingresar');
        $this->assertAuthenticated('web');
    }

    public function test_medicos_deshabilitados_y_cambios_de_password_revocan_sesiones(): void
    {
        $medico = $this->medico();
        $this->entrar($medico);
        $medico->portal_activo = false;
        $medico->save();
        $this->get('/expediente')->assertRedirect('/expediente/ingresar');
        $this->post('/expediente/ingresar', ['usuario' => $medico->usuario_portal, 'password' => 'ClavePrueba2026'])->assertSessionHasErrors('usuario');
        $medico->portal_activo = true;
        $medico->save();
        $this->entrar($medico);
        $medico->password = 'OtraClavePrueba2026';
        $medico->save();
        $this->get('/expediente')->assertRedirect('/expediente/ingresar');
    }

    public function test_cambiar_el_alcance_revoca_el_acceso_anterior(): void
    {
        $medico = $this->medico(true);
        $this->entrar($medico);
        $medico->portal_todos_pacientes = false;
        $medico->save();
        $this->get('/expediente')->assertRedirect('/expediente/ingresar');
    }

    public function test_el_alcance_propio_se_aplica_a_busqueda_historial_y_pdf_sin_filtrar_datos_ajenos(): void
    {
        Storage::fake('public');
        $medico = $this->medico();
        $otro = $this->medico();
        $propio = $this->paciente('PacientePropio');
        $ajeno = $this->paciente('PacienteAjeno');
        $ordenPropia = $this->orden($medico, $propio);
        $ordenAjena = $this->orden($otro, $ajeno);
        $compartida = $this->orden($otro, $propio);
        Storage::disk('public')->put($ordenAjena->reporteGuardadoPath(), '%PDF-1.7 privado');
        Storage::disk('public')->put($compartida->reporteGuardadoPath(), '%PDF-1.7 privado');
        $this->entrar($medico);
        $this->get('/expediente')->assertOk()->assertSee('PacientePropio')->assertDontSee('PacienteAjeno');
        $this->get('/expediente?q=%23'.$ordenAjena->id)->assertOk()->assertViewHas('pacientes', fn ($p) => $p->total() === 0);
        $this->get('/expediente/pacientes/'.$ajeno->id)->assertNotFound();
        $this->get('/expediente/pacientes/'.$propio->id)->assertOk()
            ->assertViewHas('ordenes', fn ($orders) => $orders->pluck('id')->all() === [$ordenPropia->id]);
        $this->get('/expediente/ordenes/'.$ordenAjena->id.'/pdf')->assertNotFound();
        $this->get('/expediente/ordenes/'.$compartida->id.'/pdf')->assertNotFound();
    }

    public function test_expediente_general_busca_por_nombre_dui_telefono_correo_direccion_y_orden(): void
    {
        $medico = $this->medico(true);
        $otro = $this->medico();
        $paciente = $this->paciente('PacienteBuscar', ['apellido' => 'ApellidoBusqueda', 'dui' => '12345678-9', 'telefono' => '12025550123', 'correo' => 'prueba.busqueda@example.test', 'direccion' => 'DireccionBusqueda', 'fecha_nacimiento' => '1990-04-15']);
        $orden = $this->orden($otro, $paciente);
        $this->paciente('Otro');
        $this->entrar($medico);
        foreach (['PacienteBuscar ApellidoBusqueda', $paciente->NumeroExp, '123456789', '+1(202)555-0123', 'prueba.busqueda@example.test', 'DireccionBusqueda', '#'.$orden->id] as $termino) {
            $this->get('/expediente?'.http_build_query(['q' => $termino]))->assertOk()
                ->assertViewHas('pacientes', fn ($p) => $p->pluck('id')->all() === [$paciente->id]);
        }
        $this->get('/expediente?fecha_nacimiento=1990-04-15&genero=Femenino&estado=Activo')->assertOk()
            ->assertViewHas('pacientes', fn ($p) => $p->pluck('id')->all() === [$paciente->id]);
        $this->get('/expediente/pacientes/'.$paciente->id)->assertOk()->assertSee('Orden #'.$orden->id);
    }

    public function test_filtros_de_dias_y_horas_corresponden_a_la_misma_orden_y_al_alcance_autorizado(): void
    {
        $medico = $this->medico();
        $otro = $this->medico();
        $esperado = $this->paciente('DiaHora');
        $horaDistinta = $this->paciente('HoraDistinta');
        $ajeno = $this->paciente('MedicoDistinto');
        foreach ([[$medico, $esperado, '2026-10-02', '09:15:00'], [$medico, $horaDistinta, '2026-10-02', '15:30:00'], [$medico, $horaDistinta, '2026-10-01', '09:15:00'], [$otro, $ajeno, '2026-10-02', '09:15:00']] as [$doc, $paciente, $fecha, $hora]) {
            $orden = $this->orden($doc, $paciente, ['fecha' => $fecha]);
            $orden->forceFill(['created_at' => "$fecha $hora"])->save();
        }
        $this->entrar($medico);
        $this->get('/expediente?desde=2026-10-02&hasta=2026-10-02&hora_desde=09:00&hora_hasta=10:00')->assertOk()
            ->assertViewHas('pacientes', fn ($p) => $p->pluck('id')->all() === [$esperado->id]);
        $this->get('/expediente?hasta=2026-10-01')->assertOk()
            ->assertViewHas('pacientes', fn ($p) => $p->pluck('id')->all() === [$horaDistinta->id]);
        $this->from('/expediente')->get('/expediente?desde=2026-10-03&hasta=2026-10-01')->assertSessionHasErrors('hasta');
        $this->from('/expediente')->get('/expediente?hora_desde=15:00&hora_hasta=09:00')->assertSessionHasErrors('hora_hasta');
    }

    public function test_pdf_es_el_mismo_archivo_final_con_firmas_y_nombre_sin_regenerarlo(): void
    {
        Storage::fake('public');
        $medico = $this->medico(true);
        $paciente = $this->paciente();
        $orden = $this->orden($medico, $paciente);
        $bytes = "%PDF-1.7\nContenido original con firma y sello ya guardados\n%%EOF";
        Storage::disk('public')->put($orden->reporteGuardadoPath(), $bytes);
        $this->entrar($medico);
        $response = $this->get('/expediente/ordenes/'.$orden->id.'/pdf')->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertSame($bytes, file_get_contents($response->baseResponse->getFile()->getPathname()));
        $this->assertStringContainsString('inline', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString($orden->reporteGuardadoFileName(), $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->get('/expediente/ordenes/'.$orden->id.'/pdf?descargar=1')->assertOk()
            ->assertDownload($orden->reporteGuardadoFileName());
        $this->get('/expediente/pacientes/'.$paciente->id)->assertOk()->assertSee('Ver resultados');
    }

    public function test_no_publica_pdfs_parciales_ni_finales_inexistentes_y_es_solo_lectura(): void
    {
        Storage::fake('public');
        $medico = $this->medico(true);
        $paciente = $this->paciente();
        $pendiente = $this->orden($medico, $paciente, ['estado' => 'en proceso']);
        $sinPdf = $this->orden($medico, $paciente);
        Storage::disk('public')->put($pendiente->reporteGuardadoPath(), '%PDF-1.7 sin finalizar');
        $this->entrar($medico);
        $this->get('/expediente/ordenes/'.$pendiente->id.'/pdf')->assertNotFound();
        $this->get('/expediente/ordenes/'.$sinPdf->id.'/pdf')->assertNotFound();
        $this->get('/expediente/pacientes/'.$paciente->id)->assertOk()->assertDontSee('Ver resultados');
        $this->post('/expediente/pacientes/'.$paciente->id, ['nombre' => 'Cambiar'])->assertStatus(405);
        $this->patch('/expediente/pacientes/'.$paciente->id, ['nombre' => 'Cambiar'])->assertStatus(405);
        $this->assertSame('Ana', $paciente->fresh()->nombre);
        $this->get('/expediente')->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
    }

    public function test_el_login_limita_intentos_sin_mostrar_errores_de_programacion(): void
    {
        $medico = $this->medico();
        for ($i = 0; $i < 5; $i++) {
            $this->post('/expediente/ingresar', ['usuario' => $medico->usuario_portal, 'password' => 'incorrecta'])->assertSessionHasErrors('usuario');
        }
        $this->post('/expediente/ingresar', ['usuario' => $medico->usuario_portal, 'password' => 'ClavePrueba2026'])
            ->assertSessionHasErrors(['usuario' => 'Demasiados intentos. Intenta nuevamente en un minuto.']);
        $this->assertGuest('medico');
    }

    private function administrador(): User
    {
        $this->seed(RolesPermisosSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        return $admin;
    }

    public function test_admin_configura_acceso_y_password_compartido_en_medicos_sin_filtrarlo_a_la_bitacora(): void
    {
        $this->administrador();
        $a = Medico::create(['nombre' => 'Dra. Uno']);
        $b = Medico::create(['nombre' => 'Dr. Dos']);
        Livewire::test(ListMedicos::class)->callTableBulkAction('habilitarPortal', [$a, $b], [
            'activo' => true, 'todos_pacientes' => true, 'password' => 'ClaveCompartida2026', 'password_confirmation' => 'ClaveCompartida2026',
        ])->assertHasNoTableBulkActionErrors();
        foreach ([$a, $b] as $medico) {
            $this->assertTrue($medico->fresh()->portal_activo);
            $this->assertTrue($medico->fresh()->portal_todos_pacientes);
            $this->assertTrue(Hash::check('ClaveCompartida2026', $medico->fresh()->password));
            $this->assertArrayNotHasKey('password', $medico->fresh()->toArray());
        }
        $logs = Activity::where('log_name', 'Acceso de médicos')->get();
        $this->assertCount(2, $logs);
        $this->assertStringNotContainsString('ClaveCompartida2026', $logs->toJson());
        Livewire::test(ListMedicos::class)->callTableAction('configurarPortal', $a, ['activo' => false, 'todos_pacientes' => false, 'password' => '', 'password_confirmation' => ''])
            ->assertHasNoTableActionErrors();
        $this->assertFalse($a->fresh()->portal_activo);
    }

    public function test_recepcion_no_puede_habilitar_acceso_al_portal(): void
    {
        $this->administrador();
        $recepcion = User::factory()->create();
        $recepcion->assignRole('Recepcion');
        $this->actingAs($recepcion);
        $medico = Medico::create(['nombre' => 'Sin acceso']);
        Livewire::test(ListMedicos::class)->assertTableActionHidden('configurarPortal', $medico)->assertTableBulkActionHidden('habilitarPortal');
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        app(AccesoMedicoService::class)->configurar($medico, true, true, 'ClaveCompartida2026');
    }

    public function test_la_migracion_es_reversible_sin_perder_medicos_pacientes_u_ordenes(): void
    {
        $medico = $this->medico();
        $paciente = $this->paciente();
        $orden = $this->orden($medico, $paciente);
        $migracion = require database_path('migrations/2026_10_03_000001_add_portal_access_to_medicos.php');
        $migracion->down();
        $this->assertFalse(Schema::hasColumn('medicos', 'password'));
        $this->assertDatabaseHas('medicos', ['id' => $medico->id, 'nombre' => $medico->nombre]);
        $this->assertDatabaseHas('clientes', ['id' => $paciente->id]);
        $this->assertDatabaseHas('ordens', ['id' => $orden->id, 'medico_id' => $medico->id, 'cliente_id' => $paciente->id]);
        $migracion->up();
        $this->assertTrue(Schema::hasColumn('medicos', 'password'));
        $this->assertFalse($medico->fresh()->portal_activo);
    }
}
