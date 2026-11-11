<?php

namespace App\ControlCalidad\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lote extends Model
{
    protected $table = 'cci_lotes';
    protected $fillable = ['producto_id', 'nombre', 'numero', 'caduca_el'];
    protected $casts = ['producto_id' => 'integer', 'caduca_el' => 'date'];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function valoresControl(): HasMany
    {
        return $this->hasMany(ValorControl::class, 'lote_id');
    }
}
