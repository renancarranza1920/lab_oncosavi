<?php

namespace App\Http\Middleware;

use App\Support\ControlCalidadPermissions;
use Filament\Http\Middleware\Authenticate;

class AuthenticateControlCalidad extends Authenticate
{
    protected function authenticate($request, array $guards): void
    {
        parent::authenticate($request, $guards);

        abort_unless($request->user('web')?->can(ControlCalidadPermissions::VIEW), 403);
    }

    protected function redirectTo($request): ?string
    {
        return route('filament.admin.auth.login');
    }
}
