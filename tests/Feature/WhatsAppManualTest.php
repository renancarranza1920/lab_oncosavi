<?php

namespace Tests\Feature;

use App\Filament\Resources\OrdenResource\Pages\ListOrdens;
use App\Models\Cliente;
use App\Models\Orden;
use App\Models\User;
use Database\Seeders\RolesPermisosSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class WhatsAppManualTest extends TestCase
{
    use RefreshDatabase;

    public function test_compartir_descarga_pdf_y_ofrece_whatsapp_manual_sin_llamadas_externas(): void
    {
        $this->seed(RolesPermisosSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
        $usuario = User::factory()->create();
        $usuario->assignRole('Recepcion');
        $this->actingAs($usuario);
        $cliente = Cliente::create(['nombre' => 'Paciente', 'apellido' => 'Prueba', 'genero' => 'Femenino', 'telefono' => '50377778888']);
        $orden = Orden::create(['cliente_id' => $cliente->id, 'fecha' => now(), 'estado' => 'finalizado', 'total' => 10]);
        Storage::fake('public');
        Storage::disk('public')->put($orden->reporteGuardadoPath(), '%PDF-1.7 prueba manual');
        Http::fake();
        Livewire::test(ListOrdens::class)->set('activeTab', 'finalizado')
            ->mountTableAction('enviarPorCorreoOWhatsApp', $orden)
            ->assertTableActionDataSet(['destino_whatsapp' => null])
            ->callMountedTableAction()->assertHasTableActionErrors(['destino_whatsapp' => 'required'])
            ->setTableActionData(['destino_whatsapp' => '50377778888'])
            ->callMountedTableAction()->assertHasNoTableActionErrors()
            ->assertFileDownloaded($orden->reporteGuardadoFileName(), '%PDF-1.7 prueba manual')
            ->assertDispatched('abrir-destino-envio', fn ($name, $params) => str_starts_with($params['url'], 'https://wa.me/50377778888?text='));
        Http::assertNothingSent();
    }

    public function test_pagina_de_envios_automaticos_ya_no_esta_disponible(): void
    {
        $this->get('/admin/envios-whats-app')->assertNotFound();
    }

    private function prepararEnvio(): Orden
    {
        $this->seed(RolesPermisosSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
        $usuario = User::factory()->create();
        $usuario->assignRole('admin');
        $this->actingAs($usuario);
        $cliente = Cliente::create(['nombre' => 'Paciente', 'apellido' => 'Contactos', 'genero' => 'Femenino', 'correo' => 'paciente@example.test']);
        $cliente->telefonos()->create(['numero' => '50377778888', 'codigo_pais' => '503', 'tipo' => 'movil', 'orden' => 0]);
        $cliente->telefonos()->create(['numero' => '12025550123', 'codigo_pais' => '1', 'tipo' => 'movil', 'orden' => 1]);
        $orden = Orden::create(['cliente_id' => $cliente->id, 'fecha' => now(), 'estado' => 'finalizado', 'total' => 10]);
        Storage::fake('public');
        Storage::disk('public')->put($orden->reporteGuardadoPath(), '%PDF-1.7 destino elegido');

        return $orden;
    }

    public function test_usa_el_segundo_contacto_elegido_y_no_el_primero(): void
    {
        $orden = $this->prepararEnvio();
        Livewire::test(ListOrdens::class)->set('activeTab', 'finalizado')
            ->callTableAction('enviarPorCorreoOWhatsApp', $orden, ['destino_whatsapp' => '12025550123'])
            ->assertHasNoTableActionErrors()->assertFileDownloaded($orden->reporteGuardadoFileName())
            ->assertDispatched('abrir-destino-envio', fn ($name, $params) => str_starts_with($params['url'], 'https://wa.me/12025550123?text='));
        $this->assertSame('50377778888', $orden->cliente->fresh()->telefono);
    }

    public function test_permite_escribir_otro_numero_internacional_sin_modificar_al_paciente(): void
    {
        $orden = $this->prepararEnvio();
        Livewire::test(ListOrdens::class)->set('activeTab', 'finalizado')
            ->callTableAction('enviarPorCorreoOWhatsApp', $orden, [
                'destino_whatsapp' => 'otro', 'telefono_destino_formato' => 'us', 'telefono_destino' => '(202) 555-0199',
            ])->assertHasNoTableActionErrors()->assertFileDownloaded($orden->reporteGuardadoFileName())
            ->assertDispatched('abrir-destino-envio', fn ($name, $params) => str_starts_with($params['url'], 'https://wa.me/12025550199?text='));
        $this->assertSame(['50377778888', '12025550123'], $orden->cliente->fresh()->telefonos_contacto);
    }

    public function test_permite_otro_pais_con_la_mascara_internacional(): void
    {
        $orden = $this->prepararEnvio();
        Livewire::test(ListOrdens::class)->set('activeTab', 'finalizado')
            ->callTableAction('enviarPorCorreoOWhatsApp', $orden, [
                'destino_whatsapp' => 'otro',
                'telefono_destino_formato' => 'internacional',
                'telefono_destino' => '(502)1234-5678',
            ])->assertHasNoTableActionErrors()->assertFileDownloaded($orden->reporteGuardadoFileName())
            ->assertDispatched('abrir-destino-envio', fn ($name, $params) => str_starts_with($params['url'], 'https://wa.me/50212345678?text='));
    }

    public function test_solo_correo_no_ofrece_un_whatsapp_con_destino_automatico(): void
    {
        $orden = $this->prepararEnvio();
        Livewire::test(ListOrdens::class)->set('activeTab', 'finalizado')
            ->callTableAction('enviarPorCorreoOWhatsApp', $orden, ['destino_whatsapp' => 'correo'])
            ->assertHasNoTableActionErrors()->assertFileDownloaded($orden->reporteGuardadoFileName())
            ->assertDispatched('abrir-destino-envio', fn ($name, $params) => str_starts_with($params['url'], 'https://mail.google.com/mail/?view=cm&fs=1&to=paciente%40example.test&'));
    }

    public function test_pdf_parcial_tambien_pregunta_y_usa_el_destinatario_elegido(): void
    {
        $orden = $this->prepararEnvio();
        $orden->update(['estado' => 'en proceso']);
        $nombre = strtoupper(\Illuminate\Support\Str::slug($orden->cliente->nombre.' '.$orden->cliente->apellido).' - '.$orden->id.' P.pdf');
        Storage::disk('public')->put('reportes/'.$nombre, '%PDF-1.7 parcial elegido');
        Livewire::test(\App\Filament\Resources\OrdenResource\Pages\IngresarResultados::class, ['record' => $orden])
            ->mountAction('enviar_pdf_parcial')
            ->assertActionDataSet(['destino_whatsapp' => null])
            ->callMountedAction()->assertHasActionErrors(['destino_whatsapp' => 'required'])
            ->setActionData(['destino_whatsapp' => '12025550123'])
            ->callMountedAction()->assertHasNoActionErrors()->assertFileDownloaded($nombre)
            ->assertDispatched('abrir-destino-envio', fn ($name, $params) => str_starts_with($params['url'], 'https://wa.me/12025550123?text='));
    }
}
