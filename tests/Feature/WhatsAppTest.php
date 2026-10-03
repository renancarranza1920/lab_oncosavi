<?php

namespace Tests\Feature;

use App\Filament\Pages\EnviosWhatsApp;
use App\Filament\Resources\CotizacionResource\Pages\CreateCotizacion;
use App\Filament\Resources\OrdenResource\Pages\ListOrdens;
use App\Models\Cliente;
use App\Models\EnvioWhatsApp;
use App\Models\Examen;
use App\Models\Orden;
use App\Models\TipoExamen;
use App\Models\User;
use App\Services\WhatsAppService;
use Database\Seeders\RolesPermisosSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class WhatsAppTest extends TestCase
{
    use RefreshDatabase;

    private User $recepcion;

    private Orden $orden;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermisosSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
        $this->recepcion = User::factory()->create(['nickname' => 'recepcion.wa']);
        $this->recepcion->assignRole('Recepcion');
        $cliente = Cliente::create(['nombre' => 'Paciente', 'apellido' => 'Prueba', 'genero' => 'Femenino', 'telefono' => '50377778888']);
        $this->orden = Orden::create(['cliente_id' => $cliente->id, 'total' => 10, 'fecha' => now(), 'estado' => 'finalizado']);
        Storage::fake('public');
        Storage::disk('public')->put($this->orden->reporteGuardadoPath(), '%PDF-1.7 prueba');
        config(['whatsapp.enabled' => true, 'whatsapp.webhook_secret' => str_repeat('a', 64), 'whatsapp.bridge_token' => str_repeat('b', 64)]);
        Http::preventStrayRequests();
        $this->actingAs($this->recepcion);
    }

    public function test_boton_envia_pdf_por_n8n_y_no_repite_el_mismo_documento(): void
    {
        Http::fake(['n8n:5678/*' => Http::response(['status' => 'sent', 'messageId' => 'confirmacion-123'])]);
        Livewire::test(ListOrdens::class)->set('activeTab', 'finalizado')
            ->callTableAction('enviarPorCorreoOWhatsApp', $this->orden, ['canal' => 'whatsapp'])
            ->assertNotified('PDF enviado por WhatsApp');
        $envio = app(WhatsAppService::class)->orden($this->orden);
        Http::assertSentCount(1);
        Http::assertSent(fn ($r) => $r->hasHeader('X-Oncosavi-Token', str_repeat('a', 64)) && $r['phone'] === '50377778888'
            && base64_decode($r['pdf']) === '%PDF-1.7 prueba' && $r['filename'] === 'orden-'.$this->orden->id.'.pdf');
        $this->assertSame('enviado', $envio->estado);
        $this->assertNotSame('50377778888', DB::table('envios_whatsapp')->value('telefono'));
        $this->assertArrayNotHasKey('telefono', $envio->toArray());
        $this->assertStringNotContainsString('%PDF-', json_encode($envio->getAttributes()));
    }

    public function test_respuesta_perdida_no_se_declara_enviada_ni_se_reintenta_y_se_puede_reconciliar(): void
    {
        Http::fake(['n8n:5678/*' => Http::response(['error' => 'STACK PRIVADO'], 500),
            'whatsapp:3000/messages/*' => Http::response(['status' => 'sent', 'messageId' => 'confirmado-despues'])]);
        $envio = app(WhatsAppService::class)->orden($this->orden);
        $this->assertSame('desconocido', $envio->estado);
        $this->assertStringNotContainsString('STACK PRIVADO', $envio->mensajeEstado());
        $this->assertSame($envio->id, app(WhatsAppService::class)->orden($this->orden)->id);
        Http::assertSentCount(1);
        $this->assertSame('enviado', app(WhatsAppService::class)->actualizar($envio)->estado);
        Http::assertSentCount(2);
    }

    public function test_sin_configuracion_el_boton_muestra_aviso_claro_y_no_abre_whatsapp(): void
    {
        config(['whatsapp.enabled' => false]);
        Http::fake();
        Livewire::test(ListOrdens::class)->set('activeTab', 'finalizado')
            ->callTableAction('enviarPorCorreoOWhatsApp', $this->orden, ['canal' => 'whatsapp'])
            ->assertNotified('No se pudo enviar por WhatsApp');
        Http::assertNothingSent();
        $this->assertDatabaseCount('envios_whatsapp', 0);
    }

    public function test_fallo_de_sesion_tiene_aviso_amigable_y_no_se_confunde_con_envio(): void
    {
        Http::fake(['n8n:5678/*' => Http::response(['status' => 'failed', 'code' => 'not_connected'])]);
        $envio = app(WhatsAppService::class)->orden($this->orden);
        $this->assertSame('fallido', $envio->estado);
        $this->assertStringContainsString('desconectado', $envio->mensajeEstado());
        $this->assertNull($envio->enviado_at);
    }

    public function test_validacion_telefonos_y_pdf_evitan_llamadas_invalidas(): void
    {
        Http::fake();
        $this->assertSame('50377778888', WhatsAppService::numero('7777-8888'));
        $this->assertSame('12025550123', WhatsAppService::numero('+1 (202) 555-0123'));
        $this->assertNull(WhatsAppService::numero('+44 7777 888888'));
        $this->expectException(\DomainException::class);
        try {
            app(WhatsAppService::class)->cotizacion('50377778888', '<html>no pdf</html>');
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_cotizacion_se_genera_y_envia_con_pdf_incluido(): void
    {
        Http::fake(['n8n:5678/*' => Http::response(['status' => 'sent', 'messageId' => 'cotizacion-123'])]);
        $tipo = TipoExamen::create(['nombre' => 'Química']);
        $examen = Examen::create(['nombre' => 'Glucosa', 'precio' => 10, 'tipo_examen_id' => $tipo->id]);
        Livewire::test(CreateCotizacion::class)->fillForm(['nombre_completo' => 'Cliente de prueba', 'whatsapp' => '77778888',
            'whatsapp_codigo_pais' => '503', 'examenes_seleccionados' => [['examen_id' => $examen->id, 'precio_hidden' => 10]], 'perfiles_seleccionados' => []])
            ->call('enviarWhatsApp')->assertHasNoFormErrors()->assertNotified('PDF enviado por WhatsApp');
        Http::assertSent(fn ($r) => $r['phone'] === '50377778888' && str_starts_with(base64_decode($r['pdf']), '%PDF-'));
        $this->assertSame('cotizacion', EnvioWhatsApp::first()->tipo);
    }

    public function test_cada_empleado_ve_sus_envios_y_solo_admin_puede_ver_el_qr(): void
    {
        Http::fake(['n8n:5678/*' => Http::response(['status' => 'sent', 'messageId' => 'mensaje'])]);
        $propio = app(WhatsAppService::class)->orden($this->orden);
        $admin = User::factory()->create(['nickname' => 'admin.wa']);
        $admin->assignRole('admin');
        $ajeno = EnvioWhatsApp::create(['id' => (string) \Illuminate\Support\Str::uuid(), 'user_id' => $admin->id, 'telefono' => '50377778888', 'tipo' => 'cotizacion', 'huella' => str_repeat('a', 64)]);
        Livewire::test(EnviosWhatsApp::class)->assertCanSeeTableRecords([$propio])->assertCanNotSeeTableRecords([$ajeno])
            ->assertActionHidden('vincular')->call('consultarConexion', true)->assertForbidden();
        $this->actingAs($admin);
        Livewire::test(EnviosWhatsApp::class)->assertCanSeeTableRecords([$propio, $ajeno])->assertActionVisible('vincular');
    }

    public function test_ninguna_llamada_directa_evita_el_permiso_de_enviar_resultados(): void
    {
        $usuario = User::factory()->create(['nickname' => 'sin.permiso']);
        $this->actingAs($usuario);
        Http::fake();
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        try {
            app(WhatsAppService::class)->orden($this->orden);
        } finally {
            Http::assertNothingSent();
        }
    }
    public function test_vincular_muestra_progreso_qr_y_cierre_con_motivo_visible(): void
    {
        $admin = User::factory()->create(['nickname' => 'admin.qr']);
        $admin->assignRole('admin');
        $this->actingAs($admin);
        Http::fake(['whatsapp:3000/*' => Http::sequence()
            ->push(['status' => 'connecting', 'qr' => null])
            ->push(['status' => 'qr', 'qr' => 'data:image/png;base64,AA=='])
            ->push(['status' => 'disconnected', 'qr' => null, 'code' => 'protocol_error', 'error' => 'SECRET'])]);
        Livewire::test(EnviosWhatsApp::class)->callAction('vincular')
            ->assertSet('estadoConexion', 'conectando')->assertSee('El QR aparecerá aquí automáticamente')
            ->call('actualizarConexion')->assertSet('qr', 'data:image/png;base64,AA==')
            ->call('actualizarConexion')->assertSet('qr', null)->assertSet('estadoConexion', 'desconectado')
            ->assertSee('WhatsApp rechazó la conexión')->assertDontSee('SECRET');
        Http::assertSentCount(3);
        Http::assertSent(fn ($r) => $r->method() === 'POST' && $r->url() === 'http://whatsapp:3000/connect');
    }

    public function test_error_de_configuracion_permanece_visible_y_poll_no_insiste(): void
    {
        $admin = User::factory()->create(['nickname' => 'admin.error']);
        $admin->assignRole('admin');
        $this->actingAs($admin);
        Http::fake(['whatsapp:3000/*' => Http::response(['error' => 'SECRET'], 401)]);
        Livewire::test(EnviosWhatsApp::class)->callAction('vincular')->assertSet('estadoConexion', 'no disponible')
            ->assertSee('La configuración de WhatsApp no coincide')->assertDontSee('SECRET')
            ->call('actualizarConexion');
        Http::assertSentCount(1);
    }

    public function test_diagnostico_no_imprime_qr_ni_credenciales_y_no_inicia_conexion(): void
    {
        Http::fake(['whatsapp:3000/*' => Http::response(['status' => 'disconnected', 'code' => 'protocol_error', 'qr' => 'SECRET'])]);
        $this->artisan('whatsapp:diagnostico')->expectsOutput('Configuración: completa')
            ->expectsOutput('Puente WhatsApp: HTTP 200')->expectsOutput('Sesión: disconnected')
            ->expectsOutput('Motivo: protocol_error')->assertSuccessful();
        Http::assertSentCount(1);
        Http::assertSent(fn ($r) => $r->method() === 'GET');
    }

}
