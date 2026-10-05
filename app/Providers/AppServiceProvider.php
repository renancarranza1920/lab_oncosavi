<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use SelectorExamenes;
use Illuminate\Support\Facades\Gate; // <--- NO OLVIDES ESTA LÍNEA
use Spatie\Activitylog\Models\Activity; // <--- El modelo del paquete
use App\Policies\ActivityPolicy; // <--- Tu policy
use Filament\Tables\Table;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Las filas no navegan ni ejecutan acciones al hacer clic. Toda
        // interacción debe realizarse desde la columna de acciones.
        Table::configureUsing(
            fn (Table $table): Table => $table
                ->recordUrl(null)
                ->recordAction(null),
            isImportant: true,
        );

        Gate::policy(Activity::class, ActivityPolicy::class);
        \Spatie\Permission\Models\Role::saving(function ($role): void {
            if (!app()->runningInConsole()
                && ($role->name === 'super_admin' || $role->getOriginal('name') === 'super_admin')
                && !\App\Support\AccesoSoporte::autorizado(auth()->user())) {
                abort(403);
            }
        });
    }
}
