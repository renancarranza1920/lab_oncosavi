<?php

namespace App\ControlCalidad\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValorControl extends Model
{
    protected $table = 'cci_valores_control';
    protected $fillable = ['nivel_id', 'lote_id', 'esperado', 'minimo', 'maximo'];
    protected $casts = ['nivel_id' => 'integer', 'lote_id' => 'integer', 'esperado' => 'decimal:6', 'minimo' => 'decimal:6', 'maximo' => 'decimal:6'];

    public function nivel(): BelongsTo
    {
        return $this->belongsTo(Nivel::class, 'nivel_id');
    }

    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class, 'lote_id');
    }
}
