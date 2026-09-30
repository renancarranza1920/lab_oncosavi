<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ValorReferencia extends Model
{
    use HasFactory, LogsActivity;

    protected $guarded = [];

    public function prueba(): BelongsTo
    {
        return $this->belongsTo(Prueba::class);
    }

    public function grupoEtario(): BelongsTo
    {
        return $this->belongsTo(GrupoEtario::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('Valores de Referencia')
            ->setDescriptionForEvent(function (string $eventName) {
                $eventoTraducido = match ($eventName) {
                    'created' => 'creado',
                    'updated' => 'actualizado',
                    'deleted' => 'eliminado',
                    default => $eventName,
                };

                return "Un valor de referencia (ID: {$this->id}) para la prueba [ID: {$this->prueba_id}] ha sido {$eventoTraducido}";
            })
            ->logUnguarded()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
