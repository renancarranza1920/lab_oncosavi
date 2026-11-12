<?php

namespace App\ControlCalidad\Filament\Pages;

use App\ControlCalidad\Models\Area;
use App\ControlCalidad\Models\Lote;
use App\ControlCalidad\Models\Producto;
use App\ControlCalidad\Models\Prueba;
use App\ControlCalidad\Services\ControlCalidadService;
use App\Support\ControlCalidadPermissions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class Mantenimiento extends PaginaControlCalidad implements HasTable
{
    use InteractsWithTable;

    protected static ?string $slug = 'mantenimiento';

    protected static ?string $title = 'Mantenimiento';

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.control-calidad.mantenimiento';

    public string $seccion = 'areas';

    public static function permission(): string
    {
        return ControlCalidadPermissions::MAINTAIN;
    }

    public function cambiarSeccion(string $seccion): void
    {
        $this->authorizeMaintenance();
        abort_unless(array_key_exists($seccion, $this->secciones()), 404);
        $this->seccion = $seccion;
        $this->resetTable();
    }

    public function updatedSeccion(): void
    {
        $this->authorizeMaintenance();
        abort_unless(array_key_exists($this->seccion, $this->secciones()), 404);
        $this->resetTable();
    }

    public function secciones(): array
    {
        return [
            'areas' => 'Áreas',
            'productos' => 'Productos',
            'pruebas' => 'Pruebas',
        ];
    }

    public function descripcion(): string
    {
        return match ($this->seccion) {
            'productos' => 'Registra el nombre del producto de control, su lote y la fecha de caducidad.',
            'pruebas' => 'Crea las pruebas del área y agrega sus niveles y valores de control.',
            default => 'Agrega las áreas del laboratorio y el nombre del equipo de cada una.',
        };
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->consulta())
            ->columns($this->columnas())
            ->recordTitleAttribute('nombre')
            ->recordUrl(null)
            ->recordAction(null)
            ->defaultSort($this->seccion === 'productos' ? 'numero' : 'nombre')
            ->emptyStateHeading('Todavía no hay '.mb_strtolower($this->secciones()[$this->seccion]))
            ->emptyStateDescription(fn (): string => match (true) {
                ! $this->puedeAccion('create') => 'No hay registros para mostrar.',
                $this->seccion === 'pruebas' && ! Area::query()->exists() => 'Primero registra un área y equipo.',
                $this->seccion === 'pruebas' && ! Lote::query()->exists() => 'Registra el producto y su lote para configurar los niveles de la prueba.',
                default => 'Usa el botón de agregar para registrar el primero.',
            })
            ->headerActions([
                CreateAction::make('crear')
                    ->label($this->etiquetaCrear())
                    ->modalHeading($this->etiquetaCrear())
                    ->modalSubmitActionLabel('Guardar')
                    ->model($this->modelo())
                    ->form($this->campos())
                    ->modalWidth($this->seccion === 'pruebas' ? '6xl' : '2xl')
                    ->createAnother(false)
                    ->authorize(fn (): bool => $this->puedeAccion('create'))
                    ->using(fn (array $data): Model => $this->guardarCatalogo($data))
                    ->successNotificationTitle('Registro guardado'),
            ])
            ->actions([
                EditAction::make('editar')
                    ->label('Editar')
                    ->modalHeading(fn (Model $record): string => 'Editar '.($record instanceof Lote ? $record->producto->nombre : $record->nombre))
                    ->modalSubmitActionLabel('Guardar cambios')
                    ->form($this->campos())
                    ->modalWidth($this->seccion === 'pruebas' ? '6xl' : '2xl')
                    ->authorize(fn (): bool => $this->puedeAccion('edit'))
                    ->mutateRecordDataUsing(function (array $data, Model $record): array {
                        if ($record instanceof Lote) {
                            $data['producto_nombre'] = $record->producto->nombre;
                        } elseif ($record instanceof Prueba) {
                            $data['niveles'] = $record->niveles()->with('valorControlActual')->orderBy('orden')->get()
                                ->map(fn ($nivel): array => [
                                    'id' => $nivel->id,
                                    'nombre' => $nivel->nombre,
                                    'lote_id' => $nivel->valorControlActual?->lote_id,
                                    'esperado' => ControlCalidadService::puntoMedio($nivel->valorControlActual?->minimo, $nivel->valorControlActual?->maximo),
                                    'minimo' => $nivel->valorControlActual?->minimo,
                                    'maximo' => $nivel->valorControlActual?->maximo,
                                ])->all();
                        }

                        return $data;
                    })
                    ->using(fn (Model $record, array $data): Model => $this->guardarCatalogo($data, $record))
                    ->successNotificationTitle('Cambios guardados'),
                DeleteAction::make('eliminar')
                    ->label('Eliminar')
                    ->modalHeading(fn (Model $record): string => $record instanceof Lote
                        ? 'Eliminar '.$record->producto->nombre.' · lote '.$record->numero : 'Eliminar '.$record->nombre)
                    ->requiresConfirmation()
                    ->modalDescription('Solo se puede eliminar si no está vinculado a otras configuraciones o a mediciones guardadas.')
                    ->authorize(fn (): bool => $this->puedeAccion('delete'))
                    ->using(function (Model $record, DeleteAction $action): bool {
                        $this->authorizeMaintenance('delete');
                        try {
                            app(ControlCalidadService::class)->eliminarCatalogo($record, auth()->user());
                        } catch (ValidationException $exception) {
                            Notification::make()->danger()->title('No se puede eliminar')
                                ->body($exception->validator->errors()->first())->send();
                            $action->halt();
                        }

                        return true;
                    })
                    ->successNotificationTitle('Registro eliminado'),
            ])
            ->paginated([10, 25, 50]);
    }

    private function consulta(): Builder
    {
        return match ($this->seccion) {
            'productos' => Lote::query()->with('producto'),
            'pruebas' => Prueba::query()->with('area'),
            default => Area::query(),
        };
    }

    private function modelo(): string
    {
        return match ($this->seccion) {
            'productos' => Lote::class,
            'pruebas' => Prueba::class,
            default => Area::class,
        };
    }

    private function etiquetaCrear(): string
    {
        return match ($this->seccion) {
            'productos' => 'Nuevo producto',
            'pruebas' => 'Nueva prueba',
            default => 'Nueva área',
        };
    }

    private function columnas(): array
    {
        return match ($this->seccion) {
            'productos' => [
                TextColumn::make('producto.nombre')->label('Producto de control')->searchable()->sortable()->wrap(),
                TextColumn::make('numero')->label('Número de lote')->searchable()->sortable(),
                TextColumn::make('caduca_el')->label('Caducidad')->date('d/m/Y')->sortable(),
            ],
            'pruebas' => [
                TextColumn::make('area.nombre')->label('Área')->searchable(),
                TextColumn::make('area.equipo')->label('Equipo')->searchable()->wrap(),
                TextColumn::make('nombre')->label('Prueba')->searchable()->sortable()->wrap(),
                TextColumn::make('unidad')->label('Unidad'),
                TextColumn::make('niveles_count')->label('Niveles')->counts('niveles')->badge(),
            ],
            default => [
                TextColumn::make('nombre')->label('Área')->searchable()->sortable(),
                TextColumn::make('equipo')->label('Nombre del equipo')->searchable()->wrap(),
            ],
        };
    }

    private function campos(): array
    {
        return match ($this->seccion) {
            'productos' => [
                TextInput::make('producto_nombre')->label('Nombre del producto de control')->required()->maxLength(160),
                TextInput::make('numero')->label('Número de lote')->required()->maxLength(100)
                    ->rules(function (Get $get, ?Model $record): array {
                        $nombre = trim((string) $get('producto_nombre'));
                        $productoId = Producto::query()->whereRaw('LOWER(nombre) = ?', [mb_strtolower($nombre)])->value('id');

                        return $productoId === null ? [] : [
                            Rule::unique(Lote::class, 'numero')->where('producto_id', $productoId)->ignore($record?->getKey()),
                        ];
                    }),
                DatePicker::make('caduca_el')->label('Fecha de caducidad del lote')->required()->displayFormat('d/m/Y'),
            ],
            'pruebas' => [
                Grid::make(2)->schema([
                    Select::make('area_id')->label('Área y equipo')->required()->searchable()->native(false)
                        ->options(fn (): array => Area::query()->orderBy('nombre')->get()
                            ->mapWithKeys(fn (Area $area): array => [$area->id => $area->nombre.' · '.$area->equipo])->all())
                        ->exists(Area::class, 'id'),
                    TextInput::make('nombre')->label('Nombre de la prueba')->required()->maxLength(160),
                    TextInput::make('unidad')->label('Unidad')->required()->maxLength(40),
                ]),
                Repeater::make('niveles')->label('Niveles y valores de control')
                    ->schema([
                        Hidden::make('id'),
                        TextInput::make('nombre')->label('Nombre del nivel')->required()->maxLength(80)
                            ->datalist(['Bajo', 'Medio', 'Alto']),
                        Select::make('lote_id')->label('Producto y lote')->required()->searchable()->native(false)
                            ->options(fn (): array => Lote::query()->with('producto')->orderBy('nombre')->get()
                                ->mapWithKeys(fn (Lote $lote): array => [$lote->id => $lote->producto->nombre.' · '.$lote->numero])->all())
                            ->exists(Lote::class, 'id'),
                        Grid::make(3)->schema([
                            TextInput::make('esperado')->label('Valor esperado')->readOnly()->dehydrated(false)
                                ->afterStateHydrated(fn (Get $get, Set $set) => $set('esperado', $this->puntoMedioVisible($get)))
                                ->formatStateUsing(fn (Get $get): ?string => $this->puntoMedioVisible($get)),
                            $this->campoValor('minimo', 'Mínimo')->live(onBlur: true)
                                ->afterStateUpdated(fn (Get $get, Set $set) => $set('esperado', $this->puntoMedioVisible($get))),
                            $this->campoValor('maximo', 'Máximo')->live(onBlur: true)
                                ->afterStateUpdated(fn (Get $get, Set $set) => $set('esperado', $this->puntoMedioVisible($get))),
                        ]),
                    ])->columns(2)->minItems(1)->defaultItems(1)->reorderable()->collapsible()
                    ->itemLabel(fn (array $state): string => $state['nombre'] ?? 'Nuevo nivel')
                    ->addActionLabel('Agregar nivel'),
            ],
            default => [
                TextInput::make('nombre')->label('Nombre del área')->required()->maxLength(80),
                TextInput::make('equipo')->label('Nombre del equipo')->required()->maxLength(160)
                    ->rules(fn (Get $get, ?Model $record): array => [
                        Rule::unique(Area::class, 'equipo')->where('nombre', $get('nombre'))->ignore($record?->getKey()),
                    ]),
            ],
        };
    }

    private function campoValor(string $nombre, string $etiqueta): TextInput
    {
        return TextInput::make($nombre)->label($etiqueta)->required()->inputMode('decimal')->maxLength(40)
            ->regex('/^[+-]?\d+(?:[.,]\d+)?$/D');
    }

    private function puntoMedioVisible(Get $get): ?string
    {
        $value = ControlCalidadService::puntoMedio($get('minimo'), $get('maximo'));

        return $value === null ? null : str_replace('.', ',', rtrim(rtrim($value, '0'), '.'));
    }

    private function guardarCatalogo(array $data, ?Model $record = null): Model
    {
        $this->authorizeMaintenance($record === null ? 'create' : 'edit');

        try {
            if ($this->seccion === 'productos') {
                return $this->guardarProductoYLote($data, $record);
            }
            if ($this->seccion === 'pruebas') {
                return app(ControlCalidadService::class)->guardarPrueba($data, auth()->user(), $record);
            }

            $record ??= new ($this->modelo());
            $record->fill($data);
            $record->save();

            return $record;
        } catch (ValidationException $exception) {
            Notification::make()->danger()->title('Revisa los valores ingresados')
                ->body($exception->validator->errors()->first())->send();

            throw $exception;
        }
    }

    private function guardarProductoYLote(array $data, ?Model $record): Lote
    {
        foreach (['producto_nombre', 'numero'] as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = trim($data[$field]);
            }
        }
        $valid = Validator::make($data, [
            'producto_nombre' => ['required', 'string', 'max:160'],
            'numero' => ['required', 'string', 'max:100'],
            'caduca_el' => ['required', 'date_format:Y-m-d'],
        ])->validate();

        return DB::transaction(function () use ($valid, $record): Lote {
            $producto = Producto::query()->whereRaw('LOWER(nombre) = ?', [mb_strtolower($valid['producto_nombre'])])->first()
                ?? Producto::firstOrCreate(['nombre' => $valid['producto_nombre']]);
            Validator::make($valid, [
                'numero' => [Rule::unique(Lote::class, 'numero')->where('producto_id', $producto->id)->ignore($record?->getKey())],
            ])->validate();

            $lote = $record ?? new Lote;
            abort_unless($lote instanceof Lote, 404);
            $lote->fill([
                'producto_id' => $producto->id,
                'nombre' => $producto->nombre,
                'numero' => $valid['numero'],
                'caduca_el' => $valid['caduca_el'],
            ])->save();

            return $lote;
        });
    }

    public function puedeAccion(string $action): bool
    {
        return static::canAccess() && static::allowed(ControlCalidadPermissions::maintenancePermission($this->seccion, $action));
    }

    private function authorizeMaintenance(?string $action = null): void
    {
        abort_unless($action === null ? static::canAccess() : $this->puedeAccion($action), 403);
    }
}
