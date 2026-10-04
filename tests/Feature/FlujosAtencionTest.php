<?php

namespace Tests\Feature;

use App\Filament\Resources\ClientesResource\Pages\CreateClientes;
use App\Filament\Resources\ClientesResource\Pages\EditClientes;
use App\Filament\Resources\CotizacionResource\Pages\CreateCotizacion;
use App\Filament\Resources\OrdenResource\Pages\IngresarResultados;
use App\Models\Cliente;
use App\Models\DetalleOrden;
use App\Models\Orden;
use App\Models\Resultado;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class FlujosAtencionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
        $user = User::factory()->create();
        foreach (['view_any_clientes', 'create_clientes', 'update_clientes', 'view_any_cotizacion', 'ingresar_resultados_orden', 'access_cotizaciones', 'generar_pdf_cotizacion', 'enviar_cotizacion_whatsapp', 'enviar_cotizacion_email'] as $permiso) {
            $user->givePermissionTo(Permission::findOrCreate($permiso, 'web'));
        }
        $this->actingAs($user);
    }

    public function test_guarda_telefono_usa_y_limpia_edad_y_grupo_al_elegir_fecha(): void
    {
        Livewire::test(CreateClientes::class)->assertStatus(200)
            ->fillForm([
                'nombre' => 'Paciente', 'apellido' => 'USA', 'genero' => 'Femenino',
                'telefonos' => [['numero_codigo_pais' => '1', 'numero' => '202-555-0123', 'tipo' => 'movil']],
                'edad' => 40, 'grupo_etario' => '123',
            ])
            ->set('data.fecha_nacimiento', '1990-05-20')
            ->assertSet('data.edad', null)
            ->assertSet('data.grupo_etario', null)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('clientes', [
            'apellido' => 'USA', 'telefono' => '12025550123',
            'edad' => null, 'grupo_etario' => null, 'fecha_nacimiento' => '1990-05-20',
        ]);
    }

    public function test_rechaza_longitud_incorrecta_y_conserva_numero_salvadoreno_al_editar(): void
    {
        Livewire::test(CreateClientes::class)->assertStatus(200)
            ->fillForm([
                'nombre' => 'Paciente', 'apellido' => 'Prueba', 'genero' => 'Femenino',
                'telefonos' => [['numero_codigo_pais' => '1', 'numero' => '77777777', 'tipo' => 'movil']],
            ])
            ->call('create')->assertHasFormErrors(['telefonos.0.numero']);

        $cliente = Cliente::create([
            'nombre' => 'Paciente', 'apellido' => 'Local', 'genero' => 'Femenino',
            'telefono' => '77777777',
        ]);
        $pagina = Livewire::test(EditClientes::class, ['record' => $cliente->getRouteKey()]);
        $clave = array_key_first($pagina->get('data.telefonos'));
        $pagina->assertSet('data.telefonos.'.$clave.'.numero_codigo_pais', '503')
            ->assertSet('data.telefonos.'.$clave.'.numero', '77777777')
            ->call('save')->assertHasNoFormErrors();
        $this->assertSame('50377777777', $cliente->fresh()->telefono);
    }

    public function test_cotizacion_genera_enlaces_nativos_con_destinatario_resumen_y_total(): void
    {
        $tipo = \App\Models\TipoExamen::create(['nombre' => 'Química']);
        $examen = \App\Models\Examen::create(['tipo_examen_id' => $tipo->id, 'nombre' => 'Glucosa', 'precio' => 15.50]);
        $componente = Livewire::test(CreateCotizacion::class)->assertStatus(200)
            ->fillForm([
                'nombre_completo' => 'Paciente USA', 'whatsapp_codigo_pais' => '1',
                'whatsapp' => '202-555-0123', 'email' => 'paciente@example.com',
                'examenes_seleccionados' => [['examen_id' => $examen->id, 'precio_hidden' => 15.50]],
            ]);
        // Las acciones se renderizan como enlaces nativos, sin eventos de apertura de ventanas.
        $html = $componente->html();
        $this->assertStringContainsString('https://wa.me/12025550123?text=', $html);
        $this->assertStringContainsString('https://mail.google.com/mail/?', $html);
        $this->assertStringContainsString('paciente%40example.com', $html);
        $this->assertStringContainsString(rawurlencode('Paciente USA'), $html);
        $this->assertStringContainsString(rawurlencode('$15.50'), $html);
        $this->assertStringContainsString('target="_blank"', $html);
        $this->assertStringNotContainsString('open-url-in-new-tab', $html);
    }

    public function test_descarga_cotizacion_como_pdf_sin_encabezados_http_en_el_archivo(): void
    {
        $pagina = new CreateCotizacion;
        $pagina->mount();
        $respuesta = $pagina->generatePdfPreview();
        ob_start();
        $respuesta->sendContent();
        $contenido = ob_get_clean();

        $this->assertSame('application/pdf', $respuesta->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF-', $contenido);
        $this->assertStringContainsString('attachment;', $respuesta->headers->get('Content-Disposition'));
    }

    public function test_eliminar_una_fila_externa_preserva_otras_filas_y_ediciones_sin_guardar(): void
    {
        $cliente = Cliente::create(['nombre' => 'Paciente', 'apellido' => 'Prueba', 'genero' => 'Femenino']);
        $orden = Orden::create(['cliente_id' => $cliente->id, 'total' => 10, 'fecha' => now(), 'estado' => 'en proceso']);
        $tipo = \App\Models\TipoExamen::create(['nombre' => 'Referidos']);
        $examen = \App\Models\Examen::create(['tipo_examen_id' => $tipo->id, 'nombre' => 'Referido', 'precio' => 10, 'es_externo' => true]);
        $detalle = DetalleOrden::create(['orden_id' => $orden->id, 'examen_id' => $examen->id, 'nombre_examen' => 'Referido', 'precio_examen' => 10, 'status' => 'pendiente']);
        $primero = Resultado::create(['detalle_orden_id' => $detalle->id, 'resultado' => '1', 'es_externo' => true]);
        $segundo = Resultado::create(['detalle_orden_id' => $detalle->id, 'resultado' => '2', 'es_externo' => true]);

        $pagina = new IngresarResultados;
        $pagina->record = $orden;
        $pagina->data = ['resultados_examenes' => [$detalle->id => [
            'pruebas_unitarias' => [['resultado' => 'Edición pendiente']],
            'externos' => [
                ['id' => $primero->id, 'temp_id' => 'a', 'resultado' => '1'],
                ['id' => $segundo->id, 'temp_id' => 'b', 'resultado' => 'Modificado sin guardar'],
                ['id' => null, 'temp_id' => 'c', 'resultado' => 'Fila nueva'],
            ],
        ]]];
        $pagina->removeExternalRow($detalle->id, 0);

        $this->assertDatabaseMissing('resultados', ['id' => $primero->id]);
        $this->assertDatabaseHas('resultados', ['id' => $segundo->id, 'resultado' => '2']);
        $filas = $pagina->data['resultados_examenes'][$detalle->id];
        $this->assertCount(2, $filas['externos']);
        $this->assertSame('Modificado sin guardar', $filas['externos'][1]['resultado']);
        $this->assertSame('Fila nueva', $filas['externos'][2]['resultado']);
        $this->assertSame('Edición pendiente', $filas['pruebas_unitarias'][0]['resultado']);

        $pagina->removeExternalRow($detalle->id, 2);
        $this->assertCount(1, $pagina->data['resultados_examenes'][$detalle->id]['externos']);
        $this->assertDatabaseHas('resultados', ['id' => $segundo->id]);
    }
}
