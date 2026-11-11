<?php

namespace App\Http\Middleware;

use App\Support\ControlCalidadPermissions as Permission;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class QaDemoAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user === null) {
            return redirect()->guest(route('filament.admin.auth.login'));
        }
        abort_unless($user->can('access_admin_panel') && $user->can(Permission::VIEW), 403);
        $permission = match ($request->route()->getName()) {
            'qa-demo.index' => Permission::VIEW,
            'qa-demo.configuration.index', 'qa-demo.configuration.legacy',
            'qa-demo.equipment.store', 'qa-demo.equipment.update', 'qa-demo.configuration.store',
            'qa-demo.products.store', 'qa-demo.lots.store', 'qa-demo.tests.store', 'qa-demo.tests.update',
            'qa-demo.events.index', 'qa-demo.events.store' => Permission::MAINTAIN,
            'qa-demo.runs.create', 'qa-demo.runs.store' => Permission::REGISTER,
            'qa-demo.runs.index' => Permission::HISTORY,
            'qa-demo.runs.show' => [Permission::HISTORY, Permission::EDIT, Permission::REVIEW],
            'qa-demo.runs.edit', 'qa-demo.runs.update' => Permission::EDIT,
            'qa-demo.runs.destroy' => Permission::DELETE,
            'qa-demo.runs.review' => Permission::REVIEW,
            'qa-demo.charts' => Permission::CHARTS,
            'qa-demo.export.csv', 'qa-demo.export.pdf' => Permission::EXPORT,
            default => null,
        };
        abort_unless($permission !== null && collect((array) $permission)->contains(fn (string $ability): bool => $user->can($ability)), 403);

        return $next($request);
    }
}
