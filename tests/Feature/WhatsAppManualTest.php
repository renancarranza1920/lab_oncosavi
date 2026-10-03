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
            ->callTableAction('enviarPorCorreoOWhatsApp', $orden)
            ->assertFileDownloaded($orden->reporteGuardadoFileName(), '%PDF-1.7 prueba manual');
        Http::assertNothingSent();
        $notificaciones = new \Filament\Notifications\Livewire\Notifications;
        $notificaciones->mount();
        $aviso = $notificaciones->notifications->first(fn ($n) => $n->getTitle() === 'PDF Descargado');
        $this->assertNotNull($aviso);
        $whatsapp = collect($aviso->getActions())->first(fn ($a) => $a->getName() === 'whatsapp');
        $this->assertStringStartsWith('https://wa.me/50377778888?text=', $whatsapp->getUrl());
        $this->assertStringContainsString(rawurlencode('Estimado(a) *Paciente Prueba*'), $whatsapp->getUrl());
    }

    public function test_pagina_de_envios_automaticos_ya_no_esta_disponible(): void
    {
        $this->get('/admin/envios-whats-app')->assertNotFound();
    }
}
