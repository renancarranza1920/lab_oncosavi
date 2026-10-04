<?php

namespace App\Filament\Resources\MedicoResource\Pages;

use App\Filament\Resources\MedicoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMedicos extends ListRecords
{
    protected static string $resource = MedicoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('accesoMedicoGeneral')
                ->label('Acceso médico general')->icon('heroicon-o-key')
                ->visible(fn () => auth()->user()?->can('manage_settings'))
                ->modalHeading('Acceso compartido para médicos')
                ->modalDescription('Usuario: medicos. Este acceso permite consultar todos los expedientes y sus PDFs, sin entrar al panel administrativo. Cambiar la contraseña cierra las sesiones anteriores.')
                ->modalSubmitActionLabel('Guardar acceso')->modalCancelActionLabel('Cancelar')
                ->fillForm(fn () => ['activo' => \App\Models\Medico::where('portal_usuario', 'medicos')->value('portal_activo')])
                ->form(fn () => [
                    \Filament\Forms\Components\Toggle::make('activo')->label('Habilitar acceso general')->live(),
                    \Filament\Forms\Components\TextInput::make('password')->label('Contraseña compartida')
                        ->password()->revealable()->minLength(8)->maxLength(128)->confirmed()
                        ->required(fn (\Filament\Forms\Get $get) => $get('activo') && ! \App\Models\Medico::where('portal_usuario', 'medicos')->value('password'))
                        ->helperText('Déjala vacía para conservar la contraseña actual.'),
                    \Filament\Forms\Components\TextInput::make('password_confirmation')->label('Repetir contraseña')
                        ->password()->revealable()->requiredWith('password')->dehydrated(false),
                ])
                ->action(function (array $data): void {
                    app(\App\Services\AccesoMedicoService::class)->configurarGeneral($data['activo'], $data['password'] ?? null);
                    \Filament\Notifications\Notification::make()->title('Acceso médico general actualizado')->success()->send();
                }),
            Actions\CreateAction::make(),
            Actions\Action::make('abrirPortal')
                ->label('Abrir portal de expedientes')->icon('heroicon-o-arrow-top-right-on-square')->color('gray')
                ->url(fn () => route('expediente.index'))->openUrlInNewTab()
                ->visible(fn () => auth()->user()?->can('manage_settings')),
        ];
    }
}
