<?php

namespace App\ControlCalidad\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Registro extends Model
{
    use SoftDeletes;

    protected $table = 'cci_registros';
    protected $fillable = ['area_id', 'laboratorista_id', 'registrado_por_id', 'actualizado_por_id', 'fecha_hora', 'observaciones'];
    protected $casts = ['area_id' => 'integer', 'laboratorista_id' => 'integer', 'registrado_por_id' => 'integer', 'actualizado_por_id' => 'integer', 'fecha_hora' => 'datetime'];

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function laboratorista(): BelongsTo
    {
        return $this->belongsTo(User::class, 'laboratorista_id');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por_id');
    }

    public function actualizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actualizado_por_id');
    }

    public function resultados(): HasMany
    {
        return $this->hasMany(Resultado::class, 'registro_id');
    }
}
