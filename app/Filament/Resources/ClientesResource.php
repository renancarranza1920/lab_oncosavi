<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientesResource\Pages;
use App\Models\Cliente;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\SelectFilter;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Split;
use Illuminate\Database\Eloquent\Builder;

class ClientesResource extends Resource
{
    protected static ?string $model = Cliente::class;

    protected static ?string $navigationGroup = 'Atención al Paciente';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationIcon = 'heroicon-o-identification';
    protected static ?string $navigationLabel = 'Clientes';
    protected static ?string $pluralModelLabel = 'Clientes';
    protected static ?string $modelLabel = 'Cliente';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Section::make('Datos Personales')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('nombre')
                                            ->label('Nombre')
                                            ->required()
                                            ->validationMessages([
                                                'required' => 'El nombre es obligatorio.',
                                            ])
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('apellido')
                                            ->label('Apellido')
                                            ->required()
                                            ->validationMessages([
                                                'required' => 'El apellido es obligatorio.',
                                            ])
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('dui')
                                            ->label('DUI')
                                            ->nullable()
                                            ->mask('99999999-9')
                                            ->placeholder('00000000-0')
                                            ->rule('regex:/^\d{8}-\d$/')
                                            ->unique(table: Cliente::class, column: 'dui', ignoreRecord: true)
                                            ->maxLength(10)
                                            ->validationMessages([
                                                'regex' => 'El DUI debe tener el formato 00000000-0 y contener solo numeros.',
                                                'unique' => 'Ya existe un cliente registrado con este DUI.',
                                            ]),
                                            
                                            Forms\Components\TextInput::make('edad')
                                            ->label('Edad (Años)')
                                            ->numeric()        // Valida que sea un número
                                            ->integer()        // Valida que sea un número entero (sin decimales)
                                            ->minValue(1)      // Valida que sea positivo (mínimo 1)
                                            ->maxValue(120)    // Valida que no exceda 120
                                            ->placeholder('Ej: 25')
    
                                            // Texto de ayuda para aclarar la prioridad
                                            ->helperText('Ingresar solo si desconoce la Fecha de Nacimiento.')
                                            ->columnSpan(1),

                                       // Fecha de Nacimiento ahora no es obligatoria
                                        Forms\Components\DatePicker::make('fecha_nacimiento')
                                            ->label('Fecha de Nacimiento')
                                            ->nullable() // Ahora es opcional
                                            ->placeholder('dd/mm/aaaa')
                                            ->maxDate(now())
                                            ->reactive() // Permite que los cambios actualicen el estado
                                            ->helperText('Si seleccionas una fecha de nacimiento, se borrará la selección del grupo etario.')
                                            ->afterStateUpdated(function ($state, $set) {
                                                // Si se establece una fecha, borra el grupo etario
                                                if ($state) {
                                                    $set('grupo_etario', null); // Borra la selección de grupo etario
                                                }
                                            }),

                                        // Grupo Etario con las opciones definidas
                                        Forms\Components\Select::make('grupo_etario')
                                        ->label('Grupo Etario')
                                        ->options(function () {
                                            // Filtramos los grupos etarios que queremos mostrar (los que nos mencionaste)
                                            return \App\Models\GrupoEtario::whereIn('nombre', [
                                                    'Neonatos',
                                                    'Lactantes',
                                                    'Niños',
                                                    'Adolescentes',
                                                    'Adultos',
                                                    'Adultos mayores',
                                                    'Bebes'
                                                ])
                                                ->pluck('nombre', 'id')  // Pluck el nombre para mostrar y el id para guardar
                                                ->toArray();
                                        })
                                        ->nullable()
                                        ->reactive() // Permite que los cambios actualicen el estado
                                        ->helperText('Si seleccionas un grupo etario, se borrará la fecha de nacimiento.')
                                        ->afterStateUpdated(function ($state, $set) {
                                            // Si se selecciona un grupo etario, borra la fecha de nacimiento
                                            if ($state) {
                                                $set('fecha_nacimiento', null); // Borra la selección de fecha de nacimiento
                                            }
                                        }),


