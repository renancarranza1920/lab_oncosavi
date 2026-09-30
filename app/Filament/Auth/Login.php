<?php

namespace App\Filament\Auth;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Form;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Models\Contracts\FilamentUser;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\SimplePage;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use Filament\Forms\Get; // Añadido para la validación condicional
use Filament\Forms\Set; // Añadido: para limpiar el campo opuesto cuando cambia

class Login extends SimplePage
{
    use InteractsWithFormActions;
    use WithRateLimiting;

    protected static string $view = 'filament-panels::pages.auth.login';

    public ?array $data = [];

    public function mount(): void
    {
        if (Filament::auth()->check()) {
            redirect()->intended(Filament::getUrl());
        }

        $this->form->fill();
    }

    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();
            return null;
        }

        $data = $this->form->getState();

        // ⚠️ Nota: Esta validación es necesaria si quitas el required()
        // En el getCredentialsFromFormData se encarga de usar el campo llenado.
        
        if (! Filament::auth()->attempt($this->getCredentialsFromFormData($data), $data['remember'] ?? false)) {
            $this->throwFailureValidationException();
        }

        $user = Filament::auth()->user();

        if (($user instanceof FilamentUser) && (! $user->canAccessPanel(Filament::getCurrentPanel()))) {
            Filament::auth()->logout();
            $this->throwFailureValidationException();
        }

        session()->regenerate();

        return app(LoginResponse::class);
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            // Mensaje genérico para el error de credenciales
            'data.password' => 'Credenciales incorrectas.', 
        ]);
    }

    /**
     * Fallback notification to show when rate limiter blocks requests.
     *
     * Algunos paquetes de rate limiting no inyectan este helper en la
     * página de Login; añadimos una implementación aquí para evitar el
     * error "method does not exist" y proporcionar una notificación útil.
     *
     * @param  \DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException  $exception
     * @return \Filament\Notifications\Notification|null
     */
    protected function getRateLimitedNotification(TooManyRequestsException $exception): ?Notification
    {
        $seconds = null;

        if (property_exists($exception, 'secondsUntilAvailable')) {
            $seconds = $exception->secondsUntilAvailable;
        } elseif (method_exists($exception, 'availableAt')) {
            try {
                $availableAt = $exception->availableAt();
                if ($availableAt instanceof \DateTimeInterface) {
                    $seconds = max(0, $availableAt->getTimestamp() - time());
                }
            } catch (\Throwable $e) {
                $seconds = null;
            }
        }

        $body = $seconds ? "Demasiados intentos. Intenta de nuevo en {$seconds} segundos." : 'Demasiados intentos. Intenta de nuevo más tarde.';

        return Notification::make()
            ->title('Demasiados intentos')
            ->danger()
            ->body($body);
    }

    public function form(Form $form): Form
    {
        return $form;
    }

    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        Tabs::make('Método de Autenticación')
                            ->tabs([
                                Tabs\Tab::make('Correo Electrónico')
                                    ->icon('heroicon-o-envelope')
                                    ->schema([
                                        $this->getEmailComponent(),
                                        $this->getPasswordFormComponent(),
                                    ]),
                                
                                Tabs\Tab::make('Nombre de Usuario')
                                    ->icon('heroicon-o-user')
                                    ->schema([
                                        $this->getUsernameComponent(),
                                        $this->getPasswordFormComponent(),
                                    ]),
                            ])
                            ->columnSpanFull(),
                        
                        $this->getRememberFormComponent(),
                    ])
                    ->statePath('data'),
            ),
        ];
    }
    
    // =========================================================
    // MODIFICADO: VALIDACIÓN CONDICIONAL
    // =========================================================

    protected function getEmailComponent(): Component
    {
        return TextInput::make('email')
            ->label('Correo Electrónico')
            ->email()
            ->live()
            // Hacemos que sea requerido SÓLO si el campo de username está vacío
            ->required(fn (Get $get) => !filled($get('username')))
            // Al actualizar el email, borramos el username para evitar valores duplicados en el state
            ->afterStateUpdated(fn (Set $set) => $set('username', null))
            ->extraInputAttributes(['tabindex' => 1]);
    }
    
    protected function getUsernameComponent(): Component
    {
        return TextInput::make('username')
            ->label('Nombre de usuario')
            ->live()
            // Hacemos que sea requerido SÓLO si el campo de email está vacío
            ->required(fn (Get $get) => !filled($get('email')))
            // Al actualizar el username, borramos el email para evitar valores duplicados en el state
            ->afterStateUpdated(fn (Set $set) => $set('email', null))
            ->extraInputAttributes(['tabindex' => 1]);
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label(__('filament-panels::pages/auth/login.form.password.label'))
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->autocomplete('current-password')
            ->required()
            ->hint(filament()->hasPasswordReset() ? new HtmlString(Blade::render('<x-filament::link :href="filament()->getRequestPasswordResetUrl()"> {{ __(\'filament-panels::pages/auth/login.actions.request_password_reset.label\') }}</x-filament::link>')) : null)
            ->extraInputAttributes(['tabindex' => 2]);
    }

    protected function getRememberFormComponent(): Component
    {
        return Checkbox::make('remember')
            ->label(__('filament-panels::pages/auth/login.form.remember.label'));
    }

    public function registerAction(): Action
    {
        return Action::make('register')
            ->link()
            ->label(__('filament-panels::pages/auth/login.actions.register.label'))
            ->url(filament()->getRegistrationUrl());
    }

    public function getTitle(): string|Htmlable
    {
        return __('filament-panels::pages/auth/login.title');
    }

    public function getHeading(): string|Htmlable
    {
        return __('filament-panels::pages/auth/login.heading');
    }

    protected function getFormActions(): array
    {
        return [
            $this->getAuthenticateFormAction(),
        ];
    }

    protected function getAuthenticateFormAction(): Action
    {
        return Action::make('authenticate')
            ->label(__('filament-panels::pages/auth/login.form.actions.authenticate.label'))
            ->submit('authenticate');
    }

    protected function hasFullWidthFormActions(): bool
    {
        return true;
    }
    
    // Lógica para determinar si el campo es un email o un nickname
    protected function getCredentialsFromFormData(array $data): array
    {
        $email = $data['email'] ?? null;
        $username = $data['username'] ?? null;
        $password = $data['password'];

        // Intentamos autenticar por email si el campo 'email' está lleno
        if (filled($email)) {
            return [
                'email' => $email,
                'password' => $password,
            ];
        }

        // Si no se usó email, intentamos autenticar por nickname/username.
        if (filled($username)) {
            return [
                // Usa la columna REAL de tu base de datos aquí 
                'nickname' => $username, 
                'password' => $password,
            ];
        }
        
        // Esto solo se ejecuta si la validación falla antes de llegar al auth()->attempt
        $this->throwFailureValidationException();
    }
}
