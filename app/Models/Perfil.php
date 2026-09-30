<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// 1. IMPORTAR LAS CLASES DE SPATIE Y RELACIONES
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Perfil extends Model
{
    // 2. USAR LOS TRAITS
    use HasFactory, LogsActivity;

    protected $table = 'perfil';

    protected $fillable = [
        'nombre',
        'precio',
        'estado',
    ];

    // 3. ESPECIFICAR EL TIPO DE RETORNO
    public function examenes(): BelongsToMany
    {
        return $this->belongsToMany(Examen::class, 'detalle_perfil', 'perfil_id', 'examen_id')
                    ->withTimestamps();
    }

    // 3. ESPECIFICAR EL TIPO DE RETORNO
    public function ordenes(): BelongsToMany
    {
        return $this->belongsToMany(Orden::class, 'detalle_orden_perfils', 'perfil_id', 'orden_id')
                    ->withTimestamps();
    }

    // 4. AÑADIR EL MÉTODO DE CONFIGURACIÓN DE LA BITÁCORA
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('Perfiles') // Nombre del módulo en la bitácora
            
            ->setDescriptionForEvent(function(string $eventName) {
                // Traducimos el evento
                $eventoTraducido = match($eventName) {
                    'created' => 'creado',
                    'updated' => 'actualizado',
                    'deleted' => 'eliminado',
                    default => $eventName
                };
                
                return "El perfil '{$this->nombre}' ha sido {$eventoTraducido}";
            })
            
            // Rastrear todos los campos en $fillable automáticamente
            ->logFillable() 
            
            ->logOnlyDirty() 
            ->dontSubmitEmptyLogs();
    }
}