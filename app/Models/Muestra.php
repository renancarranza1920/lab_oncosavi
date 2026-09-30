<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// 1. IMPORTAR LAS CLASES DE SPATIE Y RELACIONES
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Muestra extends Model
{
    // 2. USAR LOS TRAITS
    use HasFactory, LogsActivity;

    protected $table = 'muestras';

    protected $fillable = [
        'nombre',
        'descripcion',
        'instrucciones_paciente',
    ];

    /**
     * Define la relación de Muchos a Muchos con Examen.
     * Una muestra puede ser requerida por muchos exámenes.
     */
    // 3. ESPECIFICAR EL TIPO DE RETORNO
    public function examenes(): BelongsToMany
    {
        return $this->belongsToMany(Examen::class, 'examen_muestra', 'muestra_id', 'examen_id');
    }

    // 4. AÑADIR EL MÉTODO DE CONFIGURACIÓN DE LA BITÁCORA
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('Muestras') // Nombre del módulo en la bitácora
            
            ->setDescriptionForEvent(function(string $eventName) {
                // Traducimos el evento
                $eventoTraducido = match($eventName) {
                    'created' => 'creada',
                    'updated' => 'actualizada',
                    'deleted' => 'eliminada',
                    default => $eventName
                };
                
                // Usamos "La muestra" (femenino)
                return "La muestra '{$this->nombre}' ha sido {$eventoTraducido}";
            })
            
            // Rastrear todos los campos en $fillable automáticamente
            ->logFillable() 
            
            ->logOnlyDirty() 
            ->dontSubmitEmptyLogs();
    }
}