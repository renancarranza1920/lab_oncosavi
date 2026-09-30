<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CotizacionResource\Pages;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class CotizacionResource extends Resource
{
    protected static ?string $model = null;

    protected static ?string $navigationGroup = 'Atención al Paciente';
    protected static ?int $navigationSort = 5;
    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    protected static ?string $navigationLabel = 'Cotizaciones';
    protected static ?string $modelLabel = 'Cotización';
    protected static ?string $pluralModelLabel = 'Cotizaciones';
    protected static ?string $slug = 'cotizaciones';
 public static function shouldRegisterNavigation(): bool
{
    return true; // si quieres que aparezca en el menú
}
public static function getPermissionPrefixes(): array
{
    return [
        'ver_pdf', // solo este permiso aparecerá
    ];
}


public static function isShieldable(): bool
{
    return false; // <- esto le dice a Shield que lo ignore
}
    public static function form(Form $form): Form
    {
        return $form->schema(components: []);
    }

    public static function table(Table $table): Table
    {
        return $table;
    }

    public static function getPages(): array
    {
        return [
            // Apuntamos a la ruta de creación como la página principal de este resource
             'index' => Pages\CreateCotizacion::route('/'),
        ];
    }
}