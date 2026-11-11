<?php

namespace App\ControlCalidad\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Resultado extends Model
{
    protected $table = 'cci_resultados';
    protected $fillable = ['registro_id', 'prueba_id', 'nivel_id', 'valor_control_id', 'valor', 'valor_original', 'prueba_nombre', 'nivel_nombre', 'unidad', 'lote_nombre', 'lote_numero', 'esperado', 'minimo', 'maximo'];
    protected $casts = ['registro_id' => 'integer', 'prueba_id' => 'integer', 'nivel_id' => 'integer', 'valor_control_id' => 'integer', 'valor' => 'decimal:6', 'esperado' => 'decimal:6', 'minimo' => 'decimal:6', 'maximo' => 'decimal:6'];

    public function registro(): BelongsTo
    {
        return $this->belongsTo(Registro::class, 'registro_id');
    }

    public function prueba(): BelongsTo
    {
        return $this->belongsTo(Prueba::class, 'prueba_id');
    }

    public function nivel(): BelongsTo
    {
        return $this->belongsTo(Nivel::class, 'nivel_id');
    }

    public function valorControl(): BelongsTo
    {
        return $this->belongsTo(ValorControl::class, 'valor_control_id');
    }
}
