<?php

namespace App\Filament\Auth;

use App\Support\SelloLaboratorio;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class EditProfile extends \Filament\Pages\Auth\EditProfile
{
    protected ?string $selloLaboratorioPendiente = null;

    public static function getLabel(): string
    {
        return 'Mi perfil';
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Mi cuenta')->schema([
                $this->getNameFormComponent(),
                TextInput::make('nickname')->label('Usuario')->disabled()->dehydrated(false),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ]),
            Section::make('Sello del laboratorio')
                ->description('Sello institucional compartido para los nuevos reportes de resultados. PNG de hasta 2 MB.')
                ->visible(fn () => auth()->user()->hasRole('admin'))
                ->schema([
                    FileUpload::make('sello_laboratorio')->label('Sello del laboratorio (PNG)')
                        ->image()->acceptedFileTypes(['image/png'])->maxSize(2048)
                        ->disk('public')->directory('sellos/laboratorio'),
                ]),
            Section::make('Mi firma y sello')
                ->description('Imágenes PNG de hasta 2 MB para los reportes de resultados.')
                ->visible(fn () => auth()->user()->can('gestionar_firma_sello_propio'))
                ->schema([
                    FileUpload::make('firma_path')->label('Firma (PNG)')
                        ->image()->acceptedFileTypes(['image/png'])->maxSize(2048)
                        ->disk('public')->directory(fn () => 'firmas/' . auth()->id()),
                    FileUpload::make('sello_path')->label('Sello (PNG)')
                        ->image()->acceptedFileTypes(['image/png'])->maxSize(2048)
                        ->disk('public')->directory(fn () => 'sellos/' . auth()->id()),
                ]),
        ]);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (auth()->user()->hasRole('admin')) {
            $data['sello_laboratorio'] = SelloLaboratorio::path();
        }

        return $data;
    }

    protected function afterSave(): void
    {
        if (auth()->user()->hasRole('admin')) {
            SelloLaboratorio::guardar($this->selloLaboratorioPendiente);
        }
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (auth()->user()->hasRole('admin')) {
            $this->selloLaboratorioPendiente = $data['sello_laboratorio'] ?? null;
        }
        $data = Arr::only($data, ['name', 'email', 'password', 'firma_path', 'sello_path']);
        foreach (['firma_path' => 'firmas', 'sello_path' => 'sellos'] as $campo => $directorio) {
            if (!auth()->user()->can('gestionar_firma_sello_propio')) {
                unset($data[$campo]);
                continue;
            }
            $path = $data[$campo] ?? null;
            if ($path && $path !== $this->getUser()->{$campo}
                && (!str_starts_with($path, $directorio . '/' . auth()->id() . '/')
                    || str_contains($path, '..'))) {
                throw ValidationException::withMessages(["data.{$campo}" => 'Seleccione una imagen propia.']);
            }
        }

        return $data;
    }
}
