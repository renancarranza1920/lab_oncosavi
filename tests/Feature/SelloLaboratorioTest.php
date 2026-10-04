<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\SelloLaboratorio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SelloLaboratorioTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_configura_sello_institucional_y_se_incluye_en_pdf(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('admin', 'web'));
        $this->actingAs($user);
        $this->assertNull(SelloLaboratorio::base64());
        $path = UploadedFile::fake()->image('sello.png')->store('sellos/laboratorio', 'public');
        SelloLaboratorio::guardar($path);
        $this->assertSame($path, SelloLaboratorio::path());
        $this->assertStringStartsWith('data:image/png;base64,', SelloLaboratorio::base64());
        SelloLaboratorio::guardar(null);
        $this->assertNull(SelloLaboratorio::base64());
    }

    public function test_perfil_guarda_sello_y_oculta_apartado_a_no_administradores(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $this->seed(\Database\Seeders\RolesPermisosSeeder::class);
        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));
        \Filament\Facades\Filament::bootCurrentPanel();
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        $path = UploadedFile::fake()->image('sello.png')->store('sellos/laboratorio', 'public');
        \Livewire\Livewire::test(\App\Filament\Auth\EditProfile::class)
            ->assertSee('Sello del laboratorio')
            ->fillForm(['sello_laboratorio' => [$path]])
            ->call('save')->assertHasNoFormErrors();
        $this->assertSame($path, SelloLaboratorio::path());
        $recepcion = User::factory()->create();
        $recepcion->assignRole('Recepcion');
        $this->actingAs($recepcion);
        \Livewire\Livewire::test(\App\Filament\Auth\EditProfile::class)
            ->assertDontSee('Sello del laboratorio');
    }

    public function test_usuario_sin_rol_admin_no_puede_cambiar_sello_institucional(): void
    {
        $this->actingAs(User::factory()->create());
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        SelloLaboratorio::guardar(null);
    }
}
