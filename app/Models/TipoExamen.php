<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// 1. IMPORTAR LAS CLASES DE SPATIE Y RELACIONES FALTANTES
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Models\Examen;

class TipoExamen extends Model
{
    // 2. USAR LOS TRAITS
    use HasFactory, LogsActivity;

    protected $table = 'tipo_examens';
    protected $fillable = [
        'nombre',
        'estado',
    ];

    // 3. ESPECIFICAR EL TIPO DE RETORNO
    public function examenes(): HasMany
    {
        return $this->hasMany(Examen::class, 'tipo_examen_id');
    }

    // 4. AÑADIR EL MÉTODO DE CONFIGURACIÓN DE LA BITÁCORA
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('Tipos de Examen') // Nombre del módulo
            
            ->setDescriptionForEvent(function(string $eventName) {
                // Traducimos el evento
                $eventoTraducido = match($eventName) {
                    'created' => 'creado',
                    'updated' => 'actualizado',
                    'deleted' => 'eliminado',
                    default => $eventName
                };
                
                return "El tipo de examen '{$this->nombre}' ha sido {$eventoTraducido}";
            })
            
            // Rastrear todos los campos en $fillable automáticamente
            ->logFillable() 
            
            ->logOnlyDirty() 
            ->dontSubmitEmptyLogs();
    }
}