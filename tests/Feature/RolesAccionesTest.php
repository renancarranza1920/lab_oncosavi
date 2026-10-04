<?php

namespace Tests\Feature;

use App\Filament\Auth\EditProfile;
use App\Filament\Resources\ActivityLogResource;
use App\Filament\Resources\ActivityLogResource\Pages\ListActivityLogs;
use App\Filament\Resources\OrdenResource\Pages\IngresarResultados;
use App\Filament\Resources\OrdenResource\Pages\ListOrdens;
use App\Filament\Resources\PruebaResource\Pages\ListPruebas;
use App\Filament\Widgets\UltimasOrdenesWidget;
use App\Models\Cliente;
use App\Models\DetalleOrden;
use App\Models\Examen;
use App\Models\Orden;
use App\Models\Prueba;
use App\Models\Resultado;
use App\Models\TipoExamen;
use App\Models\User;
use Database\Seeders\RolesPermisosSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolesAccionesTest extends TestCase
{
    use RefreshDatabase;

    protected array $usuarios;
    protected Orden $orden;
    protected DetalleOrden $detalle;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermisosSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
        foreach (['admin', 'Recepcion', 'Laboratorista'] as $rol) {
            $this->usuarios[$rol] = User::factory()->create(['nickname' => 'test.' . strtolower($rol)]);
            $this->usuarios[$rol]->assignRole($rol);
        }
        $cliente = Cliente::create(['nombre' => 'Paciente', 'apellido' => 'Prueba', 'genero' => 'Femenino']);
        $tipo = TipoExamen::create(['nombre' => 'Química']);
        $examen = Examen::create(['nombre' => 'Glucosa', 'precio' => 10, 'tipo_examen_id' => $tipo->id]);
        $this->orden = Orden::create(['cliente_id' => $cliente->id, 'total' => 10, 'fecha' => now(), 'estado' => 'en proceso']);
        $this->detalle = DetalleOrden::create(['orden_id' => $this->orden->id, 'examen_id' => $examen->id, 'nombre_examen' => $examen->nombre, 'precio_examen' => 10, 'status' => 'pendiente']);
    }

    public function test_los_tres_roles_tienen_accesos_distintos_y_las_urls_no_evaden_permisos(): void
    {
        foreach ($this->usuarios as $rol => $usuario) {
            $this->actingAs($usuario);
            $this->get('/admin')->assertOk();
            $this->get('/admin/ordenes')->assertOk();
            $this->get('/admin/users')->assertStatus($rol === 'admin' ? 200 : 403);
            $this->get('/admin/shield/roles')->assertStatus($rol === 'admin' ? 200 : 403);
            $this->get('/admin/clientes')->assertOk();
            $this->get('/admin/cotizaciones')->assertOk();
            $this->get('/admin/ordenes/create')->assertOk();
            $this->get('/admin/ordenes/' . $this->orden->id . '/ingresar-resultados')->assertStatus($rol === 'Recepcion' ? 403 : 200);
            $this->get(route('filament.admin.auth.profile'))->assertOk();
        }
    }

    public function test_recepcion_no_puede_guardar_resultados_ni_finalizar_desde_los_botones(): void
    {
        $this->actingAs($this->usuarios['Recepcion']);
        Livewire::test(ListOrdens::class)->set('activeTab', 'en proceso')
            ->assertTableActionHidden('ingresarResultados', $this->orden)
            ->assertTableActionHidden('finalizarOrden', $this->orden)
            ->call('mountTableAction', 'finalizarOrden', (string) $this->orden->id)
            ->assertSet('mountedTableActions', [])
            ->call('callMountedTableAction');
        $this->assertSame('en proceso', $this->orden->fresh()->estado);
        Livewire::test(UltimasOrdenesWidget::class)->assertTableActionHidden('resultados', $this->orden);
        $pagina = new IngresarResultados;
        $pagina->record = $this->orden;
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $pagina->save();
    }

    public function test_recepcion_genera_descarga_y_comparte_pdf_sin_acceso_a_resultados(): void
    {
        Storage::fake('public');
        $this->orden->update(['estado' => 'finalizado']);
        $this->actingAs($this->usuarios['Recepcion']);
        $pagina = Livewire::test(ListOrdens::class)->set('activeTab', 'finalizado')
            ->assertTableActionVisible('generarReporte', $this->orden)
            ->callTableAction('generarReporte', $this->orden, ['observaciones_edicion' => '', 'incluir_firmas' => false]);
        $path = $this->orden->reporteGuardadoPath();
        Storage::disk('public')->assertExists($path);
        $this->assertStringStartsWith('%PDF-', Storage::disk('public')->get($path));
        $pagina->assertTableActionVisible('verReporteGuardado', $this->orden)
            ->assertTableActionVisible('enviarPorCorreoOWhatsApp', $this->orden);
        $this->get(route('orden.reporte.guardado', $this->orden))->assertOk();
        $this->actingAs($this->usuarios['Laboratorista']);
        $this->get(route('orden.reporte.guardado', $this->orden))->assertOk();
        Role::findByName('Laboratorista')->revokePermissionTo('descargar_reporte_orden');
        $this->usuarios['Laboratorista']->unsetRelation('roles');
        $this->get(route('orden.reporte.guardado', $this->orden))->assertForbidden();
    }

    public function test_laboratorista_sube_su_firma_y_sello_sin_editar_otros_usuarios_o_roles(): void
    {
        Storage::fake('public');
        $usuario = $this->usuarios['Laboratorista'];
        $otro = $this->usuarios['admin'];
        $this->actingAs($usuario);
        Livewire::test(EditProfile::class)
            ->fillForm(['firma_path' => UploadedFile::fake()->image('firma.png'), 'sello_path' => UploadedFile::fake()->image('sello.png')])
            ->set('data.roles', ['admin'])
            ->set('data.id', $otro->id)
            ->call('save')->assertHasNoFormErrors();
        $usuario->refresh();
        $this->assertStringStartsWith('firmas/' . $usuario->id . '/', $usuario->firma_path);
        $this->assertStringStartsWith('sellos/' . $usuario->id . '/', $usuario->sello_path);
        Storage::disk('public')->assertExists($usuario->firma_path);
        $this->assertTrue($usuario->hasRole('Laboratorista'));
        $this->assertFalse($usuario->hasRole('admin'));
        $this->assertNull($otro->fresh()->firma_path);
    }

    public function test_dashboard_oculta_ingresos_y_clientes_segun_el_rol_y_bloquea_widgets_directos(): void
    {
        foreach ($this->usuarios as $rol => $usuario) {
            $this->actingAs($usuario);
            Livewire::test(\App\Filament\Widgets\OrdenStatsWidget::class)
                ->assertSee('Órdenes de Hoy');
            $pagina = Livewire::test(\App\Filament\Widgets\OrdenStatsWidget::class);
            if ($rol === 'admin') $pagina->assertSee('Ingresos de Hoy');
            else $pagina->assertDontSee('Ingresos de Hoy');
            $pagina->assertSee('Total de Clientes');
        }
        Role::findByName('Recepcion')->revokePermissionTo('dashboard_examenes');
        $this->usuarios['Recepcion']->unsetRelation('roles');
        $this->actingAs($this->usuarios['Recepcion']);
        Livewire::test(\App\Filament\Widgets\ExamenesPopularesChart::class)->assertForbidden();
    }

    public function test_bitacora_solo_permite_admin_incluso_con_permisos_directos_anteriores(): void
    {
        $resultado = Resultado::create(['detalle_orden_id' => $this->detalle->id, 'resultado' => '12', 'es_externo' => true]);
        $registro = Activity::where('subject_type', Resultado::class)->firstOrFail();
        $privado = Activity::create(['log_name' => 'Usuarios', 'description' => 'Registro privado', 'subject_type' => User::class,
            'subject_id' => $this->usuarios['admin']->id, 'properties' => ['old' => ['password' => 'HASH_ANTIGUO'], 'attributes' => ['password' => 'HASH_NUEVO', 'name' => 'Administrador']]]);
        $this->actingAs($this->usuarios['Laboratorista']);
        $this->usuarios['Laboratorista']->givePermissionTo(['view_any_activity::log', 'view_activity::log', 'ver_bitacora_completa', \Spatie\Permission\Models\Permission::findOrCreate('ver_bitacora_soporte', 'web')]);
        Livewire::test(ListActivityLogs::class)->assertForbidden();
        $this->get(ActivityLogResource::getUrl())->assertForbidden();
        $this->get('/admin/bitacora-soporte')->assertForbidden();
        $this->assertFalse($this->usuarios['Laboratorista']->can('view', $privado));
        $this->actingAs($this->usuarios['Recepcion']);
        $this->get(ActivityLogResource::getUrl())->assertForbidden();
        $this->actingAs($this->usuarios['admin']);
        Livewire::test(ListActivityLogs::class)->mountTableAction('view', $privado)
            ->assertDontSee('HASH_ANTIGUO')->assertDontSee('HASH_NUEVO')->assertSee('Administrador');
        $this->usuarios['admin']->update(['password' => 'ClaveNueva12345']);
        $log = Activity::where('subject_type', User::class)->latest('id')->firstOrFail();
        $this->assertArrayNotHasKey('password', $log->properties->get('attributes', []));
    }

    public function test_laboratorista_no_modifica_catalogo_sin_permiso(): void
    {
        $this->actingAs($this->usuarios['Laboratorista']);
        $prueba = Prueba::create(['examen_id' => $this->detalle->examen_id, 'nombre' => 'Prueba', 'estado' => 'activo']);
        Livewire::test(ListPruebas::class)->assertTableActionHidden('cambiar_estado', $prueba)
            ->call('mountTableAction', 'cambiar_estado', (string) $prueba->id)
            ->assertSet('mountedTableActions', []);
        $this->assertSame('activo', $prueba->fresh()->estado);
        $this->get('/admin/pruebas/' . $prueba->id . '/edit')->assertForbidden();
        $this->assertSame([], \App\Support\Bitacora::datosVisibles(['password' => 'secreto', 'remember_token' => 'secreto']));
    }

    public function test_perfil_rechaza_una_firma_almacenada_de_otro_usuario(): void
    {
        Storage::fake('public');
        $path = 'firmas/' . $this->usuarios['admin']->id . '/ajena.png';
        Storage::disk('public')->put($path, UploadedFile::fake()->image('ajena.png')->getContent());
        $this->actingAs($this->usuarios['Laboratorista']);
        Livewire::test(EditProfile::class)->fillForm(['firma_path' => ['archivo' => $path]])
            ->call('save')->assertHasFormErrors(['firma_path']);
        $this->assertNull($this->usuarios['Laboratorista']->fresh()->firma_path);
    }

    public function test_crear_solo_admin_es_repetible_y_conserva_passwords(): void
    {
        Storage::fake('local');
        $this->artisan('oncosavi:usuarios-prueba')->assertSuccessful();
        $cuentas = User::where('nickname', 'like', 'prueba.%')->get();
        $this->assertCount(1, $cuentas);
        $this->assertSame('prueba.admin', $cuentas->sole()->nickname);
        $hashes = $cuentas->pluck('password', 'nickname')->all();
        $archivo = Storage::disk('local')->get('usuarios-prueba.json');
        foreach (json_decode($archivo, true) as $cuenta) {
            $usuario = $cuentas->firstWhere('nickname', $cuenta['usuario']);
            $this->assertTrue(\Illuminate\Support\Facades\Hash::check($cuenta['password'], $usuario->password));
            $this->assertTrue($usuario->hasRole($cuenta['rol']));
        }
        $this->artisan('oncosavi:usuarios-prueba')->assertSuccessful();
        $this->assertSame($hashes, User::where('nickname', 'like', 'prueba.%')->get()->pluck('password', 'nickname')->all());
        $this->assertSame($archivo, Storage::disk('local')->get('usuarios-prueba.json'));
    }

    public function test_seeder_no_duplica_rol_laboratorista_en_minusculas(): void
    {
        Role::where('name', 'Laboratorista')->first()->update(['name' => 'laboratorista']);
        $this->seed(RolesPermisosSeeder::class);
        $this->assertSame(1, Role::whereRaw('LOWER(name) = ?', ['laboratorista'])->count());
    }
}