                                        Forms\Components\Select::make('genero')
                                            ->label('Género')
                                            ->options([
                                                'Masculino' => 'Masculino',
                                                'Femenino' => 'Femenino',
                                            ])
                                            ->required()
                                            ->validationMessages([
                                                'required' => 'Por favor, selecciona un género.',
                                            ]),
                                    ]),
                            ]),

                        Forms\Components\Section::make('Contacto')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('telefono')
                                            ->label('Teléfono')
                                            ->numeric()
                                            ->maxLength(9),

                                        Forms\Components\TextInput::make('correo')
                                            ->label('Correo Electrónico')
                                            ->email()
                                            ->maxLength(255)
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(fn($state, callable $set) => $set('correo', strtolower($state))),

                                        Forms\Components\TextInput::make('direccion')
                                            ->label('Dirección')
                                            ->columnSpanFull()
                                            ->maxLength(255),
                                    ]),
                            ]),

                        /* AQUÍ VA EL TOGGLE CON ESTADO TEXTO */
                        Forms\Components\Toggle::make('estado')
                            ->label('Estado')
                            ->inline(false)
                            ->onColor('success')
                            ->offColor('gray')
                            ->onIcon('heroicon-o-check')
                            ->offIcon('heroicon-o-x-mark')
                            ->default('Activo')
                            ->formatStateUsing(fn ($state) => $state ? 'Activo' : 'Inactivo')
                            ->dehydrateStateUsing(fn ($state) => $state ? 'Activo' : 'Inactivo')
                            ->afterStateHydrated(function ($state, $set) {
                                $set('estado', $state === 'Activo');
                            }),
                    ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Información Personal')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Split::make([
                            // Columna 1: Nombres y Edad
                            Grid::make(1)->schema([
                                TextEntry::make('nombre')
                                    ->label('Nombre')
                                    ->weight('bold'),
                                
                                TextEntry::make('apellido')
                                    ->label('Apellido')
                                    ->weight('bold'),

                                TextEntry::make('dui')
                                    ->label('DUI')
                                    ->placeholder('No especificado')
                                    ->copyable(),
    
                                TextEntry::make('edad_calculada')
                                    ->label('Edad')
                                    ->getStateUsing(function (Cliente $record) {
                                        // 1. Si tiene fecha de nacimiento real
                                        if ($record->fecha_nacimiento) {
                                            // Ejemplo: "5 meses (17/09/2025)"
                                            return $record->edad_legible . ' (' . \Carbon\Carbon::parse($record->fecha_nacimiento)->format('d/m/Y') . ')';
                                        }
                                
                                        // 2. Si solo tiene edad manual
                                        // Ejemplo: "30 años (Aproximado)"
                                        return $record->edad_legible . ' (Aproximado)';
                                    })
                                    ->icon('heroicon-m-calendar'),
                            ]),
    
                            // Columna 2: Género y Grupo
                            Grid::make(1)->schema([
                                TextEntry::make('genero')
                                    ->label('Género')
                                    ->badge()
                                    ->color('gray'),
    
                                TextEntry::make('grupo_etario') // Asegúrate de tener la relación o el campo
                                    ->label('Grupo Etario')
                                    ->placeholder('No especificado')
                                    ->badge()
                                    ->color('gray'),
                                    
                                TextEntry::make('estado')
                                    ->label('Estado Actual')
                                    ->badge()
                                    ->color(fn ($state) => $state === 'Activo' ? 'success' : 'gray'),
                            ]),
                        ])->from('md'), // En móviles se apila, en escritorio se divide
                    ]),
    
                Section::make('Información de Contacto')
                    ->icon('heroicon-o-phone')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('telefono')
                                ->label('Teléfono')
                                ->icon('heroicon-m-phone')
                                ->copyable(),
    
                            TextEntry::make('correo')
                                ->label('Correo Electrónico')
                                ->icon('heroicon-m-envelope')
                                ->copyable(),
    
                            TextEntry::make('direccion')
                                ->label('Dirección Completa')
                                ->icon('heroicon-m-map-pin')
                                ->columnSpanFull(),
                        ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nombre')
            ->columns([
                Tables\Columns\TextColumn::make('NumeroExp')
                    ->label('No. Expediente')
                    ->weight('bold')
                    ->copyable()
                     ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('dui')
                    ->label('DUI')
                    ->copyable()
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        $dui = preg_replace('/\D/', '', $search);

                        return $query->where(function (Builder $query) use ($search, $dui) {
                            $query->where('dui', 'like', "%{$search}%")
                                ->when($dui !== '', fn (Builder $query) => $query->orWhereRaw("REPLACE(dui, '-', '') like ?", ["%{$dui}%"]));
                        });
                    })
                    ->sortable(),
    
                Tables\Columns\TextColumn::make('nombre_completo')
                    ->label('Paciente')
                    ->getStateUsing(fn (Cliente $record) => $record->nombre . ' ' . $record->apellido)
                       ->searchable(['nombre', 'apellido'])
                    ->sortable(['nombre']),
    
                Tables\Columns\TextColumn::make('fecha_nacimiento')
                    ->label('Fecha / Edad') // Puedes cambiar la etiqueta si gustas
                    ->date('d/m/Y') // Arriba muestra la fecha: 17/09/2025
                    ->description(function (Cliente $record) {
                        // AQUÍ ESTÁ LA CLAVE: Llamamos a la función del modelo, NO calculamos nada aquí.
                        return $record->edad_legible; 
                    }),
    
                Tables\Columns\TextColumn::make('telefono')
                    ->label('Teléfono')
                     ->searchable()
                    ->icon('heroicon-m-phone'),
    
                Tables\Columns\TextColumn::make('correo')
                    ->label('Correo')
                     ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
    
                /* ESTADO COMO TEXTO */
                Tables\Columns\TextColumn::make('estado')
                    ->badge()
                    ->color(fn ($state) => $state === 'Activo' ? 'success' : 'gray'),
            ])
            ->filters([
                /* FILTRO USANDO TEXTO */
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        'Activo' => 'Activo',
                        'Inactivo' => 'Inactivo',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->visible(fn () => auth()->user()->can('update_clientes')),
    
                // --- AQUÍ ESTÁ EL CAMBIO PRINCIPAL ---
                // Usamos ViewAction nativo que busca automáticamente tu infolist()
                Tables\Actions\ViewAction::make()
                    ->label('Ver')
                    ->color('gray')
                    ->icon('heroicon-s-eye')
                    ->visible(fn () => auth()->user()->can('ver_detalle_clientes'))
                    ->modalHeading('Detalle del Cliente')
                    ->modalWidth('lg'),
                // -------------------------------------
    
                /* CAMBIO DE ESTADO TEXTO */
                Action::make('cambiar_estado')
                    ->label(fn($record) => $record->estado === 'Activo' ? 'Dar de baja' : 'Dar de alta')
                    ->icon(fn($record) => $record->estado === 'Activo' ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn($record) => $record->estado === 'Activo' ? 'warning' : 'success')
                    ->visible(fn () => auth()->user()->can('cambiar_estado_clientes'))
                    ->tooltip(fn($record) => $record->estado === 'Activo' ? 'Dar de baja' : 'Dar de alta')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->estado = $record->estado === 'Activo' ? 'Inactivo' : 'Activo';
                        $record->save();
    
                        Notification::make()
                            ->title($record->estado === 'Activo' ? 'Cliente activado' : 'Cliente desactivado')
                            ->success()
                            ->send();
                    })
                    ->iconButton(),
    
                Action::make('expediente')
                    ->label('Expediente')
                    ->icon('heroicon-o-folder-open')
                    ->color('info')
                    ->visible(fn () => auth()->user()->can('ver_expediente_clientes'))
                    ->url(fn (Cliente $record): string => ClientesResource::getUrl('expediente', ['record' => $record->id])),
            ])
               
            ->bulkActions([
                // Puedes agregar acciones masivas aquí si lo necesitas
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClientes::route('/'),
            'create' => Pages\CreateClientes::route('/create'),
            'edit' => Pages\EditClientes::route('/{record}/edit'),
            'expediente' => Pages\Expediente::route('/{record}/expediente'),
        ];
    }
}
