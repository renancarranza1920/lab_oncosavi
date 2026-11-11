<?php

namespace App\ControlCalidad\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prueba extends Model
{
    protected $table = 'cci_pruebas';
    protected $fillable = ['area_id', 'nombre', 'unidad'];
    protected $casts = ['area_id' => 'integer'];

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function niveles(): HasMany
    {
        return $this->hasMany(Nivel::class, 'prueba_id')->orderBy('orden')->orderBy('id');
    }

    public function resultados(): HasMany
    {
        return $this->hasMany(Resultado::class, 'prueba_id');
    }
}
