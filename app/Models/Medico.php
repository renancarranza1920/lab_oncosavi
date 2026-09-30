<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Medico extends Model
{
    use HasFactory;

    protected $table = 'medicos';

    // Permitimos asignación masiva solo para el nombre
    protected $fillable = [
        'nombre',
    ];

    /**
     * Relación opcional: Obtener todas las órdenes de este médico.
     */
    public function ordens(): HasMany
    {
        return $this->hasMany(Orden::class);
    }
}