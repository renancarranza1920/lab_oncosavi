<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// 1. IMPORTAR LAS CLASES DE SPATIE Y RELACIONES FALTANTES
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Models\DetalleOrden;
use App\Models\Prueba;

class Resultado extends Model 
{
    // 2. USAR LOS TRAITS
    use HasFactory, LogsActivity;

    protected $guarded = [];
    protected $casts = [
    'alertar' => 'boolean',
];
    public function setResultadoAttribute($value): void
    {
        $this->attributes['resultado'] = \App\Support\NumeroLaboratorio::normalizar($value);
    }

    public function realizadoPor()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    // 3. ESPECIFICAR TIPO DE RETORNO
    public function detalleOrden(): BelongsTo 
    { 
        return $this->belongsTo(DetalleOrden::class); 
    }

    // 3. ESPECIFICAR TIPO DE RETORNO
    public function prueba(): BelongsTo 
    { 
        return $this->belongsTo(Prueba::class); 
    }

    // 4. AÑADIR EL MÉTODO DE CONFIGURACIÓN DE LA BITÁCORA
   // 4. CONFIGURACIÓN DE BITÁCORA MEJORADA
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('Resultados')
            ->setDescriptionForEvent(function(string $eventName) {
                $evento = match($eventName) {
                    'created' => 'ingresado',
                    'updated' => 'actualizado',
                    'deleted' => 'eliminado',
                    default => $eventName
                };

                // --- MEJORA AQUÍ ---
                // Determinamos el nombre real de la prueba
                $nombrePrueba = 'Desconocida';

                if ($this->prueba) {
                    // Si es interno, tomamos el nombre de la relación
                    $nombrePrueba = $this->prueba->nombre;
                } elseif ($this->prueba_nombre_snapshot) {
                    // Si es externo, tomamos el nombre que guardaste manualmente
                    $nombrePrueba = $this->prueba_nombre_snapshot . ' (Externo)';
                }
                // -------------------

                // Obtenemos el ID de la orden de forma segura
                $ordenId = $this->detalleOrden ? $this->detalleOrden->orden_id : 'N/A';

                return "Resultado {$evento} para '{$nombrePrueba}' en Orden #{$ordenId}. Valor: {$this->resultado}";
            })
            ->logUnguarded()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}