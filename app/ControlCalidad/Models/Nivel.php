<?php

namespace App\ControlCalidad\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Nivel extends Model
{
    protected $table = 'cci_niveles';
    protected $fillable = ['prueba_id', 'nombre', 'orden'];
    protected $casts = ['prueba_id' => 'integer', 'orden' => 'integer'];

    public function prueba(): BelongsTo
    {
        return $this->belongsTo(Prueba::class, 'prueba_id');
    }

    public function valorControlActual(): HasOne
    {
        return $this->hasOne(ValorControl::class, 'nivel_id');
    }

    public function valorControl(): HasOne
    {
        return $this->valorControlActual();
    }

    public function resultados(): HasMany
    {
        return $this->hasMany(Resultado::class, 'nivel_id');
    }
}
