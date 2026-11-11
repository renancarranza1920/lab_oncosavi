<?php

namespace App\ControlCalidad\Filament\Pages;

use App\Support\ControlCalidadPermissions;
use Filament\Pages\Page;

abstract class PaginaControlCalidad extends Page
{
    abstract public static function permission(): string;

    public static function allowed(string $permission): bool
    {
        $user = auth('web')->user();

        return $user !== null
            && $user->can('access_admin_panel')
            && $user->can(ControlCalidadPermissions::VIEW)
            && $user->can($permission);
    }

    public static function canAccess(): bool
    {
        return static::allowed(static::permission());
    }

    public function hydrate(): void
    {
        abort_unless(static::canAccess(), 403);
    }
}
