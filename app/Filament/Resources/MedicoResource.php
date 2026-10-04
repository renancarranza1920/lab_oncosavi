<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MedicoResource\Pages;
use App\Models\Medico;
use App\Services\AccesoMedicoService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MedicoResource extends Resource
{
    protected static ?string $model = Medico::class;

    protected static ?string $navigationGroup = 'Atención al Paciente';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationIcon = 'heroicon-o-user';

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->whereNull('portal_usuario');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // 1. Contenedor principal tipo Tarjeta (igual que en Clientes)
                Forms\Components\Card::make()
                    ->schema([

                        // 2. Sección con título
                        Forms\Components\Section::make('Datos del Médico')
                            ->schema([

                                // 3. Grid (aunque sea un solo campo, mantiene la estructura visual)
                                Forms\Components\Grid::make(1)
                                    ->schema([
                                        Forms\Components\TextInput::make('nombre')
                                            ->label('Nombre Completo')
                                            ->required()
                                            ->validationMessages([
                                                'required' => 'El nombre del médico es obligatorio.',
                                            ])
                                            ->maxLength(255)
                                            ->placeholder('Ej: Dr. Juan Pérez')
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->searchable()
                    ->sortable()
                    ->label('Nombre'),
                Tables\Columns\TextColumn::make('usuario_portal')
                    ->label('Usuario del portal')->copyable()
                    ->visible(fn () => auth()->user()?->can('manage_settings')),
                Tables\Columns\IconColumn::make('portal_activo')->label('Acceso al expediente')->boolean()
                    ->visible(fn () => auth()->user()?->can('manage_settings')),
                Tables\Columns\TextColumn::make('portal_todos_pacientes')->label('Expedientes permitidos')
                    ->formatStateUsing(fn (bool $state) => $state ? 'Todos los pacientes' : 'Sus propias órdenes')
                    ->visible(fn () => auth()->user()?->can('manage_settings')),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('configurarPortal')
                    ->label('Acceso al portal')->icon('heroicon-o-key')->color('gray')
                    ->modalSubmitActionLabel('Guardar acceso')->modalCancelActionLabel('Cancelar')
                    ->visible(fn () => auth()->user()?->can('manage_settings'))
                    ->modalDescription(fn (Medico $record) => 'Usuario: '.$record->usuario_portal.'. Puedes usar la misma contraseña para varios médicos. Cambiar el acceso cierra sus sesiones anteriores.')
                    ->form(fn (Medico $record) => self::formAcceso($record))
                    ->action(function (Medico $record, array $data): void {
                        app(AccesoMedicoService::class)->configurar($record, $data['activo'], $data['todos_pacientes'], $data['password'] ?? null);
                        Notification::make()->title('Acceso al expediente actualizado')->success()->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('habilitarPortal')
                        ->label('Habilitar portal con una misma contraseña')->icon('heroicon-o-key')
                        ->modalSubmitActionLabel('Guardar accesos')->modalCancelActionLabel('Cancelar')
                        ->visible(fn () => auth()->user()?->can('manage_settings'))
                        ->modalDescription('Los médicos seleccionados tendrán la misma contraseña y su propio usuario MED-ID. Los cambios cierran las sesiones anteriores.')
                        ->form(self::formAcceso())
                        ->action(function (Collection $records, array $data): void {
                            DB::transaction(function () use ($records, $data): void {
                                foreach ($records as $record) {
                                    app(AccesoMedicoService::class)->configurar($record, $data['activo'], $data['todos_pacientes'], $data['password'] ?? null);
                                }
                            });
                            Notification::make()->title('Acceso de médicos actualizado')->success()->send();
                        })->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function formAcceso(?Medico $medico = null): array
    {
        return [
            Forms\Components\Toggle::make('activo')->label('Habilitar acceso al portal')
                ->default($medico?->portal_activo ?? true)->live(),
            Forms\Components\Toggle::make('todos_pacientes')->label('Permitir consultar todos los pacientes')
                ->helperText('Apagado: solo pacientes y órdenes asociados a este médico. Encendido: expediente general de todo el laboratorio.')
                ->default($medico?->portal_todos_pacientes ?? false),
            Forms\Components\TextInput::make('password')->label('Contraseña del portal')
                ->password()->revealable()->minLength(8)->maxLength(128)->confirmed()
                ->required(fn (Forms\Get $get) => $get('activo') && ! $medico?->password)
                ->helperText($medico?->password ? 'Déjala vacía para conservar la contraseña actual.' : 'Define una contraseña de al menos 8 caracteres.'),
            Forms\Components\TextInput::make('password_confirmation')->label('Repetir contraseña')
                ->password()->revealable()->requiredWith('password')->dehydrated(false),
        ];
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMedicos::route('/'),
            'create' => Pages\CreateMedico::route('/create'),
            'edit' => Pages\EditMedico::route('/{record}/edit'),
        ];
    }
}
