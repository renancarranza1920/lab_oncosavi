<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\DetalleOrden;
use App\Models\Examen;
use App\Models\Orden;
use App\Models\Resultado;
use App\Models\TipoExamen;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class ReferenciasResultadosSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_ambas_referencias_son_text_y_admiten_contenido_largo_y_null(): void
    {
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        $this->assertTiposDeReferencia('text');

        $snapshot = str_repeat("Niños: 3 – 8 mg/dL\r\nAdultos: 4 – 10 mg/dL\n", 20);
        $externo = str_repeat("Referencia externa: ≤ 12 µg/L\n", 30);
        $resultado = $this->crearResultado($snapshot, $externo)->fresh();

        $this->assertSame($snapshot, $resultado->valor_referencia_snapshot);
        $this->assertSame($externo, $resultado->valor_referencia_externo);

        $sinReferencias = $this->crearResultado()->fresh();
        $this->assertNull($sinReferencias->valor_referencia_snapshot);
        $this->assertNull($sinReferencias->valor_referencia_externo);
    }

    public function test_down_y_up_conservan_referencias_de_255_caracteres_y_null(): void
    {
        // El límite cuenta caracteres, incluso cuando cada carácter ocupa varios bytes.
        $snapshot = str_repeat('á', 255);
        $externo = str_repeat('🧪', 255);
        $resultado = $this->crearResultado($snapshot, $externo);
        $sinReferencias = $this->crearResultado();
        $antes = Resultado::orderBy('id')->get()->toArray();
        $migracion = $this->migracion();

        $migracion->down();

        $this->assertTiposDeReferencia('varchar');
        $this->assertSame($antes, Resultado::orderBy('id')->get()->toArray());
        $previo = $this->crearResultado('135 - 145 mmol/L', 'Negativo');
        $antesDeAmpliar = Resultado::orderBy('id')->get()->toArray();

        $migracion->up();

        $this->assertTiposDeReferencia('text');
        $this->assertSame($antesDeAmpliar, Resultado::orderBy('id')->get()->toArray());
        $this->assertSame($snapshot, $resultado->fresh()->valor_referencia_snapshot);
        $this->assertSame($externo, $resultado->fresh()->valor_referencia_externo);
        $this->assertNull($sinReferencias->fresh()->valor_referencia_snapshot);
        $this->assertNull($sinReferencias->fresh()->valor_referencia_externo);
        $this->assertSame('135 - 145 mmol/L', $previo->fresh()->valor_referencia_snapshot);
        $this->assertSame('Negativo', $previo->fresh()->valor_referencia_externo);
    }

    public static function referenciasDemasiadoLargas(): array
    {
        return [
            'snapshot de 256 caracteres ASCII' => ['valor_referencia_snapshot', str_repeat('A', 256)],
            'externa de 256 caracteres ASCII' => ['valor_referencia_externo', str_repeat('B', 256)],
            'snapshot de 256 caracteres multibyte' => ['valor_referencia_snapshot', str_repeat('á', 256)],
            'externa de 256 caracteres multibyte' => ['valor_referencia_externo', str_repeat('🧪', 256)],
        ];
    }

    #[DataProvider('referenciasDemasiadoLargas')]
    public function test_down_rechaza_referencias_largas_sin_cambiar_el_esquema_ni_truncar_datos(string $columna, string $referencia): void
    {
        $this->crearResultado('Referencia corta', 'Otra referencia corta');
        $resultado = $this->crearResultado();
        $resultado->update([$columna => $referencia]);
        $antes = Resultado::orderBy('id')->get()->toArray();
        $esquemaAntes = Schema::getColumns('resultados');

        try {
            $this->migracion()->down();
            $this->fail('El rollback debe rechazar cualquier referencia de más de 255 caracteres.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('más de 255 caracteres', $exception->getMessage());
        }

        $this->assertSame($esquemaAntes, Schema::getColumns('resultados'));
        $this->assertTiposDeReferencia('text');
        $this->assertSame($antes, Resultado::orderBy('id')->get()->toArray());
        $this->assertSame($referencia, $resultado->fresh()->getAttribute($columna));
    }

    private function assertTiposDeReferencia(string $tipo): void
    {
        $this->assertSame($tipo, Schema::getColumnType('resultados', 'valor_referencia_snapshot'));
        $this->assertSame($tipo, Schema::getColumnType('resultados', 'valor_referencia_externo'));
    }

    private function migracion(): Migration
    {
        return require database_path('migrations/2026_10_07_000001_expand_resultados_reference_columns.php');
    }

    private function crearResultado(?string $snapshot = null, ?string $externo = null): Resultado
    {
        $cliente = Cliente::create([
            'nombre' => 'Paciente',
            'apellido' => 'Referencias',
            'genero' => 'Femenino',
            'fecha_nacimiento' => '1990-01-01',
        ]);
        $orden = Orden::create([
            'cliente_id' => $cliente->id,
            'fecha' => now(),
            'total' => 10,
            'estado' => 'en proceso',
        ]);
        $tipo = TipoExamen::create(['nombre' => 'Química']);
        $examen = Examen::create([
            'nombre' => 'Examen de referencia',
            'tipo_examen_id' => $tipo->id,
            'precio' => 10,
        ]);
        $detalle = DetalleOrden::create([
            'orden_id' => $orden->id,
            'examen_id' => $examen->id,
            'nombre_examen' => $examen->nombre,
            'precio_examen' => 10,
            'status' => 'pendiente',
        ]);

        return Resultado::create([
            'detalle_orden_id' => $detalle->id,
            'resultado' => '12',
            'prueba_nombre_snapshot' => 'Prueba de referencia',
            'valor_referencia_snapshot' => $snapshot,
            'valor_referencia_externo' => $externo,
        ]);
    }
}
