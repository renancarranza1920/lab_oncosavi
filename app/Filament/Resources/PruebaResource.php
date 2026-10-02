<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PruebaResource\Pages;
use App\Filament\Resources\PruebaResource\Pages\ListPruebasConjuntas;
use App\Models\GrupoEtario;
use App\Models\Prueba;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class PruebaResource extends Resource
{
    protected static ?string $model = Prueba::class;
    protected static ?string $navigationGroup = 'Gestión de Laboratorio';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $pluralModelLabel = 'Pruebas';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Tipo de Creación')
                    ->tabs([
                        Tabs\Tab::make('Prueba Unitaria')
                            ->schema([
                                Forms\Components\Card::make()
                                    ->schema([
                                        Forms\Components\TextInput::make('nombre')
                                            ->label('Nombre de la Prueba')
                                            ->maxLength(255),

                                        Forms\Components\Select::make('examen_id')
                                            ->label('Examen al que Pertenece')
                                            ->relationship('examen', 'nombre')
                                            ->requiredWith('nombre')
                                            ->searchable()
                                            ->preload(),

                                        Forms\Components\Select::make('tipo_prueba_id')
                                            ->label('Tipo de Prueba')
                                            ->relationship('tipoPrueba', 'nombre')
                                            ->createOptionAction(fn ($action) => $action->visible(true))
                                            ->createOptionForm([
                                                Forms\Components\TextInput::make('nombre')
                                                    ->label('Nombre del Tipo de Prueba')
                                                    ->required()
                                                    ->maxLength(255),
                                            ])
                                            ->searchable()
                                            ->preload()
                                            ->helperText('Opcional.'),
                                    ]),

                                static::valoresReferenciaSection(useRelationship: false),
                            ]),

                        Tabs\Tab::make('Pruebas Conjuntas (Matriz)')
                            ->schema([
                                Forms\Components\Card::make()
                                    ->schema(function (Get $get): array {
                                        $staticComponents = [
                                            Forms\Components\Select::make('examen_id_conjunto')
                                                ->label('Examen para la Matriz')
                                                ->relationship('examen', 'nombre')
                                                ->requiredWith('filas,columnas')
                                                ->searchable()
                                                ->preload(),

                                            Grid::make(4)
                                                ->schema([
                                                    TextInput::make('filas')
                                                        ->label('Número de Filas')
                                                        ->numeric()
                                                        ->minValue(1)
                                                        ->nullable()
                                                        ->requiredWith('examen_id_conjunto,columnas')
                                                        ->live(onBlur: true),

                                                    TextInput::make('columnas')
                                                        ->label('Número de Columnas')
                                                        ->numeric()
                                                        ->minValue(1)
                                                        ->nullable()
                                                        ->requiredWith('examen_id_conjunto,filas')
                                                        ->live(onBlur: true),
                                                ]),
                                        ];

                                        $filas = (int) $get('filas', 0);
                                        $columnas = (int) $get('columnas', 0);
                                        $dynamicComponents = [];

                                        if ($filas > 0 && $columnas > 0) {
                                            $headerComponents = [
                                                Placeholder::make('top_left_corner')->label('')->content(new HtmlString('&nbsp;')),
                                            ];

                                            for ($c = 1; $c <= $columnas; $c++) {
                                                $headerComponents[] = TextInput::make("nombres_columnas.{$c}")
                                                    ->label("Columna {$c}")
                                                    ->placeholder("Nombre Columna {$c}");
                                            }

                                            $matrixComponents = [
                                                Grid::make($columnas + 1)->schema($headerComponents),
                                            ];

                                            for ($f = 1; $f <= $filas; $f++) {
                                                $matrixComponents[] = Grid::make($columnas + 1)
                                                    ->schema([
                                                        TextInput::make("nombres_filas.{$f}")
                                                            ->label("Fila {$f}")
                                                            ->placeholder("Nombre Fila {$f}")
                                                            ->columnSpan(1),
                                                    ]);
                                            }

                                            $dynamicComponents[] = Forms\Components\Card::make()
                                                ->schema($matrixComponents)
                                                ->columnSpanFull();
                                        }

                                        return array_merge($staticComponents, $dynamicComponents);
                                    }),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function valoresReferenciaSection(bool $useRelationship = true): Forms\Components\Section
    {
        $repeater = Forms\Components\Repeater::make('valoresReferencia')
            ->label('')
            ->itemLabel(function (array $state): ?string {
                $min = \App\Support\NumeroLaboratorio::formatear($state['valor_min'] ?? '?');
                $max = \App\Support\NumeroLaboratorio::formatear($state['valor_max'] ?? '?');
                $unidades = $state['unidades'] ?? '';
                $descriptivo = $state['descriptivo'] ?? null;

                return trim(($descriptivo ? "{$descriptivo} | " : '') . "{$min} - {$max} {$unidades}");
            })
            ->schema([
                Grid::make(2)
                    ->schema([
                        Forms\Components\Select::make('grupo_etario_id')
                            ->label('Grupo Etario')
                            ->options(fn () => GrupoEtario::orderBy('nombre')->pluck('nombre', 'id'))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('genero', 'Ambos')),

                        Forms\Components\Select::make('genero')
                            ->label('Género')
                            ->options(function (Get $get) {
                                $grupoId = $get('grupo_etario_id');
                                $base = ['Masculino' => 'Masculino', 'Femenino' => 'Femenino', 'Ambos' => 'Ambos'];

                                if ($grupoId) {
                                    $grupo = GrupoEtario::find($grupoId);

                                    if ($grupo && $grupo->genero !== 'Ambos') {
                                        return [$grupo->genero => $grupo->genero];
                                    }
                                }

                                return $base;
                            })
                            ->default('Ambos')
                            ->required(),
                    ]),

                Grid::make(4)
                    ->schema([
                        Forms\Components\Select::make('operador')
                            ->label('Operador')
                            ->options([
                                'rango' => 'Rango (Min - Max)',
                                '<=' => 'Menor o igual (<=)',
                                '>=' => 'Mayor o igual (>=)',
                                '<' => 'Menor que (<)',
                                '>' => 'Mayor que (>)',
                                '=' => 'Igual a (=)',
                            ])
                            ->default('rango')
                            ->required()
                            ->live(),

                        TextInput::make('valor_min')
                            ->numeric()
                            ->type('text')
                            ->mask(\Filament\Support\RawJs::make('$money($input, ".", ",", 2)'))
                            ->stripCharacters(',')
                            ->label('Mínimo')
                            ->visible(fn (Get $get) => in_array($get('operador'), ['rango', '>=', '>', '=']))
                            ->required(fn (Get $get) => in_array($get('operador'), ['rango', '>=', '>'])),

                        TextInput::make('valor_max')
                            ->numeric()
                            ->type('text')
                            ->mask(\Filament\Support\RawJs::make('$money($input, ".", ",", 2)'))
                            ->stripCharacters(',')
                            ->label('Máximo')
                            ->visible(fn (Get $get) => in_array($get('operador'), ['rango', '<=', '<']))
                            ->required(fn (Get $get) => in_array($get('operador'), ['rango', '<=', '<'])),

                        TextInput::make('unidades')
                            ->label('Unidades')
                            ->placeholder('mg/dL')
                            ->datalist(['mg/dL', 'g/dL', '%', 'U/L', 'mm/Hora', 'UI/mL', 'mg/L']),
                    ]),

                Grid::make(2)
                    ->schema([
                        TextInput::make('descriptivo')
                            ->label('Texto descriptivo')
                            ->placeholder('Ej: Negativo'),

                        TextInput::make('nota')
                            ->label('Nota interna'),
                    ]),
            ])
            ->columns(1)
            ->collapsible()
            ->collapsed(false)
            ->cloneable()
            ->defaultItems(0)
            ->deleteAction(fn ($action) => $action->requiresConfirmation());

        if ($useRelationship) {
            $repeater->relationship('valoresReferencia');
        }

        return Forms\Components\Section::make('Valores de Referencia')
            ->description('Administre los rangos que pertenecen a esta prueba.')
            ->headerActions([
                FormAction::make('copyFrom')
                    ->label('Copiar valores')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->form([
                        Forms\Components\Select::make('source_prueba_id')
                            ->label('Seleccionar Prueba de Origen')
                            ->helperText('Se copiarán todos los rangos de referencia de la prueba seleccionada.')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->options(function (?Prueba $record = null) {
                                return Prueba::query()
                                    ->with('examen')
                                    ->whereNull('tipo_conjunto')
                                    ->when($record?->getKey(), fn (Builder $query) => $query->whereKeyNot($record->getKey()))
                                    ->orderBy('nombre')
                                    ->limit(200)
                                    ->get()
                                    ->mapWithKeys(fn (Prueba $prueba) => [
                                        $prueba->id => "{$prueba->nombre} (" . ($prueba->examen->nombre ?? 'Sin examen') . ')',
                                    ]);
                            }),
                    ])
                    ->action(function (array $data, callable $set) {
                        $source = Prueba::with('valoresReferencia')->find($data['source_prueba_id']);

                        if (! $source) {
                            return;
                        }

                        $valuesToCopy = $source->valoresReferencia
                            ->map(function ($valor) {
                                $data = $valor->toArray();
                                $legacyKey = 'react' . 'ivo_id';
                                unset($data['id'], $data['prueba_id'], $data[$legacyKey], $data['created_at'], $data['updated_at']);

                                return $data;
                            })
                            ->toArray();

                        $set('valoresReferencia', $valuesToCopy);

                        Notification::make()
                            ->title('Valores importados correctamente')
                            ->success()
                            ->send();
                    }),
            ])
            ->schema([$repeater])
            ->columnSpanFull();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('tipoPrueba.nombre')
                    ->label('Tipo de Prueba')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tipo_conjunto')
                    ->label('Grupo Conjunto')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->colors([
                        'success' => 'activo',
                        'gray' => 'inactivo',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('gestionar_valores')
                    ->label('Valores de Referencia')
                    ->icon('heroicon-o-table-cells')
                    ->color('gray')
                    ->modalWidth('4xl')
                    ->modalSubmitActionLabel('Guardar valores')
                    ->modalSubmitAction(fn ($action) => auth()->user()->can('gestionar_valores_ref') ? $action : false)
                    ->fillForm(fn (Prueba $record) => [
                        'valoresReferencia' => $record->valoresReferencia()->get()->toArray(),
                    ])
                    ->form([static::valoresReferenciaSection(useRelationship: false)->disabled(fn () => !auth()->user()->can('gestionar_valores_ref'))])
                    ->action(function (Prueba $record, array $data): void {
                        abort_unless(auth()->user()->can('gestionar_valores_ref'), 403);
                        DB::transaction(function () use ($record, $data) {
                            $record->valoresReferencia()->delete();

                            foreach ($data['valoresReferencia'] ?? [] as $valor) {
                                $record->valoresReferencia()->create(Arr::only($valor, [
                                    'grupo_etario_id',
                                    'genero',
                                    'operador',
                                    'valor_min',
                                    'valor_max',
                                    'unidades',
                                    'descriptivo',
                                    'nota',
                                ]));
                            }
                        });

                        Notification::make()
                            ->title('Valores de referencia actualizados')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('cambiar_estado')
                    ->label(fn (Prueba $record) => $record->estado === 'activo' ? 'Dar de baja' : 'Dar de alta')
                    ->icon(fn (Prueba $record) => $record->estado === 'activo' ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (Prueba $record) => $record->estado === 'activo' ? 'warning' : 'success')
                    ->tooltip(fn (Prueba $record) => $record->estado === 'activo' ? 'Desactivar Prueba' : 'Activar Prueba')
                    ->visible(fn () => auth()->user()->can('cambiar_estado_pruebas'))
                    ->requiresConfirmation()
                    ->modalHeading(fn (Prueba $record) => $record->estado === 'activo' ? 'Desactivar Prueba?' : 'Activar Prueba?')
                    ->modalDescription('¿Estás seguro de que deseas cambiar el estado de este registro?')
                    ->action(function (Prueba $record) {
                        $record->estado = $record->estado === 'activo' ? 'inactivo' : 'activo';
                        $record->save();

                        Notification::make()
                            ->title($record->estado === 'activo' ? 'Prueba activada' : 'Prueba desactivada')
                            ->success()
                            ->send();
                    })
                    ->iconButton(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('pruebas_conjuntas')
                    ->label('Ver Pruebas en Matriz')
                    ->visible(fn () => auth()->user()->can('ver_pruebas_conjuntas'))
                    ->icon('heroicon-o-table-cells')
                    ->color('gray')
                    ->url(ListPruebasConjuntas::getUrl()),

                Tables\Actions\Action::make('gestionar_tipos')
                    ->label('Tipos de Prueba')
                    ->visible(fn () => auth()->user()->can('view_any_tipo::prueba'))
                    ->url(TipoPruebaResource::getUrl('index'))
                    ->color('gray'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPruebas::route('/'),
            'create' => Pages\CreatePrueba::route('/create'),
            'edit' => Pages\EditPrueba::route('/{record}/edit'),
            'matrices' => ListPruebasConjuntas::route('/matrices'),
            'edit-conjunta' => Pages\EditPruebaConjunta::route('/{record}/edit-conjunta'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereNull('tipo_conjunto');
    }
}
