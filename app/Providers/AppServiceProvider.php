<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use SelectorExamenes;
use Illuminate\Support\Facades\Gate; // <--- NO OLVIDES ESTA LÍNEA
use Spatie\Activitylog\Models\Activity; // <--- El modelo del paquete
use App\Policies\ActivityPolicy; // <--- Tu policy

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
        //
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
