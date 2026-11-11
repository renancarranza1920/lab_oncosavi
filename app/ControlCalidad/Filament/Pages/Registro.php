<?php

namespace App\ControlCalidad\Filament\Pages;

use App\ControlCalidad\Filament\Concerns\InteractsWithControlRecords;
use App\ControlCalidad\Services\ControlCalidadService;
use App\Support\ControlCalidadPermissions as Permission;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class Registro extends PaginaControlCalidad implements HasTable
{
    use InteractsWithControlRecords;
    use InteractsWithTable;

    protected static ?string $slug = 'registro';
    protected static ?string $title = 'Registro de Control de Calidad Interno';
    protected static ?string $navigationLabel = 'Registro';
    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';
    protected static ?int $navigationSort = 3;
    protected static string $view = 'filament.control-calidad.registro';

    public ?array $data = [];

    public static function permission(): string
    {
        return Permission::REGISTER;
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
        $this->form->fill(['laboratorista_id' => auth()->id(), 'resultados' => []]);
    }

    public function form(Form $form): Form
    {
        return $form->schema($this->esquemaRegistro())->statePath('data');
    }

    public function guardar(): void
    {
        abort_unless(static::allowed(Permission::REGISTER), 403);
        $data = $this->form->getState();
        try {
            $registro = app(ControlCalidadService::class)->guardar($data, auth()->user());
        } catch (ValidationException $exception) {
            Notification::make()->danger()->title('Revisa los datos ingresados')
                ->body($exception->validator->errors()->first())->send();
            throw $exception;
        }
        $this->form->fill([
            'area_id' => $registro->area_id,
            'laboratorista_id' => auth()->id(),
            'resultados' => $this->filasDelArea($registro->area_id),
        ]);
        $this->resetTable();
        Notification::make()->title('Registro guardado')->success()->send();
    }

    public function mostrarRegistros(): bool
    {
        return static::allowed(Permission::HISTORY) || static::allowed(Permission::EDIT) || static::allowed(Permission::DELETE);
    }

    public function table(Table $table): Table
    {
        $table = $this->tablaRegistros($table);
        if (! $this->mostrarRegistros()) {
            $table->modifyQueryUsing(fn ($query) => $query->whereRaw('1 = 0'));
        }

        return $table;
    }
}
