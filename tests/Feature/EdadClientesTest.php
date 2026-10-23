<?php

namespace Tests\Feature;

use App\Filament\Resources\ClientesResource\Pages\ListClientes;
use App\Models\Cliente;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolesPermisosSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EdadClientesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-10-23 12:00:00', 'America/El_Salvador'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_calcula_periodos_cumplidos_y_distingue_datos_manuales_y_ausentes(): void
    {
        $casos = [
            ['1989-10-24', null, '36 años'],
            ['1989-10-23', null, '37 años'],
            ['2025-10-23', null, '1 año'],
            ['2025-10-24', null, '11 meses'],
            ['2026-04-23', null, '6 meses'],
            ['2026-09-23', null, '1 mes'],
            ['2026-09-24', null, '29 días'],
            ['2026-10-22', null, '1 día'],
            ['2026-10-23', null, '0 días'],
            ['2026-10-24', null, null],
            [null, 1, '1 año'],
            [null, 34, '34 años'],
            [null, null, null],
        ];

        foreach ($casos as [$nacimiento, $manual, $esperado]) {
            $cliente = new Cliente(['fecha_nacimiento' => $nacimiento, 'edad' => $manual]);
            $this->assertSame($esperado, $cliente->edad_legible, 'Fecha: '.($nacimiento ?? 'sin fecha'));
        }
    }

    public function test_tabla_clientes_muestra_edad_calculada_incluso_sin_fecha_de_nacimiento(): void
    {
        $this->seed(RolesPermisosSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $crear = fn (string $apellido, array $datos) => Cliente::create([
            'nombre' => 'Paciente', 'apellido' => $apellido, 'genero' => 'Femenino', ...$datos,
        ]);
        $adulto = $crear('Adulto', ['fecha_nacimiento' => '1989-10-24']);
        $bebe = $crear('Bebe', ['fecha_nacimiento' => '2026-04-23']);
        $manual = $crear('Manual', ['edad' => 34]);
        $sinEdad = $crear('Sin edad', []);

        Livewire::test(ListClientes::class)
            ->assertCanSeeTableRecords([$adulto, $bebe, $manual, $sinEdad])
            ->assertTableColumnStateSet('edad_legible', '36 años', $adulto)
            ->assertTableColumnStateSet('edad_legible', '6 meses', $bebe)
            ->assertTableColumnStateSet('edad_legible', '34 años', $manual)
            ->assertTableColumnStateSet('edad_legible', null, $sinEdad)
            ->assertSee('36 años')->assertSee('6 meses')->assertSee('34 años')
            ->assertSee('Sin edad registrada')->assertSee('24/10/1989');

        $this->assertNull($adulto->fresh()->edad);
        $this->assertNull($bebe->fresh()->edad);
        $this->assertSame(34, (int) $manual->fresh()->edad);
    }
}
