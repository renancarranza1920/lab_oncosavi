<?php

namespace Tests\Feature;

use App\Filament\Resources\OrdenResource\Pages\IngresarResultados;
use App\Filament\Resources\OrdenResource\Pages\ListOrdens;
use App\Models\Cliente;
use App\Models\DetalleOrden;
use App\Models\Examen;
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

class RegeneracionResultadosPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_regenerar_parcial_y_final_usa_las_imagenes_actuales_de_los_autores_sin_reasignar_resultados(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $this->seed(RolesPermisosSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        $autores = User::factory()->count(2)->create();
        $cliente = Cliente::create(['nombre' => 'Paciente', 'apellido' => 'Ejemplo', 'genero' => 'Femenino', 'fecha_nacimiento' => '1990-01-01']);
        $orden = Orden::create(['cliente_id' => $cliente->id, 'fecha' => now(), 'total' => 20, 'estado' => 'en proceso']);
        foreach ($autores as $i => $autor) {
            $tipo = TipoExamen::create(['nombre' => 'Área '.($i + 1)]);
            $examen = Examen::create(['nombre' => 'Examen '.($i + 1), 'tipo_examen_id' => $tipo->id, 'precio' => 10]);
            $prueba = Prueba::create(['nombre' => 'Prueba '.($i + 1), 'examen_id' => $examen->id, 'estado' => 'activo']);
            $detalle = DetalleOrden::create(['orden_id' => $orden->id, 'examen_id' => $examen->id, 'nombre_examen' => $examen->nombre, 'precio_examen' => 10, 'status' => 'pendiente']);
            Resultado::create(['detalle_orden_id' => $detalle->id, 'prueba_id' => $prueba->id, 'resultado' => '12', 'user_id' => $autor->id, 'prueba_nombre_snapshot' => $prueba->nombre]);
        }
        $antes = Resultado::orderBy('id')->get()->toArray();
        $capturados = [];
        View::composer('pdf.reporte_resultados', function ($vista) use (&$capturados) {
            $capturados = $vista->getData();
        });
        $pagina = Livewire::test(IngresarResultados::class, ['record' => $orden]);
        $pagina->call('generarPdfParcial')->assertFileDownloaded('PACIENTE-EJEMPLO - '.$orden->id.' P.PDF');
        $this->assertCount(2, $capturados['grupos_por_usuario']);
        $this->assertNull($capturados['grupos_por_usuario'][0]['sello_b64']);

        // Los sellos se suben después de guardar y de generar el primer parcial.
        foreach ($autores as $i => $autor) {
            $firma = 'firmas/'.$autor->id.'/firma.png';
            $sello = 'sellos/'.$autor->id.'/sello.png';
            $imagen = ReporteResultadosDisenoTest::imagen(50 + 50 * $i, 0, 200);
            Storage::disk('public')->put($firma, base64_decode(explode(',', $imagen, 2)[1]));
            Storage::disk('public')->put($sello, base64_decode(explode(',', $imagen, 2)[1]));
            $autor->update(['firma_path' => $firma, 'sello_path' => $sello]);
        }
        $pagina->call('generarPdfParcial')->assertFileDownloaded('PACIENTE-EJEMPLO - '.$orden->id.' P.PDF');
        foreach ($autores as $i => $autor) {
            $grupo = $capturados['grupos_por_usuario'][$i];
            $this->assertSame($autor->name, $grupo['laboratorista']);
            $this->assertSame(ImagenPdf::desdeDiscoPublico($autor->sello_path), $grupo['sello_b64']);
            $this->assertNotNull($grupo['firma_b64']);
        }
        $this->assertSame($antes, Resultado::orderBy('id')->get()->toArray());
        $orden->update(['estado' => 'finalizado']);
        Livewire::test(ListOrdens::class)->set('activeTab', 'finalizado')
            ->callTableAction('generarReporte', $orden, ['incluir_firmas' => true]);
        foreach ($autores as $i => $autor) {
            $this->assertSame($autor->name, $capturados['grupos_por_usuario'][$i]['laboratorista']);
            $this->assertNotNull($capturados['grupos_por_usuario'][$i]['sello_b64']);
        }
        $this->assertSame($antes, Resultado::orderBy('id')->get()->toArray());
        Storage::disk('public')->assertExists($orden->reporteGuardadoPath());
    }

    public function test_reemplazar_imagen_con_mismo_nombre_y_fecha_no_reutiliza_bytes_anteriores(): void
    {
        Storage::fake('public');
        $path = 'sellos/ejemplo.png';
        Storage::disk('public')->put($path, base64_decode(explode(',', ReporteResultadosDisenoTest::imagen(200, 0, 0, 1600, 850), 2)[1]));
        $fecha = filemtime(Storage::disk('public')->path($path));
        $antes = ImagenPdf::desdeDiscoPublico($path);
        Storage::disk('public')->put($path, base64_decode(explode(',', ReporteResultadosDisenoTest::imagen(0, 0, 200, 1600, 850), 2)[1]));
        touch(Storage::disk('public')->path($path), $fecha);
        $this->assertNotSame($antes, ImagenPdf::desdeDiscoPublico($path));
    }
}
