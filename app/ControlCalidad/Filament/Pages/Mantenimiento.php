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
use Filament\Notifications\Notification;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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
            'areas' => 'Áreas y equipos',
            'productos' => 'Productos de control',
            'lotes' => 'Lotes',
            'pruebas' => 'Pruebas y niveles',
        ];
    }

    public function descripcion(): string
    {
        return match ($this->seccion) {
            'productos' => 'Registra los productos de control que utiliza el laboratorio.',
            'lotes' => 'Identifica cada lote, su producto y su fecha de caducidad.',
            'pruebas' => 'Crea las pruebas del área y agrega sus niveles y valores de control.',
            default => 'Identifica las áreas de química y hematología y el equipo de cada una.',
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
            ->defaultSort('nombre')
            ->emptyStateHeading('Todavía no hay '.mb_strtolower($this->secciones()[$this->seccion]))
            ->emptyStateDescription(fn (): string => match (true) {
                $this->seccion === 'lotes' && ! Producto::query()->exists() => 'Primero registra un producto en Productos de control.',
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
                    ->authorize(fn (): bool => static::canAccess())
                    ->using(fn (array $data): Model => $this->guardarCatalogo($data))
                    ->successNotificationTitle('Registro guardado'),
            ])
            ->actions([
                EditAction::make('editar')
                    ->label('Editar')
                    ->modalHeading(fn (Model $record): string => 'Editar '.$record->nombre)
                    ->modalSubmitActionLabel('Guardar cambios')
                    ->form($this->campos())
                    ->modalWidth($this->seccion === 'pruebas' ? '6xl' : '2xl')
                    ->authorize(fn (): bool => static::canAccess())
                    ->mutateRecordDataUsing(function (array $data, Model $record): array {
                        if ($record instanceof Prueba) {
                            $data['niveles'] = $record->niveles()->with('valorControlActual')->orderBy('orden')->get()
                                ->map(fn ($nivel): array => [
                                    'id' => $nivel->id,
                                    'nombre' => $nivel->nombre,
                                    'lote_id' => $nivel->valorControlActual?->lote_id,
                                    'esperado' => $nivel->valorControlActual?->esperado,
                                    'minimo' => $nivel->valorControlActual?->minimo,
                                    'maximo' => $nivel->valorControlActual?->maximo,
                                ])->all();
                        }

                        return $data;
                    })
                    ->using(fn (Model $record, array $data): Model => $this->guardarCatalogo($data, $record))
                    ->successNotificationTitle('Cambios guardados'),
            ])
            ->paginated([10, 25, 50]);
    }

    private function consulta(): Builder
    {
        return match ($this->seccion) {
            'productos' => Producto::query(),
            'lotes' => Lote::query()->with('producto'),
            'pruebas' => Prueba::query()->with('area'),
            default => Area::query(),
        };
    }

    private function modelo(): string
    {
        return match ($this->seccion) {
            'productos' => Producto::class,
            'lotes' => Lote::class,
            'pruebas' => Prueba::class,
            default => Area::class,
        };
    }

    private function etiquetaCrear(): string
    {
        return match ($this->seccion) {
            'productos' => 'Nuevo producto',
            'lotes' => 'Nuevo lote',
            'pruebas' => 'Nueva prueba',
            default => 'Nueva área y equipo',
        };
    }

    private function columnas(): array
    {
        return match ($this->seccion) {
            'productos' => [
                TextColumn::make('nombre')->label('Producto de control')->searchable()->sortable(),
                TextColumn::make('lotes_count')->label('Lotes registrados')->counts('lotes')->badge(),
            ],
            'lotes' => [
                TextColumn::make('producto.nombre')->label('Producto')->searchable()->wrap(),
                TextColumn::make('nombre')->label('Nombre del lote')->searchable()->sortable(),
                TextColumn::make('numero')->label('Número de lote')->searchable(),
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
                TextInput::make('nombre')->label('Nombre del producto')->required()->maxLength(160)
                    ->unique(table: Producto::class, column: 'nombre', ignoreRecord: true),
            ],
            'lotes' => [
                Select::make('producto_id')->label('Producto de control')->required()->searchable()->native(false)
                    ->options(fn (): array => Producto::query()->orderBy('nombre')->pluck('nombre', 'id')->all())
                    ->exists(Producto::class, 'id'),
                TextInput::make('nombre')->label('Nombre del lote')->required()->maxLength(160),
                TextInput::make('numero')->label('Número de lote')->required()->maxLength(100)
                    ->rules(fn (Get $get, ?Model $record): array => [
                        Rule::unique(Lote::class, 'numero')->where('producto_id', $get('producto_id'))->ignore($record?->getKey()),
                    ]),
                DatePicker::make('caduca_el')->label('Caducidad del lote')->required()->displayFormat('d/m/Y'),
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
                        TextInput::make('nombre')->label('Nombre del nivel')->required()->maxLength(80),
                        Select::make('lote_id')->label('Producto y lote')->required()->searchable()->native(false)
                            ->options(fn (): array => Lote::query()->with('producto')->orderBy('nombre')->get()
                                ->mapWithKeys(fn (Lote $lote): array => [$lote->id => $lote->producto->nombre.' · '.$lote->nombre.' · '.$lote->numero])->all())
                            ->exists(Lote::class, 'id'),
                        Grid::make(3)->schema([
                            $this->campoValor('esperado', 'Valor esperado'),
                            $this->campoValor('minimo', 'Mínimo'),
                            $this->campoValor('maximo', 'Máximo'),
                        ]),
                    ])->columns(2)->minItems(1)->defaultItems(1)->reorderable()->collapsible()
                    ->itemLabel(fn (array $state): string => $state['nombre'] ?? 'Nuevo nivel')
                    ->addActionLabel('Agregar nivel'),
            ],
            default => [
                Select::make('nombre')->label('Área')->options(['Química' => 'Química', 'Hematología' => 'Hematología'])
                    ->required()->native(false),
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

    private function guardarCatalogo(array $data, ?Model $record = null): Model
    {
        $this->authorizeMaintenance();

        try {
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

    private function authorizeMaintenance(): void
    {
        abort_unless(static::canAccess(), 403);
    }
}
