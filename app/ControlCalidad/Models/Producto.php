<?php

namespace App\ControlCalidad\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producto extends Model
{
    protected $table = 'cci_productos';
    protected $fillable = ['nombre'];

    public function lotes(): HasMany
    {
        return $this->hasMany(Lote::class, 'producto_id');
    }
}
