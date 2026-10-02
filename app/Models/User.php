<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class User extends Authenticatable implements \Filament\Models\Contracts\FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;
   use LogsActivity;
        use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
       'name',
        'email',
        'password',
        'nickname', 
        'firma_path',
        'sello_path',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected static function booted(): void
    {
        static::updated(function (self $usuario) {
            if ($usuario->wasChanged('password')) {
                activity('Usuarios')->performedOn($usuario)->causedBy(auth()->user())
                    ->withProperties(['password_actualizada' => true])
                    ->event('updated')->log('Contraseña actualizada para el usuario ' . $usuario->nickname);
            }
        });
    }

   public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('Usuarios')
            ->setDescriptionForEvent(function(string $eventName) {
                $eventoTraducido = match($eventName) {
                    'created' => 'creado',
                    'updated' => 'actualizado',
                    'deleted' => 'eliminado',
                    default => $eventName
                };
                return "El usuario '{$this->name}' (ID: {$this->id}) ha sido {$eventoTraducido}";
            })
            ->logOnly(['name', 'email', 'nickname', 'firma_path', 'sello_path'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function canAccessPanel(\Filament\Panel $panel): bool
    {
        return $this->can('access_admin_panel');
    }

    public function username()
{
    return 'nickname';
}

}
