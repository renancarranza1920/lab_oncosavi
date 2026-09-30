<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Prueba extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['nombre', 'tipo_prueba_id', 'examen_id', 'tipo_conjunto', 'estado'];

    public function tipoPrueba(): BelongsTo
    {
        return $this->belongsTo(TipoPrueba::class);
    }

    public function examen(): BelongsTo
    {
        return $this->belongsTo(Examen::class);
    }

    public function valoresReferencia(): HasMany
    {
        return $this->hasMany(ValorReferencia::class);
    }

    public function resultados(): HasMany
    {
        return $this->hasMany(Resultado::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('Pruebas')
            ->setDescriptionForEvent(function (string $eventName) {
                $eventoTraducido = match ($eventName) {
                    'created' => 'creada',
                    'updated' => 'actualizada',
                    'deleted' => 'eliminada',
                    default => $eventName,
                };

                return "La prueba '{$this->nombre}' ha sido {$eventoTraducido}";
            })
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
