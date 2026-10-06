<?php

namespace Tests\Feature;

use App\Filament\Resources\ClientesResource\Pages\CreateClientes;
use App\Filament\Resources\ClientesResource\Pages\EditClientes;
use App\Filament\Resources\MedicoResource;
use App\Filament\Resources\MedicoResource\Pages\CreateMedico;
use App\Filament\Resources\MedicoResource\Pages\ListMedicos;
use App\Filament\Resources\OrdenResource;
use App\Filament\Resources\OrdenResource\Pages\CreateOrden;
use App\Filament\Resources\OrdenResource\Pages\IngresarResultados;
use App\Filament\Resources\OrdenResource\Pages\ListOrdens;
use App\Models\Cliente;
use App\Models\DetalleOrden;
use App\Models\Examen;
use App\Models\Medico;
use App\Models\Muestra;
use App\Models\Orden;
use App\Models\TipoExamen;
use App\Models\User;
use App\Services\AccesoMedicoService;
use Database\Seeders\RolesPermisosSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class MejorasOperacionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermisosSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
    }

    private function orden(): array
    {
        $cliente = Cliente::create(['nombre' => 'Paciente', 'apellido' => 'Prueba', 'genero' => 'Femenino']);
        $tipo = TipoExamen::create(['nombre' => 'Química']);
        $examen = Examen::create(['tipo_examen_id' => $tipo->id, 'nombre' => 'Glucosa', 'precio' => 10]);
        $muestra = Muestra::create(['nombre' => 'Sangre']);
        $examen->muestras()->attach($muestra);
        $orden = Orden::create(['cliente_id' => $cliente->id, 'fecha' => now(), 'total' => 10, 'estado' => 'pendiente']);
        $detalle = DetalleOrden::create(['orden_id' => $orden->id, 'examen_id' => $examen->id, 'nombre_examen' => 'Glucosa', 'precio_examen' => 10, 'status' => 'pendiente']);

        return [$orden, $detalle, $muestra, $examen];
    }

    public function test_crear_medico_regresa_al_listado_y_permite_crear_otro(): void
    {
        $pagina = app(CreateMedico::class);
        $metodo = new \ReflectionMethod($pagina, 'getRedirectUrl');

        $this->assertSame(MedicoResource::getUrl('index'), $metodo->invoke($pagina));
        $this->assertTrue(CreateMedico::canCreateAnother());
    }

    public function test_la_url_filtrada_incluye_pestana_busqueda_e_id_exacto(): void
    {
        [$orden] = $this->orden();

        parse_str(parse_url(OrdenResource::getUrlOrdenFiltrada($orden, 'pendiente'), PHP_URL_QUERY), $query);

        $this->assertSame('pendiente', $query['activeTab']);
        $this->assertSame((string) $orden->id, $query['ordenId']);
        $this->assertSame((string) $orden->id, $query['tableSearch']);
    }

    public function test_recepcion_y_finalizacion_abren_la_pestana_filtrada_por_orden(): void
    {
        [$orden, $detalle, $muestra] = $this->orden();
        Livewire::test(ListOrdens::class)->set('activeTab', 'todas')
            ->callTableAction('gestionarMuestras', $orden, ['muestras_recibidas_list' => ['d'.$detalle->id.'_m'.$muestra->id]])
            ->assertRedirect(OrdenResource::getUrlOrdenFiltrada($orden->fresh(), 'en proceso'));
        $this->assertSame('en proceso', $orden->fresh()->estado);

        Livewire::test(ListOrdens::class)->set('activeTab', 'en proceso')
            ->callTableAction('finalizarOrden', $orden->fresh())
            ->assertRedirect(OrdenResource::getUrlOrdenFiltrada($orden->fresh(), 'finalizado'));
        $this->assertSame('finalizado', $orden->fresh()->estado);
    }

    public function test_crear_orden_abre_pendientes_filtrando_la_nueva_orden(): void
    {
        [$orden, , , $examen] = $this->orden();
        config(['laboratorio.impresion_etiquetas_habilitada' => false]);
        $siguienteId = ((int) Orden::max('id')) + 1;
        Livewire::test(CreateOrden::class)->fillForm([
            'cliente_id' => $orden->cliente_id,
            'examenes_seleccionados' => [['examen_id' => $examen->id, 'nombre_examen' => 'Glucosa', 'precio_hidden' => 10, 'recipiente' => 'pendiente']],
        ])->assertHasNoFormErrors()->call('create')->assertRedirect(OrdenResource::getUrl('index', [
            'activeTab' => 'pendiente',
            'ordenId' => $siguienteId,
            'tableSearch' => (string) $siguienteId,
        ]));
        $this->assertSame(2, Orden::count());
    }

    public function test_completar_desde_resultados_abre_finalizadas_filtrando_la_orden(): void
    {
        [$orden] = $this->orden();
        $orden->update(['estado' => 'en proceso']);
        Livewire::test(IngresarResultados::class, ['record' => $orden])
            ->callAction('completar')
            ->assertRedirect(OrdenResource::getUrlOrdenFiltrada($orden->fresh(), 'finalizado'));
        $this->assertSame('finalizado', $orden->fresh()->estado);
    }

    public function test_guarda_varios_paises_y_busca_por_un_numero_secundario(): void
    {
        Livewire::test(CreateClientes::class)->fillForm([
            'nombre' => 'Contacto', 'apellido' => 'Múltiple', 'genero' => 'Femenino',
            'telefonos' => [
                ['formato' => 'sv', 'numero' => '7777-8888'],
                ['formato' => 'us', 'numero' => '(202) 555-0123'],
                ['formato' => 'internacional', 'numero' => '(502)1234-5678'],
            ],
        ])->call('create')->assertHasNoFormErrors();
        $cliente = Cliente::where('nombre', 'Contacto')->firstOrFail();
        $this->assertSame('50377778888', $cliente->telefono);
        $this->assertSame(['50377778888', '12025550123', '50212345678'], $cliente->telefonos_contacto);
        $this->assertSame('movil', $cliente->telefonos[1]->tipo);
        $this->assertSame($cliente->id, Cliente::buscarTelefono('+1 (202) 555-0123')->sole()->id);
        Livewire::test(\App\Filament\Resources\ClientesResource\Pages\ListClientes::class)
            ->searchTable('50212345678')->assertCanSeeTableRecords([$cliente]);
        $general = Medico::where('portal_usuario', 'medicos')->firstOrFail();
        $this->assertSame($cliente->id, app(\App\Services\ExpedienteMedicoService::class)->buscar($general, ['q' => '+50212345678'])->sole()->id);

        $pagina = Livewire::test(EditClientes::class, ['record' => $cliente->getRouteKey()]);
        $filas = $pagina->get('data.telefonos');
        $ultima = array_key_last($filas);
        $pagina->assertSet('data.telefonos.'.$ultima.'.formato', 'internacional')
            ->assertSet('data.telefonos.'.$ultima.'.numero', '50212345678')
            ->call('save')->assertHasNoFormErrors();
        $this->assertSame(['50377778888', '12025550123', '50212345678'], $cliente->fresh()->telefonos_contacto);
    }

    public function test_quitar_un_telefono_conserva_los_demas_y_actualiza_el_principal(): void
    {
        $cliente = Cliente::create(['nombre' => 'Paciente', 'apellido' => 'Contacto', 'genero' => 'Femenino']);
        $primero = $cliente->telefonos()->create(['numero' => '50377778888', 'codigo_pais' => '503', 'tipo' => 'movil', 'orden' => 0]);
        $segundo = $cliente->telefonos()->create(['numero' => '12025550123', 'codigo_pais' => '1', 'tipo' => 'fijo', 'orden' => 1]);
        $pagina = Livewire::test(EditClientes::class, ['record' => $cliente->getRouteKey()]);
        $filas = $pagina->get('data.telefonos');
        array_shift($filas);
        $pagina->set('data.telefonos', $filas)->call('save')->assertHasNoFormErrors();
        $this->assertDatabaseMissing('cliente_telefonos', ['id' => $primero->id]);
        $this->assertDatabaseHas('cliente_telefonos', ['id' => $segundo->id, 'numero' => '12025550123']);
        $this->assertSame('12025550123', $cliente->fresh()->telefono);
    }

    public function test_acceso_medico_general_es_repetible_con_password_hash_y_sin_aparecer_como_medico_clinico(): void
    {
        Storage::fake('local');
        $this->artisan('oncosavi:portal-medicos-general')->assertSuccessful();
        $general = Medico::where('portal_usuario', 'medicos')->sole();
        $credenciales = json_decode(Storage::disk('local')->get('portal-medicos-general.json'), true);
        $this->assertSame('medicos', $credenciales['usuario']);
        $this->assertTrue(Hash::check($credenciales['password'], $general->password));
        $hash = $general->password;
        $this->artisan('oncosavi:portal-medicos-general')->assertSuccessful();
        $this->assertSame($hash, $general->fresh()->password);
        $this->assertNull(MedicoResource::getEloquentQuery()->find($general->id));
        Livewire::test(ListMedicos::class)->assertCanNotSeeTableRecords([$general]);
    }

    public function test_general_consulta_todos_los_expedientes_y_pdf_y_sigue_sin_acceso_administrativo(): void
    {
        Storage::fake('public');
        [$orden] = $this->orden();
        $otro = Medico::create(['nombre' => 'Dra. Referente']);
        $orden->update(['medico_id' => $otro->id, 'estado' => 'finalizado']);
        Storage::disk('public')->put($orden->reporteGuardadoPath(), '%PDF-1.7 resultados firmados originales');
        $general = Medico::where('portal_usuario', 'medicos')->sole();
        app(AccesoMedicoService::class)->configurarGeneral(true, 'ClaveCompartida2026');
        auth('web')->logout();
        $this->post('/expediente/ingresar', ['usuario' => 'medicos', 'password' => 'ClaveCompartida2026'])->assertRedirect('/expediente');
        $this->assertAuthenticatedAs($general, 'medico');
        $this->get('/expediente')->assertOk()->assertSee('Paciente Prueba');
        $this->get('/expediente/pacientes/'.$orden->cliente_id)->assertOk();
        $pdf = $this->get('/expediente/ordenes/'.$orden->id.'/pdf')->assertOk();
        $this->assertSame('%PDF-1.7 resultados firmados originales', file_get_contents($pdf->baseResponse->getFile()->getPathname()));
        $this->get('/admin')->assertRedirect('/admin/login');
        $general->refresh();
        $general->password = 'OtraClaveCompartida2026';
        $general->save();
        $this->get('/expediente')->assertRedirect('/expediente/ingresar');
    }

    public function test_recepcion_no_configura_el_acceso_general_desde_una_accion_oculta(): void
    {
        $usuario = User::factory()->create();
        $usuario->assignRole('Recepcion');
        $this->actingAs($usuario);
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        app(AccesoMedicoService::class)->configurarGeneral(true, 'UnaClave2026');
    }
}
