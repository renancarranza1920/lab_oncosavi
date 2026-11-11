<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Support\ControlCalidadPermissions;
use BezhanSalleh\FilamentShield\Resources\RoleResource as ShieldRoleResource;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;

class RoleResource extends ShieldRoleResource
{
    public static function getShieldFormComponents(): Component
    {
        $sections = [];
        foreach (ControlCalidadPermissions::groups() as $label => $options) {
            $sections[] = Section::make($label)->compact()->schema([
                static::getCheckboxListFormComponent(
                    name: 'cci_permissions_'.count($sections),
                    options: $options,
                    searchable: false,
                    columns: 2,
                ),
            ]);
        }

        return Tabs::make('Permissions')->contained()->tabs([
            static::getTabFormComponentForResources(),
            static::getTabFormComponentForPage(),
            static::getTabFormComponentForWidget(),
            static::getTabFormComponentForCustomPermissions(),
            Tab::make('control_calidad_interno')->label('Control de Calidad Interno')
                ->badge(count(ControlCalidadPermissions::all()))
                ->schema($sections),
        ])->columnSpan('full');
    }

    public static function getCustomPermissionOptions(): ?array
    {
        return array_diff_key(parent::getCustomPermissionOptions() ?? [], array_flip(ControlCalidadPermissions::all()));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'view' => Pages\ViewRole::route('/{record}'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
