<?php

namespace Tests\Feature;

use App\Filament\Resources\OrdenResource\Pages\IngresarResultados;
use App\Filament\Resources\OrdenResource\Pages\ListOrdens;
use App\Models\Cliente;
use App\Models\DetalleOrden;
use App\Models\Examen;
use App\Models\GrupoEtario;
use App\Models\Orden;
use App\Models\Prueba;
use App\Models\Resultado;
use App\Models\TipoExamen;
use App\Models\User;
use App\Support\ImagenPdf;
use Database\Seeders\RolesPermisosSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Livewire\Livewire;
use Tests\TestCase;

class AutoriaResultadosTest extends TestCase
{
    use RefreshDatabase;

    public function test_guardar_y_completar_desde_otro_laboratorista_conserva_autores_y_sellos_en_pdf_parcial_y_final(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        config(['laboratorio.logo' => 'images/logo-ausente-en-prueba.png']);
        $this->seed(RolesPermisosSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();

        $laboratoristas = [];
        foreach (['Laboratorista uno', 'Laboratorista dos'] as $i => $nombre) {
            $laboratorista = User::factory()->create(['name' => $nombre]);
            $laboratorista->assignRole('Laboratorista');
            $firma = 'firmas/'.$laboratorista->id.'/firma.png';
            $sello = 'sellos/'.$laboratorista->id.'/sello.png';
            foreach ([$firma => 50, $sello => 150] as $path => $azul) {
                $imagen = ReporteResultadosDisenoTest::imagen(50 + $i * 100, 40, $azul);
                Storage::disk('public')->put($path, base64_decode(explode(',', $imagen, 2)[1]));
            }
            $laboratorista->update(['firma_path' => $firma, 'sello_path' => $sello]);
            $laboratoristas[] = $laboratorista;
        }
        [$primero, $segundo] = $laboratoristas;
        $this->assertNotSame(ImagenPdf::desdeDiscoPublico($primero->sello_path), ImagenPdf::desdeDiscoPublico($segundo->sello_path));
        [$orden, $detalles] = $this->crearOrden();
        $capturados = [];
        View::composer('pdf.reporte_resultados', function ($vista) use (&$capturados): void {
            $capturados = $vista->getData();
        });

        $this->actingAs($primero);
        $paginaPrimero = Livewire::test(IngresarResultados::class, ['record' => $orden]);
        $paginaPrimero
            ->set('data.resultados_examenes.'.$detalles[0]->id.'.pruebas_unitarias.0.resultado', '90')
            ->call('save')->assertHasNoFormErrors();
        $trasPrimerGuardado = $this->resultadosDe($orden);
        $esperadoPrimero = [$detalles[0]->id => ['resultado' => '90', 'user_id' => $primero->id]];
        $this->assertSame($esperadoPrimero, $trasPrimerGuardado);

        $paginaPrimero->call('generarPdfParcial')->assertFileDownloaded('PACIENTE-AUTORIA - '.$orden->id.' P.PDF');
        $primerParcial = $capturados['grupos_por_usuario'];
        $this->assertGruposDeAutores($primerParcial, [$primero], [['Glucosa' => '90']]);
        $this->assertSame($trasPrimerGuardado, $this->resultadosDe($orden), 'Generar el primer parcial debe ser una lectura.');

        $this->actingAs($segundo);
        $paginaSegundo = Livewire::test(IngresarResultados::class, ['record' => $orden->fresh()]);
        $paginaSegundo
            ->assertSet('data.resultados_examenes.'.$detalles[0]->id.'.pruebas_unitarias.0.resultado', '90')
            ->set('data.resultados_examenes.'.$detalles[1]->id.'.pruebas_unitarias.0.resultado', '180')
            ->call('save')->assertHasNoFormErrors();
        $trasSegundoGuardado = $this->resultadosDe($orden);
        $antesDeCompletar = Resultado::orderBy('id')->get()->toArray();

        // Completar y generar el final antes de afirmar la segunda autoría permite
        // identificar un fallo previo sin dejar el flujo final sin comprobar.
        $capturados = [];
        $paginaSegundo->call('generarPdfParcial')->assertFileDownloaded('PACIENTE-AUTORIA - '.$orden->id.' P.PDF');
        $segundoParcial = $capturados['grupos_por_usuario'];
        $this->assertSame($trasSegundoGuardado, $this->resultadosDe($orden), 'Generar el segundo parcial debe ser una lectura.');

        // La segunda pantalla no cambia ningún campo entre Guardar y Completar.
        $paginaSegundo->callAction('completar')->assertHasNoFormErrors();
        $this->assertSame('finalizado', $orden->fresh()->estado);
        $trasCompletar = $this->resultadosDe($orden);

        $capturados = [];
        Livewire::test(ListOrdens::class)->set('activeTab', 'finalizado')
            ->callTableAction('generarReporte', $orden->fresh())
            ->assertFileDownloaded($orden->reporteGuardadoFileName());
        $final = $capturados['grupos_por_usuario'];
        $this->assertSame($trasCompletar, $this->resultadosDe($orden), 'Generar el final debe ser una lectura.');
        Storage::disk('public')->assertExists($orden->reporteGuardadoPath());

        $esperado = $esperadoPrimero + [$detalles[1]->id => ['resultado' => '180', 'user_id' => $segundo->id]];
        $evidencia = json_encode([
            'primer_guardado' => $trasPrimerGuardado,
            'segundo_guardado' => $trasSegundoGuardado,
            'completar' => $trasCompletar,
            'autores_primer_parcial' => array_column($primerParcial, 'laboratorista'),
            'autores_segundo_parcial' => array_column($segundoParcial, 'laboratorista'),
            'autores_pdf_final' => array_column($final, 'laboratorista'),
            'imagenes_pdf_final' => array_map(fn ($grupo) => [
                'laboratorista' => $grupo['laboratorista'],
                'sello_del_primero' => $grupo['sello_b64'] === ImagenPdf::desdeDiscoPublico($primero->sello_path),
                'sello_del_segundo' => $grupo['sello_b64'] === ImagenPdf::desdeDiscoPublico($segundo->sello_path),
                'firma_del_primero' => $grupo['firma_b64'] === ImagenPdf::desdeDiscoPublico($primero->firma_path),
                'firma_del_segundo' => $grupo['firma_b64'] === ImagenPdf::desdeDiscoPublico($segundo->firma_path),
            ], $final),
            'estado_orden' => $orden->fresh()->estado,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $this->assertSame($esperado, $trasSegundoGuardado, "Guardar el segundo examen no debe reasignar el primero. Evidencia del flujo completo:\n".$evidencia);
        $this->assertGruposDeAutores($segundoParcial, [$primero, $segundo], [['Glucosa' => '90'], ['Colesterol total' => '180']]);
        $this->assertSame($esperado, $trasCompletar, 'Completar sin cambios debe conservar ambos autores.');
        $this->assertSame($antesDeCompletar, Resultado::orderBy('id')->get()->toArray(), 'Completar y generar PDF no deben reescribir resultados guardados.');
        $this->assertGruposDeAutores($final, [$primero, $segundo], [['Glucosa' => '90'], ['Colesterol total' => '180']]);
    }

    private function crearOrden(): array
    {
        $grupo = GrupoEtario::create([
            'nombre' => 'Adultos', 'edad_min' => 18, 'edad_max' => 120,
            'unidad_tiempo' => 'años', 'genero' => 'Ambos', 'estado' => 'activo',
        ]);
        $cliente = Cliente::create([
            'nombre' => 'Paciente', 'apellido' => 'Autoria',
            'genero' => 'Femenino', 'fecha_nacimiento' => '1990-01-01',
        ]);
        $orden = Orden::create(['cliente_id' => $cliente->id, 'fecha' => now(), 'total' => 20, 'estado' => 'en proceso']);
        $tipo = TipoExamen::create(['nombre' => 'Química']);
        $detalles = [];
        foreach (['Glucosa', 'Colesterol total'] as $i => $nombre) {
            $examen = Examen::create(['nombre' => $nombre, 'tipo_examen_id' => $tipo->id, 'precio' => 10]);
            $prueba = Prueba::create(['nombre' => $nombre, 'examen_id' => $examen->id, 'estado' => 'activo']);
            $detalles[] = DetalleOrden::create([
                'orden_id' => $orden->id, 'examen_id' => $examen->id,
                'nombre_examen' => $nombre, 'precio_examen' => 10, 'status' => 'pendiente',
                'pruebas_snapshot' => [[
                    'id' => $prueba->id, 'nombre' => $nombre, 'tipo_conjunto' => null,
                    'valores_referencia' => [[
                        'grupo_etario_id' => $grupo->id, 'genero' => 'Ambos',
                        'valor_min' => $i === 0 ? 70 : 140, 'valor_max' => $i === 0 ? 110 : 200,
                        'operador' => 'rango', 'unidades' => 'mg/dL', 'descriptivo' => null, 'nota' => null,
                    ]],
                ]],
            ]);
        }

        return [$orden, $detalles];
    }

    private function resultadosDe(Orden $orden): array
    {
        return $orden->resultados()->orderBy('detalle_orden_id')->get()
            ->mapWithKeys(fn ($resultado) => [$resultado->detalle_orden_id => [
                'resultado' => $resultado->resultado, 'user_id' => $resultado->user_id,
            ]])->all();
    }

    private function assertGruposDeAutores(array $grupos, array $autores, array $resultadosEsperados): void
    {
        $this->assertCount(count($autores), $grupos);
        $porAutor = collect($grupos)->keyBy('laboratorista');
        foreach ($autores as $i => $autor) {
            $this->assertArrayHasKey($autor->name, $porAutor->all());
            $grupo = $porAutor[$autor->name];
            $this->assertSame(ImagenPdf::desdeDiscoPublico($autor->sello_path), $grupo['sello_b64']);
            $this->assertSame(ImagenPdf::desdeDiscoPublico($autor->firma_path), $grupo['firma_b64']);
            $pruebas = collect($grupo['datos'])->flatten(1)
                ->flatMap(fn ($examen) => $examen['pruebas_unitarias']);
            foreach ($pruebas as $prueba) {
                $this->assertSame($autor->id, $prueba['user_id']);
            }
            $resultados = $pruebas->mapWithKeys(fn ($prueba) => [$prueba['nombre'] => $prueba['resultado']])->all();
            $this->assertSame($resultadosEsperados[$i], $resultados);
        }
    }
}
