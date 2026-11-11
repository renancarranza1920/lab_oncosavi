<?php

namespace App\ControlCalidad\Filament\Pages;

use App\ControlCalidad\Filament\Concerns\InteractsWithControlRecords;
use App\Support\ControlCalidadPermissions as Permission;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class Historial extends PaginaControlCalidad implements HasTable
{
    use InteractsWithControlRecords;
    use InteractsWithTable;

    protected static ?string $slug = 'historial';
    protected static ?string $title = 'Historial';
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?int $navigationSort = 5;
    protected static string $view = 'filament.control-calidad.historial';

    public static function permission(): string
    {
        return Permission::HISTORY;
    }

    public function table(Table $table): Table
    {
        return $this->tablaRegistros($table, true);
    }
}
