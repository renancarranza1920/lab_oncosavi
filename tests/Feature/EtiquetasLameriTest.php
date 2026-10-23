<?php

namespace Tests\Feature;

use App\Filament\Pages\DetalleOrdenKanban;
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
use App\Services\ZebraLabelService;
use Database\Seeders\RolesPermisosSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EtiquetasLameriTest extends TestCase
{
    use RefreshDatabase;

    private User $operadora;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermisosSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
        $this->operadora = User::factory()->create();
        $this->operadora->assignRole('Recepcion');
        $this->actingAs($this->operadora);
        config(['laboratorio.impresion_etiquetas_habilitada' => true]);
    }

    private function crearOrden(string $apellido = 'Ejemplo', string $estado = 'en proceso'): Orden
    {
        $cliente = Cliente::create(['nombre' => 'Paciente', 'apellido' => $apellido, 'genero' => 'Femenino']);

        return Orden::create(['cliente_id' => $cliente->id, 'fecha' => now(), 'estado' => $estado, 'total' => 10]);
    }

    private function agregarExamen(Orden $orden, string $nombre = 'Glucosa', string $tipo = 'Quimica', string $recipiente = 'quimica_sanguinea'): DetalleOrden
    {
        $area = TipoExamen::firstOrCreate(['nombre' => $tipo]);
        $examen = Examen::create(['nombre' => $nombre, 'tipo_examen_id' => $area->id, 'precio' => 10]);

        return DetalleOrden::create([
            'orden_id' => $orden->id, 'examen_id' => $examen->id, 'nombre_examen' => $nombre,
            'precio_examen' => 10, 'status' => $recipiente,
        ]);
    }

    private function comprobarFormato(string $zpl, int $cantidad): void
    {
        $this->assertSame($cantidad, substr_count($zpl, '^XA'));
        $this->assertSame($cantidad, substr_count($zpl, '^XZ'));
        $this->assertSame($cantidad, substr_count($zpl, '^PW406'));
        $this->assertSame($cantidad, substr_count($zpl, '^LL203'));
        $this->assertSame($cantidad, substr_count($zpl, '^FDLABORATORIO MERINO^FS'));
    }

    public function test_etiquetas_se_pueden_abrir_y_reimprimir_despues_de_recibir_muestras(): void
    {
        $ordenes = collect(['pendiente', 'en proceso', 'pausada', 'finalizado'])
            ->map(fn ($estado) => $this->crearOrden('Ejemplo', $estado));
        $cancelada = $this->crearOrden('Cancelada', 'cancelado');
        $pagina = Livewire::test(ListOrdens::class)->set('activeTab', 'todas');

        foreach ($ordenes as $orden) {
            $pagina->assertTableActionVisible('imprimirEtiquetas', $orden);
        }
        $pagina->assertTableActionHidden('imprimirEtiquetas', $cancelada);

        $detalle = $this->agregarExamen($ordenes[1]);
        Livewire::test(DetalleOrdenKanban::class, ['ordenId' => $ordenes[1]->id])
            ->assertOk()->assertSee($detalle->nombre_examen)
            ->assertActionVisible('Generar ZPL');
    }

    public function test_imprime_individual_grupo_y_orden_sin_modificar_muestras_resultados_o_estado(): void
    {
        $orden = $this->crearOrden();
        $detalle = $this->agregarExamen($orden);
        $this->agregarExamen($orden, 'Examen de orina', 'Orina', 'uroanalisis');
        $ajena = $this->crearOrden('Ajena');
        $this->agregarExamen($ajena, 'EXAMEN AJENO');
        $muestra = Muestra::create(['nombre' => 'Suero']);
        $detalle->examen->muestras()->attach($muestra);
        $detalle->forceFill(['muestras_recibidas' => [$muestra->id]])->save();
        $prueba = Prueba::create(['nombre' => 'Glucosa', 'examen_id' => $detalle->examen_id, 'estado' => 'activo']);
        $resultado = Resultado::create(['detalle_orden_id' => $detalle->id, 'prueba_id' => $prueba->id, 'resultado' => '90', 'user_id' => $this->operadora->id]);
        $originales = [$orden->fresh()->getRawOriginal(), $detalle->fresh()->getRawOriginal(), $resultado->fresh()->getRawOriginal()];

        Livewire::test(DetalleOrdenKanban::class, ['ordenId' => $orden->id])
            ->call('printSingle', $detalle->id)
            ->assertDispatched('imprimirZplFrontend', function ($evento, $datos) use ($orden) {
                $this->comprobarFormato($datos['zpl'], 1);
                $this->assertStringContainsString('^FDGLUCOSA^FS', $datos['zpl']);
                $this->assertStringContainsString('Ord: #'.$orden->id, $datos['zpl']);

                return true;
            });

        Livewire::test(DetalleOrdenKanban::class, ['ordenId' => $orden->id])
            ->call('printGroup', 'quimica_sanguinea')
            ->assertDispatched('imprimirZplFrontend', function ($evento, $datos) {
                $this->comprobarFormato($datos['zpl'], 1);
                $this->assertStringContainsString('-QUIMICA', $datos['zpl']);
                $this->assertStringNotContainsString('-ORINA', $datos['zpl']);
                $this->assertStringNotContainsString('AJENA', $datos['zpl']);

                return true;
            });

        Livewire::test(DetalleOrdenKanban::class, ['ordenId' => $orden->id])
            ->call('printAll')
            ->assertDispatched('imprimirZplFrontend', function ($evento, $datos) {
                $this->comprobarFormato($datos['zpl'], 2);
                $this->assertStringContainsString('-QUIMICA', $datos['zpl']);
                $this->assertStringContainsString('-ORINA', $datos['zpl']);
                $this->assertStringNotContainsString('AJENA', $datos['zpl']);

                return true;
            });

        $this->assertSame($originales, [$orden->fresh()->getRawOriginal(), $detalle->fresh()->getRawOriginal(), $resultado->fresh()->getRawOriginal()]);
    }

    public function test_mantiene_agrupacion_original_en_bloques_de_cuatro_areas_por_recipiente(): void
    {
        $orden = $this->crearOrden();
        foreach (['Area uno', 'Area dos', 'Area tres', 'Area cuatro', 'Area cinco'] as $area) {
            $this->agregarExamen($orden, 'Examen '.$area, $area);
        }
        $this->agregarExamen($orden, 'Otro examen de la primera area', 'Area uno');

        $zpl = app(ZebraLabelService::class)->generarZplMultiple($orden->detalleOrden);
        $this->comprobarFormato($zpl, 2);
        foreach (['AREA UNO', 'AREA DOS', 'AREA TRES', 'AREA CUATRO', 'AREA CINCO'] as $area) {
            $this->assertSame(1, substr_count($zpl, '^FD-'.$area.'^FS'));
        }
    }

    public function test_cultivos_exclusivos_generan_una_etiqueta_por_examen_con_numero_de_orden(): void
    {
        $orden = $this->crearOrden();
        $primero = $this->agregarExamen($orden, 'Cultivo nasal', 'Bacteriologia', 'cultivo_secreciones');
        $segundo = $this->agregarExamen($orden, 'Cultivo faringeo', 'Bacteriologia', 'cultivo_secreciones');

        foreach (['printGroup' => ['cultivo_secreciones'], 'printAll' => []] as $metodo => $argumentos) {
            Livewire::test(DetalleOrdenKanban::class, ['ordenId' => $orden->id])
                ->call($metodo, ...$argumentos)
                ->assertDispatched('imprimirZplFrontend', function ($evento, $datos) use ($orden) {
                    $this->comprobarFormato($datos['zpl'], 2);
                    $this->assertStringContainsString('CULTIVO NASAL', $datos['zpl']);
                    $this->assertStringContainsString('CULTIVO FARINGEO', $datos['zpl']);
                    $this->assertSame(2, substr_count($datos['zpl'], 'Ord: #'.$orden->id.'^FS'));

                    return true;
                });
        }
        $this->assertSame([$primero->id, $segundo->id], $orden->detalleOrden()->pluck('id')->all());
    }

    public function test_desactivar_impresion_conserva_visualizacion_y_no_envia_etiquetas(): void
    {
        $orden = $this->crearOrden();
        $detalle = $this->agregarExamen($orden);
        config(['laboratorio.impresion_etiquetas_habilitada' => false]);

        foreach (['printSingle' => [$detalle->id], 'printGroup' => ['quimica_sanguinea'], 'printAll' => []] as $metodo => $argumentos) {
            Livewire::test(DetalleOrdenKanban::class, ['ordenId' => $orden->id])
                ->assertSee('Glucosa')->assertActionHidden('Generar ZPL')
                ->call($metodo, ...$argumentos)->assertNotDispatched('imprimirZplFrontend');
        }
    }

    public function test_no_imprime_un_detalle_de_otra_orden_ni_un_grupo_vacio(): void
    {
        $orden = $this->crearOrden();
        $this->agregarExamen($orden);
        $detalleAjeno = $this->agregarExamen($this->crearOrden('Ajena'));

        Livewire::test(DetalleOrdenKanban::class, ['ordenId' => $orden->id])
            ->call('printSingle', $detalleAjeno->id)->assertNotDispatched('imprimirZplFrontend');
        Livewire::test(DetalleOrdenKanban::class, ['ordenId' => $orden->id])
            ->call('printGroup', 'uroanalisis')->assertNotDispatched('imprimirZplFrontend');
    }

    public function test_permisos_separan_visualizar_de_imprimir_y_protegen_rutas_zpl(): void
    {
        $orden = $this->crearOrden();
        $detalle = $this->agregarExamen($orden);
        $consulta = User::factory()->create();
        $consulta->givePermissionTo(['access_admin_panel', 'view_any_orden', 'view_orden', 'imprimir_etiquetas_orden']);
        $this->actingAs($consulta);

        Livewire::test(DetalleOrdenKanban::class, ['ordenId' => $orden->id])
            ->assertOk()->assertSee('Glucosa')->assertActionHidden('Generar ZPL')
            ->call('printSingle', $detalle->id)->assertNotDispatched('imprimirZplFrontend')
            ->call('printGroup', 'quimica_sanguinea')->assertNotDispatched('imprimirZplFrontend')
            ->call('printAll')->assertNotDispatched('imprimirZplFrontend');

        $rutas = [route('detalles.zpl', $detalle->id), route('grupo.zpl', ['status' => 'quimica_sanguinea', 'ordenId' => $orden->id]), route('zpl.all', $orden->id)];
        foreach ($rutas as $ruta) {
            $this->getJson($ruta)->assertForbidden();
        }

        $consulta->revokePermissionTo('imprimir_etiquetas_orden');
        $this->actingAs($consulta->fresh());
        Livewire::test(ListOrdens::class)->set('activeTab', 'todas')->assertTableActionHidden('imprimirEtiquetas', $orden);
        Livewire::test(DetalleOrdenKanban::class, ['ordenId' => $orden->id])->assertForbidden();

        auth()->logout();
        foreach ($rutas as $ruta) {
            $this->getJson($ruta)->assertUnauthorized();
        }
    }

    public function test_rutas_individual_grupo_y_completa_devuelven_zpl_de_la_orden_solicitada(): void
    {
        $orden = $this->crearOrden();
        $detalle = $this->agregarExamen($orden);
        $this->agregarExamen($orden, 'Examen de orina', 'Orina', 'uroanalisis');
        $this->agregarExamen($this->crearOrden('Ajena'), 'EXAMEN AJENO');

        foreach ([
            [route('detalles.zpl', $detalle->id), 1],
            [route('grupo.zpl', ['status' => 'quimica_sanguinea', 'ordenId' => $orden->id]), 1],
            [route('zpl.all', $orden->id), 2],
        ] as [$ruta, $cantidad]) {
            $respuesta = $this->getJson($ruta)->assertOk()->assertJsonStructure(['zpl']);
            $zpl = $respuesta->json('zpl');
            $this->comprobarFormato($zpl, $cantidad);
            $this->assertStringContainsString('PACIENTE EJEMPLO', $zpl);
            $this->assertStringNotContainsString('AJENA', $zpl);
        }
    }
}
