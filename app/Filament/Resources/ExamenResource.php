<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExamenResource\Pages;
use App\Filament\Resources\ExamenResource\RelationManagers\PruebasRelationManager;
use App\Models\Examen;
use App\Models\TipoExamen;
use Illuminate\Support\Facades\DB;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\HtmlString;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Split;

class ExamenResource extends Resource
{
    protected static ?string $model = Examen::class;

    protected static ?string $navigationGroup = 'Gestión de Laboratorio';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document';
    protected static ?string $navigationLabel = 'Exámenes';
    protected static ?string $pluralModelLabel = 'Exámenes';
    protected static ?string $modelLabel = 'Examen';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Section::make('Información ')
                            ->schema([
                                Forms\Components\Select::make('tipo_examen_id')
                                    ->label('Tipo de Examen')
                                    ->relationship('tipoExamen', 'nombre')
                                    ->options(function () {
                                        return TipoExamen::where('estado', 1)->pluck('nombre', 'id');
                                    })
                                    ->required()
                                    ->validationMessages([
                                        'required' => 'Por favor, selecciona un tipo de examen.',
                                    ])
                                    ->searchable()
                                    ->preload(),

                                Forms\Components\TextInput::make('nombre')
                                    ->label('Nombre del Examen')
                                    ->placeholder('Ej: Glucosa, Creatinina...')
                                    ->required()
                                    ->validationMessages([
                                        'required' => 'El nombre del examen es obligatorio.',
                                    ])
                                    ->reactive()
                                    ->maxLength(255),

                                Forms\Components\Select::make('recipiente')
                                    ->label('Recipiente')
                                    ->options([
                                        'quimica_sanguinea' => 'Quimica Sanginea',
                                        'cuagulacion' => 'Cuagulacion',
                                        'hematologia' => 'Hematologia',
                                        'coprologia' => 'Coprologia',
                                        'uroanalisis' => 'Uroanalisis',
                                        'cultivo_secreciones' => 'Cultivo Secreciones',
                                    ])
                                    ->required()
                                    ->validationMessages([
                                        'required' => 'Por favor, selecciona un recipiente.',
                                    ])
                                    ->searchable(),

                                Forms\Components\Select::make('muestras')
                                    ->relationship('muestras', 'nombre')
                                    ->multiple()
                                    ->preload()
                                    ->searchable()
                                    ->required()
                                   ->validationMessages([ 
                                   'required' => 'Por favor, selecciona al menos una muestra.',
                                    ])
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('nombre')
                                            ->required()
                                            ->validationMessages([
                                                'required' => 'El nombre de la muestra es obligatorio.',
                                            ])
                                            ->unique('muestras', 'nombre'),
                                    ]),

                                Forms\Components\Toggle::make('es_externo')
                                    ->label('Es un examen externo/referido')
                                    ->helperText('Activa esto si el examen se procesa en otro laboratorio.'),

                                Forms\Components\TextInput::make('precio')
                                    ->label('Precio')
                                    ->prefix('$')
                                    ->numeric()
                                    ->validationMessages([
                                        'numeric' => 'El precio debe ser un número válido.',
                                        'required' => 'El precio es obligatorio.',
                                    ])
                                    ->required(),

