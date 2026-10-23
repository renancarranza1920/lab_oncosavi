<?php

namespace Tests\Feature;

use App\Filament\Resources\OrdenResource\Pages\ListOrdens;
use App\Models\Cliente;
use App\Models\DetalleOrden;
use App\Models\Examen;
use App\Models\Muestra;
use App\Models\Orden;
use App\Models\Prueba;
use App\Models\Resultado;
use App\Models\TipoExamen;
use App\Models\User;
use App\Services\RecepcionMuestras;
use Database\Seeders\RolesPermisosSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class RecepcionMuestrasTest extends TestCase
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

    private function crearOrden(): array
    {
        $cliente = Cliente::create(['nombre' => 'Paciente', 'apellido' => 'Ejemplo', 'genero' => 'Femenino']);
        $tipo = TipoExamen::create(['nombre' => 'Bacteriología']);
        $examen = Examen::create(['nombre' => 'Cultivo de secreciones', 'precio' => 10, 'tipo_examen_id' => $tipo->id]);
        $muestras = collect(['Secreciones vaginales', 'Hisopado nasal', 'Secreción de absceso'])
            ->map(fn ($nombre) => Muestra::create(['nombre' => $nombre]));
        $examen->muestras()->attach($muestras->pluck('id')->all());
        $orden = Orden::create(['cliente_id' => $cliente->id, 'fecha' => now(), 'total' => 10, 'estado' => 'pendiente']);
        $detalle = DetalleOrden::create(['orden_id' => $orden->id, 'examen_id' => $examen->id, 'nombre_examen' => $examen->nombre, 'precio_examen' => 10, 'status' => 'pendiente']);

        return [$orden, $detalle, $muestras];
    }

    private function clave(DetalleOrden $detalle, Muestra $muestra): string
    {
        return "d{$detalle->id}_m{$muestra->id}";
    }

    public function test_una_alternativa_es_suficiente_para_procesar_el_cultivo(): void
    {
        [$orden, $detalle, $muestras] = $this->crearOrden();
        Livewire::test(ListOrdens::class)->set('activeTab', 'pendiente')
            ->callTableAction('gestionarMuestras', $orden, ['muestras_recibidas_list' => [$this->clave($detalle, $muestras[0])]])
            ->assertHasNoTableActionErrors();

        $this->assertSame('en proceso', $orden->fresh()->estado);
        $this->assertSame([$muestras[0]->id], $detalle->fresh()->muestras_recibidas);
        $this->assertSame(['Secreciones vaginales'], $detalle->fresh()->nombresMuestrasRecibidas());
        $this->assertSame(auth()->id(), $orden->fresh()->toma_muestra_user_id);
        $this->assertNotNull($orden->fresh()->fecha_toma_muestra);
    }

    public function test_sin_muestras_recibidas_sigue_pendiente(): void
    {
        [$orden, $detalle] = $this->crearOrden();
        Livewire::test(ListOrdens::class)->set('activeTab', 'pendiente')
            ->callTableAction('gestionarMuestras', $orden, ['muestras_recibidas_list' => []])
            ->assertHasNoTableActionErrors();

        $this->assertSame('pendiente', $orden->fresh()->estado);
        $this->assertNull($orden->fresh()->fecha_toma_muestra);
        $this->assertEmpty($detalle->fresh()->muestras_recibidas);
    }

    public function test_recepcion_parcial_exige_una_muestra_por_examen_y_recupera_las_ya_marcadas(): void
    {
        [$orden, $detalle, $muestras] = $this->crearOrden();
        $otro = $detalle->replicate();
        $otro->save();
        $claves = $muestras->map(fn ($muestra) => $this->clave($detalle, $muestra))->all();
        Livewire::test(ListOrdens::class)->set('activeTab', 'pendiente')
            ->callTableAction('gestionarMuestras', $orden, ['muestras_recibidas_list' => $claves]);
        $this->assertSame('pendiente', $orden->fresh()->estado);
        $this->assertSame($muestras->pluck('id')->all(), $detalle->fresh()->muestras_recibidas);

        Livewire::test(ListOrdens::class)->set('activeTab', 'pendiente')
            ->mountTableAction('gestionarMuestras', $orden)
            ->assertTableActionDataSet(['muestras_recibidas_list' => $claves])
            ->set('mountedTableActionsData.0.muestras_recibidas_list', [$claves[0], $this->clave($otro, $muestras[0])])
            ->callMountedTableAction()->assertHasNoTableActionErrors();
        $this->assertSame('en proceso', $orden->fresh()->estado);
        $this->assertSame([$muestras[0]->id], $detalle->fresh()->muestras_recibidas);
        $this->assertSame([$muestras[0]->id], $otro->fresh()->muestras_recibidas);
    }

    public function test_examenes_sin_muestras_configuradas_no_bloquean_ni_inventan_una_toma(): void
    {
        [$orden, $detalle] = $this->crearOrden();
        $detalle->examen->muestras()->detach();
        Livewire::test(ListOrdens::class)->set('activeTab', 'pendiente')
            ->callTableAction('gestionarMuestras', $orden)->assertHasNoTableActionErrors();
        $this->assertSame('en proceso', $orden->fresh()->estado);
        $this->assertNull($orden->fresh()->fecha_toma_muestra);
        $this->assertNull($orden->fresh()->toma_muestra_user_id);
    }

    public static function estadosCorregibles(): array
    {
        return [['en proceso'], ['pausada'], ['finalizado']];
    }

    #[DataProvider('estadosCorregibles')]
    public function test_corregir_muestras_conserva_estado_y_datos_originales_de_recepcion(string $estado): void
    {
        [$orden, $detalle, $muestras] = $this->crearOrden();
        $receptor = User::factory()->create();
        $fecha = now()->subDays(3)->startOfSecond();
        $orden->update(['estado' => $estado, 'fecha_toma_muestra' => $fecha, 'toma_muestra_user_id' => $receptor->id]);
        $detalle->forceFill(['muestras_recibidas' => $muestras->pluck('id')->all()])->save();
        $prueba = Prueba::create(['nombre' => 'Resultado de cultivo', 'examen_id' => $detalle->examen_id, 'estado' => 'activo']);
        Resultado::create(['detalle_orden_id' => $detalle->id, 'prueba_id' => $prueba->id, 'resultado' => 'Dato conservado', 'user_id' => $receptor->id]);
        $resultadosOriginales = Resultado::all()->toArray();

        Livewire::test(ListOrdens::class)->set('activeTab', $estado)
            ->assertTableActionVisible('gestionarMuestras', $orden)
            ->mountTableAction('gestionarMuestras', $orden)
            ->set('mountedTableActionsData.0.muestras_recibidas_list', [$this->clave($detalle, $muestras[0])])
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors();

        $this->assertSame($estado, $orden->fresh()->estado);
        $this->assertTrue($fecha->equalTo($orden->fresh()->fecha_toma_muestra));
        $this->assertSame($receptor->id, $orden->fresh()->toma_muestra_user_id);
        $this->assertSame([$muestras[0]->id], $detalle->fresh()->muestras_recibidas);
        $this->assertSame($resultadosOriginales, Resultado::all()->toArray());
        $evento = Activity::where('description', 'Muestras recibidas actualizadas')->sole();
        $this->assertSame(auth()->id(), $evento->causer_id);
        $this->assertSame($muestras->pluck('id')->all(), $evento->properties['anteriores'][$detalle->id]);
    }

    public function test_no_se_puede_quitar_toda_la_recepcion_de_un_examen_ya_procesado(): void
    {
        [$orden, $detalle, $muestras] = $this->crearOrden();
        $orden->update(['estado' => 'finalizado']);
        $detalle->forceFill(['muestras_recibidas' => [$muestras[0]->id]])->save();
        Livewire::test(ListOrdens::class)->set('activeTab', 'finalizado')
            ->callTableAction('gestionarMuestras', $orden, ['muestras_recibidas_list' => []])
            ->assertHasTableActionErrors(['muestras_recibidas_list']);
        $this->assertSame([$muestras[0]->id], $detalle->fresh()->muestras_recibidas);
        $this->assertSame('finalizado', $orden->fresh()->estado);
    }

    public function test_no_acepta_una_muestra_de_otro_detalle_y_no_guarda_cambios_parciales(): void
    {
        [$orden, $detalle, $muestras] = $this->crearOrden();
        try {
            app(RecepcionMuestras::class)->registrar($orden, [$this->clave($detalle, $muestras[0]), 'd999_m999'], auth()->id());
            $this->fail('La selección ajena debía rechazarse.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('muestras_recibidas_list', $exception->errors());
        }
        $this->assertNull($detalle->fresh()->muestras_recibidas);
        $this->assertSame('pendiente', $orden->fresh()->estado);
    }

    public function test_respeta_el_permiso_de_recepcion_y_las_ordenes_canceladas(): void
    {
        [$orden] = $this->crearOrden();
        $orden->update(['estado' => 'cancelado']);
        Livewire::test(ListOrdens::class)->set('activeTab', 'cancelado')
            ->assertTableActionHidden('gestionarMuestras', $orden);
        $orden->update(['estado' => 'finalizado']);
        $usuario = User::factory()->create();
        $usuario->givePermissionTo(['view_orden', 'view_any_orden']);
        $this->actingAs($usuario);
        Livewire::test(ListOrdens::class)->set('activeTab', 'finalizado')
            ->assertTableActionHidden('gestionarMuestras', $orden)
            ->call('mountTableAction', 'gestionarMuestras', (string) $orden->id)
            ->assertSet('mountedTableActions', []);
    }
}
