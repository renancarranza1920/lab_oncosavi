<?php

namespace Tests\Feature;

use App\Filament\Resources\OrdenResource\Pages\ListOrdens;
use App\Models\Cliente;
use App\Models\DetalleOrden;
use App\Models\Examen;
use App\Models\Orden;
use App\Models\Prueba;
use App\Models\Resultado;
use App\Models\TipoExamen;
use App\Models\User;
use App\Support\SelloLaboratorio;
use Database\Seeders\RolesPermisosSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReporteSinSellosTest extends TestCase
{
    use RefreshDatabase;

    public static function opcionesFirmas(): array
    {
        return ['desactivadas' => [false], 'activadas' => [true]];
    }

    #[DataProvider('opcionesFirmas')]
    public function test_generar_reporte_respeta_la_opcion_para_los_tres_elementos_y_conserva_el_archivo_al_descargar(bool $incluirFirmas): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $this->seed(RolesPermisosSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $institucional = UploadedFile::fake()->image('institucional.png', 260, 110)
            ->store('sellos/laboratorio', 'public');
        SelloLaboratorio::guardar($institucional);
        $autor = User::factory()->create(['name' => 'Laboratorista original']);
        $autor->assignRole('Laboratorista');
        $autor->update([
            'firma_path' => UploadedFile::fake()->image('firma.png', 170, 105)
                ->store('firmas/'.$autor->id, 'public'),
            'sello_path' => UploadedFile::fake()->image('sello.png', 170, 90)
                ->store('sellos/'.$autor->id, 'public'),
        ]);
        $cliente = Cliente::create([
            'nombre' => 'Paciente', 'apellido' => 'Sellos', 'genero' => 'Femenino',
            'fecha_nacimiento' => '1990-01-01',
        ]);
        $orden = Orden::create(['cliente_id' => $cliente->id, 'fecha' => now(), 'total' => 10, 'estado' => 'finalizado']);
        $tipo = TipoExamen::create(['nombre' => 'Química']);
        $examen = Examen::create(['nombre' => 'Glucosa', 'tipo_examen_id' => $tipo->id, 'precio' => 10]);
        $prueba = Prueba::create(['nombre' => 'GLUCOSA', 'examen_id' => $examen->id, 'estado' => 'activo']);
        $detalle = DetalleOrden::create([
            'orden_id' => $orden->id, 'examen_id' => $examen->id, 'nombre_examen' => $examen->nombre,
            'precio_examen' => 10, 'status' => 'quimica_sanguinea',
            'pruebas_snapshot' => [[
                'id' => $prueba->id, 'nombre' => $prueba->nombre, 'tipo_conjunto' => null,
                'tipo_prueba_id' => null, 'valores_referencia' => [],
            ]],
        ]);
        $resultado = Resultado::create([
            'detalle_orden_id' => $detalle->id, 'prueba_id' => $prueba->id, 'resultado' => '14.52',
            'prueba_nombre_snapshot' => $prueba->nombre, 'valor_referencia_snapshot' => '0 - 110',
            'unidades_snapshot' => 'mg/dL', 'user_id' => $autor->id,
            'created_at' => now()->subDays(2)->startOfSecond(), 'updated_at' => now()->subDays(2)->startOfSecond(),
        ]);
        $resultadoAntes = $resultado->fresh()->getRawOriginal();
        $datos = [];
        $documento = null;
        $generaciones = 0;
        View::composer('pdf.reporte_resultados', function ($vista) use (&$datos): void {
            $datos = $vista->getData();
        });
        $this->app->resolving('dompdf.wrapper', function ($pdf) use (&$documento, &$generaciones): void {
            $documento = $pdf;
            $generaciones++;
        });

        Livewire::test(ListOrdens::class)->set('activeTab', 'finalizado')
            ->callTableAction('generarReporte', $orden, ['incluir_firmas' => $incluirFirmas])
            ->assertHasNoTableActionErrors()
            ->assertFileDownloaded($orden->reporteGuardadoFileName());

        $grupo = collect($datos['grupos_por_usuario'])->sole();
        $this->assertSame($autor->name, $grupo['laboratorista']);
        if ($incluirFirmas) {
            $this->assertNotNull($datos['sello_registro_b64']);
            $this->assertNotNull($grupo['sello_b64']);
            $this->assertNotNull($grupo['firma_b64']);
        } else {
            $this->assertNull($datos['sello_registro_b64']);
            $this->assertNull($grupo['sello_b64']);
            $this->assertNull($grupo['firma_b64']);
        }

        $html = view('pdf.reporte_resultados', $datos)->render();
        $this->assertSame($incluirFirmas, str_contains($html, 'class="firma-container"'));
        $this->assertStringContainsString('LABORATORIO', $html);
        $this->assertStringContainsString('GLUCOSA', $html);
        $this->assertStringContainsString('14.52', $html);
        $this->assertCount($incluirFirmas ? 3 : 0, $this->imagenesDeFirmas($documento));
        $this->assertSame($resultadoAntes, $resultado->fresh()->getRawOriginal());

        $archivo = Storage::disk('public')->get($orden->reporteGuardadoPath());
        $this->assertStringStartsWith('%PDF-', $archivo);
        $this->assertSame(1, $generaciones);
        $descarga = $this->get(route('orden.reporte.guardado', ['orden' => $orden, 'descargar' => 1]))
            ->assertOk()->assertDownload($orden->reporteGuardadoFileName());
        $this->assertSame($archivo, file_get_contents($descarga->baseResponse->getFile()->getPathname()));
        $this->assertSame(1, $generaciones, 'Descargar debe conservar el PDF existente, sin volver a generarlo.');
        $this->assertSame($resultadoAntes, $resultado->fresh()->getRawOriginal());
    }

    private function imagenesDeFirmas($documento): array
    {
        $objetos = $documento->getDomPDF()->getCanvas()->get_cpdf()->objects;
        $imagenes = [];
        foreach ($objetos as $objeto) {
            if ($objeto['t'] !== 'page') {
                continue;
            }
            $contenido = implode('', array_map(fn ($id) => $objetos[$id]['c'], $objeto['info']['contents']));
            preg_match_all('/([\d.]+) 0 0 ([\d.]+) ([\d.]+) ([\d.]+) cm \/I\d+ Do/', $contenido, $dibujadas, PREG_SET_ORDER);
            foreach ($dibujadas as $imagen) {
                // Las decoraciones se conservan; estos anchos identifican los sellos y la firma.
                if (abs((float) $imagen[1] - 97.5) < 0.001 || abs((float) $imagen[1] - 127.5) < 0.001) {
                    $imagenes[] = $imagen;
                }
            }
        }

        return $imagenes;
    }
}