                                Forms\Components\Toggle::make('estado')
                                    ->label('Activo')
                                    ->required()
                                    ->validationMessages([
                                        'required' => 'El estado del examen es obligatorio.',
                                    ])
                                    ->default(true)
                                    ->inline(false),
                            ])->columns(2),
                    ])
            ]);
    }
    
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Detalles del Examen')
                    ->schema([
                        Split::make([
                            // Columna 1: Información Principal
                            Grid::make(1)->schema([
                                TextEntry::make('nombre')
                                    ->label('Nombre del Examen')
                                    ->weight('bold')
                                    ->size(TextEntry\TextEntrySize::Large),
    
                                TextEntry::make('tipoExamen.nombre')
                                    ->label('Categoría / Tipo')
                                    ->icon('heroicon-m-tag'),
    
                                TextEntry::make('precio')
                                    ->label('Precio al Público')
                                    ->money('USD')
                                    ->color('success')
                                    ->weight('bold'),
                            ]),
    
                            // Columna 2: Detalles Técnicos
                            Grid::make(1)->schema([
                                TextEntry::make('recipiente')
                                    ->label('Recipiente')
                                    ->icon('heroicon-m-beaker')
                                    ->formatStateUsing(fn (string $state) => ucwords(str_replace('_', ' ', $state))),
    
                                IconEntry::make('es_externo')
                                    ->label('¿Es Referido/Externo?')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-paper-airplane') // Avión si se envía fuera
                                    ->falseIcon('heroicon-o-home')          // Casa si es local
                                    ->trueColor('info')
                                    ->falseColor('gray'),
    
                                TextEntry::make('estado')
                                    ->label('Estado Actual')
                                    ->badge()
                                    ->formatStateUsing(fn (bool $state) => $state ? 'Activo' : 'Inactivo')
                                    ->color(fn (bool $state) => $state ? 'success' : 'danger'),
                            ]),
                        ])->from('md'),
                    ]),
    
                Section::make('Requerimientos')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('muestras.nombre')
                                ->label('Muestras Necesarias')
                                ->badge()
                                ->color('warning')
                                ->listWithLineBreaks()
                                ->placeholder('No se especificaron muestras'),
    
                            TextEntry::make('pruebas.nombre')
                                ->label('Pruebas que incluye')
                                ->bulleted()
                                ->listWithLineBreaks()
                                ->visible(fn ($record) => !$record->es_externo) // Solo mostrar si es interno
                                ->placeholder('Sin pruebas individuales configuradas'),
                        ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->columns([
                Tables\Columns\TextColumn::make('tipoExamen.nombre')
                    ->label('Tipo de Examen')
                    ->sortable()
                    ->searchable(),
    
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Nombre')
                    ->sortable()
                    ->searchable()
                    ->description(function (Examen $record) {
                        if (!$record->es_externo && $record->pruebas()->count() === 0) {
                            return '⚠️ Sin pruebas asignadas';
                        }
                        return null;
                    })
                    ->color(function (Examen $record) {
                        if (!$record->es_externo && $record->pruebas()->count() === 0) {
                            return 'warning';
                        }
                        return null;
                    }),
    
                Tables\Columns\TextColumn::make('muestras.nombre')
                    ->label('Muestras')
                    ->badge()
                    ->searchable(),
    
                Tables\Columns\TextColumn::make('precio')
                    ->label('Precio')
                    ->money('USD'),
    
                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->formatStateUsing(fn($state) => $state ? '✅ Activo' : '❌ Inactivo')
                    ->badge()
                    ->color(fn($state) => $state ? 'success' : 'danger'),
    
                Tables\Columns\BooleanColumn::make('es_externo')
                    ->label('Origen')
                    ->trueIcon('heroicon-o-paper-airplane')
                    ->falseIcon('heroicon-o-home')
                    ->color(fn($state) => $state ? 'info' : 'success')
                    ->sortable(),
    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
    
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                Tables\Actions\Action::make('gestionar_muestras')
                    ->label('Catálogo de Muestras')
                    ->url(fn() => \App\Filament\Resources\MuestraResource::getUrl('index')) // Asegúrate de la ruta
                    ->color('gray'),
            ])
            ->filters([
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        '1' => 'Activos',
                        '0' => 'Inactivos',
                    ])
                    ->attribute('estado')
                    ->default(null),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
    
                // --- NUEVA ACCIÓN DE VER (Usa infolist) ---
                Tables\Actions\ViewAction::make()
                    ->label('Ver')
                    ->color('gray')
                    ->icon('heroicon-s-eye')
                    ->modalHeading('Detalle del Examen')
                    ->modalWidth('lg')
                    ->url(null)
                    ->visible(fn() => auth()->user()->can('ver_detalle_examenes')),
    
                Action::make('addPruebas')
                    ->label('Añadir Pruebas')
                    ->icon('heroicon-o-plus-circle')
                    ->visible(fn(Examen $record) => $record->es_externo === false && auth()->user()->can('agregar_pruebas_examenes'))
                    ->color('gray')
                    ->modalHeading(fn(Examen $record) => 'Añadir pruebas a: ' . $record->nombre)
                    ->form([
                        Forms\Components\TagsInput::make('nombres_pruebas')
                            ->label('Nombres de las Pruebas')
                            ->helperText('Escribe un nombre y presiona Enter para añadirlo a la lista.')
                            ->placeholder('Nueva prueba...')
                            ->required()
                            ->validationMessages([
                                'required' => 'Por favor, ingresa al menos una prueba.',
                            ]),
                    ])
                    ->action(function (Examen $record, array $data) {
                        $nombres = $data['nombres_pruebas'];
                        try {
                            DB::transaction(function () use ($record, $nombres) {
                                foreach ($nombres as $nombre) {
                                    $record->pruebas()->create([
                                        'nombre' => $nombre,
                                    ]);
                                }
                            });
                            Notification::make()->title(count($nombres) . ' pruebas creadas')->success()->send();
                        } catch (\Throwable $e) {
                            Notification::make()->title('Error')->body($e->getMessage())->danger()->send();
                        }
                    }),
    
                // --- ACCIÓN CAMBIAR ESTADO (MANTENIDA IDÉNTICA) ---
                Action::make('cambiar_estado')
                    ->label(fn($record) => $record->estado ? 'Dar de baja' : 'Dar de alta')
                    ->icon(fn($record) => $record->estado ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn($record) => $record->estado ? 'danger' : 'success')
                    ->visible(fn(Examen $record) =>    auth()->user()->can('cambiar_estado_examenes'))
                    ->tooltip(fn($record) => $record->estado ? 'Dar de baja' : 'Dar de alta')
                    ->requiresConfirmation()
                    ->modalHeading(fn($record) => $record->estado ? '¿Desactivar Examen?' : '¿Activar Examen?')
                    ->modalDescription(function (Examen $record) {
                        if (!$record->estado) {
                            return '¿Activar este examen nuevamente?';
                        }
    
                        $perfiles = $record->perfiles;
    
                        if ($perfiles->isNotEmpty()) {
                            $listaHtml = '<ul class="list-disc list-inside text-sm text-gray-500 dark:text-gray-400 text-left ml-4">';
                            $perfilesEnRiesgo = [];
    
                            foreach ($perfiles as $perfil) {
                                $listaHtml .= '<li>' . e($perfil->nombre) . '</li>';
                                if ($perfil->estado == 1) {
                                    $activosRestantes = $perfil->examenes()
                                        ->where('estado', 1)
                                        ->where($record->getTable() . '.id', '!=', $record->id)
                                        ->count();
    
                                    if ($activosRestantes < 2) {
                                        $perfilesEnRiesgo[] = $perfil->nombre;
                                    }
                                }
                            }
                            $listaHtml .= '</ul>';
    
                            $alertaRiesgo = '';
                            if (!empty($perfilesEnRiesgo)) {
                                $afectados = implode(', ', $perfilesEnRiesgo);
                                $alertaRiesgo =
                                    "<div class='mt-3 text-sm text-red-600 dark:text-red-400 font-semibold text-center'>" .
                                    "⚠️ Nota: Se desactivarán automáticamente los perfiles:<br>" .
                                    "<span class='text-base'>{$afectados}</span><br>" .
                                    "debido a que quedarán con menos de 2 exámenes activos." .
                                    "</div>";
                            }
    
                            return new HtmlString(
                                "<div class='space-y-3 text-center'>" .
                                "<p>Este examen pertenece a estos perfiles:</p>" .
                                $listaHtml .
                                "<p>¿Deseas darlo de baja?</p>" .
                                $alertaRiesgo .
                                "</div>"
                            );
                        }
                        return '¿Deseas dar de baja este examen?';
                    })
                    ->action(function ($record) {
                        $nuevoEstado = !$record->estado;
                        $mensajePerfiles = '';
                        $tipoNotificacion = 'success';
    
                        DB::transaction(function () use ($record, $nuevoEstado, &$mensajePerfiles, &$tipoNotificacion) {
                            $record->estado = $nuevoEstado;
                            $record->save();
    
                            if (!$nuevoEstado) {
                                $perfiles = $record->perfiles;
                                $perfilesDesactivados = [];
    
                                foreach ($perfiles as $perfil) {
                                    if ($perfil->estado == 1) {
                                        $activosRestantes = $perfil->examenes()
                                            ->where('estado', 1)
                                            ->count();
    
                                        if ($activosRestantes < 2) {
                                            $perfil->estado = 0;
                                            $perfil->save();
                                            $perfilesDesactivados[] = $perfil->nombre;
                                        }
                                    }
                                }
    
                                if (!empty($perfilesDesactivados)) {
                                    $mensajePerfiles = ' (Se desactivaron automáticamente los perfiles: ' . implode(', ', $perfilesDesactivados) . ' por tener menos de 2 exámenes activos)';
                                    $tipoNotificacion = 'warning';
                                }
                            }
                        });
    
                        Notification::make()
                            ->title($record->estado ? 'Activado' : 'Desactivado')
                            ->body(($record->estado ? 'Examen activado.' : 'Examen dado de baja.') . $mensajePerfiles)
                            ->status($tipoNotificacion)
                            ->send();
                    })
                    ->iconButton()
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [
            PruebasRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExamens::route('/'),
            'create' => Pages\CreateExamen::route('/create'),
            'edit' => Pages\EditExamen::route('/{record}/edit'),
            'view' => Pages\ViewExamen::route('/{record}'),
        ];
    }
}