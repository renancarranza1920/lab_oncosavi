<?php

namespace Tests\Feature;

use App\Models\Cliente;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Tests\TestCase;

class MigracionTelefonosTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh')->assertSuccessful();
    }

    protected function tearDown(): void
    {
        RefreshDatabaseState::$migrated = false;
        RefreshDatabaseState::$inMemoryConnections = [];
        parent::tearDown();
    }

    public function test_migration_preserves_old_contacts_and_copies_international_prefixes(): void
    {
        $migration = require database_path('migrations/2026_10_03_000003_create_cliente_telefonos_table.php');
        $migration->down();
        $cases = [
            ['7777-8888', '50377778888', '503'],
            ['+1 (202) 555-0123', '12025550123', '1'],
            ['+81 90 1234 5678', '819012345678', '81'],
        ];
        $clientes = [];
        foreach ($cases as [$original]) {
            $clientes[] = Cliente::create(['nombre' => 'Paciente', 'apellido' => 'Migración', 'genero' => 'Femenino', 'telefono' => $original]);
        }
        $sinTelefono = Cliente::create(['nombre' => 'Paciente', 'apellido' => 'Sin contacto', 'genero' => 'Femenino']);
        $migration->up();
        foreach ($cases as $index => [, $numero, $codigo]) {
            $this->assertDatabaseHas('cliente_telefonos', ['cliente_id' => $clientes[$index]->id, 'numero' => $numero, 'codigo_pais' => $codigo]);
            $this->assertSame($numero, $clientes[$index]->fresh()->telefono);
        }
        $this->assertSame(0, $sinTelefono->telefonos()->count());
        $this->assertSame(4, Cliente::count());
    }
}
