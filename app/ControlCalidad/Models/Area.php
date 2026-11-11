<?php

namespace App\ControlCalidad\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Area extends Model
{
    protected $table = 'cci_areas';
    protected $fillable = ['nombre', 'equipo'];

    public function pruebas(): HasMany
    {
        return $this->hasMany(Prueba::class, 'area_id');
    }

    public function registros(): HasMany
    {
        return $this->hasMany(Registro::class, 'area_id');
    }
}
