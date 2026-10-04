<?php

namespace App\Filament\Pages;

use App\Filament\Resources\ClientesResource;
use App\Models\Cliente;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action; // Importante para las acciones si las usas

class BuscarExpediente extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';
   protected static ?string $navigationGroup = 'Atención al Paciente';
protected static ?int $navigationSort = 1;
    protected static ?string $title = 'Buscador de Expedientes';
    protected static string $view = 'filament.pages.buscar-expediente';


    public ?array $data = [];
    public $resultados = [];
public static function canAccess(): bool
    {
        return auth()->user()->can('acceder_buscador_expedientes');
    }
    public function mount(): void
    {   
        abort_unless(auth()->user()->can('acceder_buscador_expedientes'), 403);
        $this->form->fill();
        // Iniciamos vacío o con los últimos creados si prefieres
        $this->resultados = []; 
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Grid principal de 4 columnas para pantallas grandes
                Grid::make([
                    'default' => 1,
                    'sm' => 2,
                    'md' => 3,
                    'xl' => 4,
                ])
                ->schema([
                    // 1. Identificadores Principales
                    TextInput::make('NumeroExp')
                        ->label('No. Expediente')
                        ->placeholder('Ej: EA25001')
                        ->prefixIcon('heroicon-m-hashtag'),

                    TextInput::make('dui')
                        ->label('DUI')
                        ->mask('99999999-9')
                        ->placeholder('00000000-0')
                        ->prefixIcon('heroicon-m-identification'),

                    TextInput::make('nombre')
                        ->label('Nombre')
                        ->placeholder('Ej: Manuel')
                        ->prefixIcon('heroicon-m-user'),

                    TextInput::make('apellido')
                        ->label('Apellido')
                        ->placeholder('Ej: Carranza')
                        ->prefixIcon('heroicon-m-user'),

                    // 2. Datos Demográficos
                    Select::make('genero')
                        ->label('Género')
                        ->options([
                            'Masculino' => 'Masculino',
                            'Femenino' => 'Femenino',
                        ])
                        ->native(false),

                    DatePicker::make('fecha_nacimiento')
                        ->label('Fecha de Nacimiento')
                        ->native(false) // Usa el datepicker bonito de Filament
                        ->displayFormat('d/m/Y')
                        ->prefixIcon('heroicon-m-calendar'),

                    // 3. Contacto
                    TextInput::make('telefono')
                        ->label('Teléfono')
                        ->numeric()
                        ->prefixIcon('heroicon-m-phone'),

                    TextInput::make('correo')
                        ->label('Correo Electrónico')
                        ->email()
                        ->prefixIcon('heroicon-m-envelope'),
                    
                    // 4. Estado
                    Select::make('estado')
                        ->label('Estado del Cliente')
                        ->options([
                            'Activo' => 'Activo',
                            'Inactivo' => 'Inactivo', // O null/0 según tu lógica
                        ])
                        ->native(false),
                ]),
            ])
            ->statePath('data');
    }

    public function buscar(): void
    {
        $filtros = $this->form->getState();

        // Iniciamos la consulta base
        $query = Cliente::query();

        // Aplicamos filtros dinámicamente solo si el campo tiene valor
        
        // Búsqueda exacta o parcial por Expediente
        $query->when($filtros['NumeroExp'], fn($q, $v) => $q->where('NumeroExp', 'like', "%{$v}%"));

        // Busqueda parcial por DUI con o sin guion
        $query->when($filtros['dui'], function ($q, $v) {
            $dui = preg_replace('/\D/', '', $v);

            $q->where(function ($q) use ($v, $dui) {
                $q->where('dui', 'like', "%{$v}%")
                    ->when($dui !== '', fn ($q) => $q->orWhereRaw("REPLACE(dui, '-', '') like ?", ["%{$dui}%"]));
            });
        });

        // Búsqueda parcial por Nombre
        $query->when($filtros['nombre'], fn($q, $v) => $q->where('nombre', 'like', "%{$v}%"));

        // Búsqueda parcial por Apellido
        $query->when($filtros['apellido'], fn($q, $v) => $q->where('apellido', 'like', "%{$v}%"));

        // Búsqueda exacta por Género
        $query->when($filtros['genero'], fn($q, $v) => $q->where('genero', $v));

        // Búsqueda exacta por Fecha Nacimiento
        $query->when($filtros['fecha_nacimiento'], fn($q, $v) => $q->whereDate('fecha_nacimiento', $v));

        // Búsqueda parcial por Teléfono
        $query->when($filtros['telefono'], fn($q, $v) => $q->buscarTelefono($v));

        // Búsqueda parcial por Correo
        $query->when($filtros['correo'], fn($q, $v) => $q->where('correo', 'like', "%{$v}%"));

        // Búsqueda exacta por Estado (Asumiendo que en BD guardas 'Activo'/'Inactivo' como string según tu seed anterior)
        $query->when($filtros['estado'], fn($q, $v) => $q->where('estado', $v));

        // Obtenemos resultados (limitamos a 20 para rendimiento)
        $this->resultados = $query->with('telefonos')->latest()->limit(24)->get();
    }

    public function limpiar(): void
    {
        $this->form->fill();
        $this->resultados = [];
    }
}
