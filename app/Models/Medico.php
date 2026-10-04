<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Medico extends Authenticatable
{
    use HasFactory;

    protected $table = 'medicos';

    // Permitimos asignación masiva solo para el nombre
    protected $fillable = [
        'nombre',
    ];

    protected $hidden = ['password', 'portal_version'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'portal_activo' => 'boolean',
            'portal_todos_pacientes' => 'boolean',
            'portal_version' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (Medico $medico): void {
            if ($medico->isDirty(['password', 'portal_activo', 'portal_todos_pacientes'])) {
                $medico->portal_version = (int) $medico->getOriginal('portal_version') + 1;
            }
        });
    }

    public function getUsuarioPortalAttribute(): string
    {
        return $this->portal_usuario ?: 'MED-'.$this->id;
    }

    /**
     * Relación opcional: Obtener todas las órdenes de este médico.
     */
    public function ordens(): HasMany
    {
        return $this->hasMany(Orden::class);
    }
}
