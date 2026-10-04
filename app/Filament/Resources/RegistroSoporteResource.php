<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RegistroSoporteResource\Pages\ListRegistrosSoporte;
use App\Models\RegistroSoporte;
use App\Support\Bitacora;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class RegistroSoporteResource extends Resource
{
    protected static ?string $model = RegistroSoporte::class;
    protected static ?string $slug = 'bitacora-soporte';
    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';
    protected static ?string $navigationGroup = 'Administración';
    protected static ?string $modelLabel = 'Registro de soporte';
    protected static ?string $pluralModelLabel = 'Bitácora de soporte';

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user && $user->can('view_any_activity::log')
            && ($user->hasRole('super_admin') || $user->getDirectPermissions()->contains('name', 'ver_bitacora_soporte'));
    }

    public static function canView(Model $record): bool { return static::canViewAny(); }
    public static function canCreate(): bool { return false; }
    public static function canEdit(Model $record): bool { return false; }
    public static function canDelete(Model $record): bool { return false; }
    public static function canDeleteAny(): bool { return false; }

    public static function getEloquentQuery(): Builder
    {
        abort_unless(static::canViewAny(), 403);
        return parent::getEloquentQuery();
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('registrado_at')->label('Fecha y hora')->dateTime('d/m/Y H:i:s')->timezone('America/El_Salvador')->sortable(),
            Tables\Columns\TextColumn::make('nombre_usuario')->label('Soporte')->searchable(),
            Tables\Columns\TextColumn::make('datos.log_name')->label('Módulo'),
            Tables\Columns\TextColumn::make('datos.description')->label('Detalle')->limit(70),
        ])->defaultSort('registrado_at', 'desc')->actions([
            Tables\Actions\ViewAction::make()->modalHeading('Detalle reservado de soporte')
                ->infolist(fn (Infolist $infolist) => $infolist->schema([
                    TextEntry::make('registrado_at')->label('Fecha')->dateTime('d/m/Y H:i:s')->timezone('America/El_Salvador'),
                    TextEntry::make('nombre_usuario')->label('Usuario de soporte'),
                    TextEntry::make('datos.description')->label('Acción original'),
                    TextEntry::make('datos.event')->label('Tipo de acción'),
                    TextEntry::make('datos.subject_type')->label('Modelo'),
                    TextEntry::make('datos.subject_id')->label('Registro'),
                    KeyValueEntry::make('anteriores')->label('Valores anteriores')
                        ->state(fn (RegistroSoporte $record) => Bitacora::datosParaMostrar($record->datos['properties']['old'] ?? [])),
                    KeyValueEntry::make('nuevos')->label('Valores nuevos')
                        ->state(fn (RegistroSoporte $record) => Bitacora::datosParaMostrar($record->datos['properties']['attributes'] ?? [])),
                    KeyValueEntry::make('otros')->label('Otros datos del evento')
                        ->state(fn (RegistroSoporte $record) => Bitacora::datosParaMostrar(array_diff_key($record->datos['properties'] ?? [], ['old' => true, 'attributes' => true]))),
                ])),
        ])->bulkActions([]);
    }

    public static function getPages(): array
    {
        return ['index' => ListRegistrosSoporte::route('/')];
    }
}
