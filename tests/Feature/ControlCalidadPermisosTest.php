<?php

namespace Tests\Feature;

use App\Filament\Resources\RoleResource;
use App\Filament\Resources\RoleResource\Pages\CreateRole;
use App\Filament\Resources\RoleResource\Pages\EditRole;
use App\Models\User;
use App\Support\ControlCalidadPermissions as Cci;
use Database\Seeders\RolesPermisosSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ControlCalidadPermisosTest extends TestCase
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

    public function test_el_registro_de_permisos_es_aditivo_y_no_concede_el_modulo_a_los_otros_roles(): void
    {
        $unrelated = Permission::findOrCreate('permiso_de_otro_modulo', 'web');
        $admin = Role::findByName('admin', 'web');
        $admin->givePermissionTo($unrelated);
        $laboratorista = Role::findOrCreate('Consulta CCI personalizada', 'web');
        $laboratorista->givePermissionTo([Cci::VIEW, $unrelated]);
        $before = $laboratorista->fresh()->permissions->pluck('name')->sort()->values()->all();

        Cci::register();
        Cci::register();

        $this->assertSame(count(Cci::all()), Permission::where('guard_name', 'web')->whereIn('name', Cci::all())->count());
        $this->assertCount(8, Cci::all());
        $this->assertTrue($admin->fresh()->hasAllPermissions(Cci::all()));
        $this->assertTrue($admin->fresh()->hasPermissionTo($unrelated));
        $this->assertSame($before, $laboratorista->fresh()->permissions->pluck('name')->sort()->values()->all());
        $this->assertFalse(Role::findByName('Recepcion', 'web')->hasPermissionTo(Cci::VIEW));
    }

    public function test_la_pestana_cci_aparece_en_el_recurso_de_roles_sin_duplicar_permisos_personalizados(): void
    {
        $role = Role::findOrCreate('Consulta CCI', 'web');
        $this->get('/admin/shield/roles/'.$role->id.'/edit')->assertOk()
            ->assertSee(__('filament-shield::filament-shield.custom'))->assertSee('Control de Calidad Interno');
        $this->assertContains(RoleResource::class, Filament::getCurrentPanel()->getResources());
        $this->assertNotContains(\BezhanSalleh\FilamentShield\Resources\RoleResource::class, Filament::getCurrentPanel()->getResources());
        $this->assertSame([], array_intersect(Cci::all(), array_keys(RoleResource::getCustomPermissionOptions())));
    }

    public function test_guardar_la_pestana_cci_cambia_sus_permisos_y_conserva_los_otros_modulos(): void
    {
        $role = Role::findOrCreate('Captura CCI', 'web');
        $role->givePermissionTo(['access_admin_panel', 'view_any_orden', Cci::VIEW, Cci::HISTORY]);

        Livewire::test(EditRole::class, ['record' => $role->id])
            ->assertFormSet(['cci_permissions_0' => [Cci::VIEW, Cci::HISTORY]])
            ->fillForm([
                'cci_permissions_0' => [Cci::VIEW, Cci::CHARTS],
                'cci_permissions_1' => [],
                'cci_permissions_2' => [Cci::REGISTER],
            ])->call('save')->assertHasNoFormErrors();

        $expected = ['access_admin_panel', 'view_any_orden', Cci::VIEW, Cci::CHARTS, Cci::REGISTER];
        sort($expected);
        $this->assertSame($expected, $role->fresh()->permissions->pluck('name')->sort()->values()->all());

        Livewire::test(EditRole::class, ['record' => $role->id])
            ->fillForm(['cci_permissions_0' => [], 'cci_permissions_1' => [], 'cci_permissions_2' => []])
            ->call('save')->assertHasNoFormErrors();
        $this->assertSame(['access_admin_panel', 'view_any_orden'], $role->fresh()->permissions->pluck('name')->sort()->values()->all());
    }

    public function test_el_permiso_de_exportacion_se_guarda_desde_su_grupo_y_se_puede_retirar(): void
    {
        $role = Role::findOrCreate('Informes CCI', 'web');
        $role->givePermissionTo(['access_admin_panel', 'view_any_orden', Cci::VIEW, Cci::HISTORY]);
        Livewire::test(EditRole::class, ['record' => $role->id])->fillForm(['cci_permissions_3' => [Cci::EXPORT]])
            ->call('save')->assertHasNoFormErrors();
        $this->assertTrue($role->fresh()->hasAllPermissions([Cci::VIEW, Cci::HISTORY, Cci::EXPORT, 'view_any_orden']));
        Livewire::test(EditRole::class, ['record' => $role->id])->fillForm(['cci_permissions_3' => []])
            ->call('save')->assertHasNoFormErrors();
        $this->assertFalse($role->fresh()->hasPermissionTo(Cci::EXPORT));
        $this->assertTrue($role->fresh()->hasPermissionTo('view_any_orden'));
    }

    #[DataProvider('rolesQa')]
    public function test_qa_habilita_los_roles_del_laboratorio_y_se_abre_desde_el_menu(string $name): void
    {
        $role = Role::findOrCreate($name, 'web');
        $unrelated = Permission::findOrCreate('permiso_existente_del_laboratorio', 'web');
        $role->givePermissionTo($unrelated);
        Cci::register();
        Cci::register();
        $this->assertTrue($role->fresh()->hasAllPermissions(['access_admin_panel', ...Cci::all()]));
        $this->assertTrue($role->fresh()->hasPermissionTo($unrelated));
        $user = User::factory()->create();
        $user->assignRole($role);
        $this->actingAs($user);
        $items = collect(Filament::getPanel('admin')->getNavigationItems());
        $qa = $items->first(fn ($item) => str_starts_with($item->getLabel(), 'QA'));
        $this->assertNotNull($qa);
        $this->assertTrue($qa->isVisible());
        $this->assertSame('Gestión de Laboratorio', $qa->getGroup());
        $this->assertSame('/control-calidad-interno', parse_url($qa->getUrl(), PHP_URL_PATH));
        foreach (Filament::getPanel('control-calidad')->getPages() as $page) {
            $this->get($page::getUrl(panel: 'control-calidad'))->assertOk();
        }
    }

    public static function rolesQa(): array
    {
        return [['admin'], ['Laboratorista'], ['super_admin'], ['superadmin']];
    }

    public function test_el_menu_qa_no_aparece_sin_su_permiso_y_la_migracion_no_borra_datos(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Recepcion');
        $this->actingAs($user);
        $qa = collect(Filament::getPanel('admin')->getNavigationItems())->first(fn ($item) => str_starts_with($item->getLabel(), 'QA'));
        $this->assertFalse($qa->isVisible());
        $before = DB::table('cci_areas')->insertGetId(['nombre' => 'Área existente', 'equipo' => 'Equipo existente', 'created_at' => now(), 'updated_at' => now()]);
        $migration = require database_path('migrations/2026_11_12_000001_enable_quality_control_for_lab_roles.php');
        $migration->up();
        $this->assertDatabaseHas('cci_areas', ['id' => $before, 'nombre' => 'Área existente']);
        $this->assertFalse(Role::findByName('Recepcion')->hasPermissionTo(Cci::VIEW));
    }

    public function test_se_puede_crear_un_rol_con_permisos_del_modulo_y_editar_no_se_habilita_por_registrar(): void
    {
        Livewire::test(CreateRole::class)->fillForm([
            'name' => 'Registro CCI restringido',
            'guard_name' => 'web',
            'cci_permissions_0' => [Cci::VIEW],
            'cci_permissions_1' => [],
            'cci_permissions_2' => [Cci::REGISTER],
        ])->call('create')->assertHasNoFormErrors();

        $role = Role::findByName('Registro CCI restringido', 'web');
        $this->assertTrue($role->hasAllPermissions([Cci::VIEW, Cci::REGISTER]));
        $this->assertFalse($role->hasPermissionTo(Cci::EDIT));
        $this->assertFalse($role->hasPermissionTo(Cci::DELETE));

        $user = User::factory()->create();
        $user->assignRole('Laboratorista');
        $this->actingAs($user)->get('/admin/shield/roles/'.$role->id.'/edit')->assertForbidden();
    }
}
