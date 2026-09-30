<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

// 1. IMPORTAR LAS CLASES DE SPATIE Y RELACIONES FALTANTES
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Models\Prueba;

class TipoPrueba extends Model
{
    // 2. USAR LOS TRAITS
    use HasFactory, LogsActivity;

    protected $table = 'tipos_pruebas';
    protected $fillable = ['nombre'];

    // 3. ESPECIFICAR EL TIPO DE RETORNO (ya estaba)
    public function pruebas(): HasMany
    {
        return $this->hasMany(Prueba::class);
    }

    // 4. AÑADIR EL MÉTODO DE CONFIGURACIÓN DE LA BITÁCORA
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('Tipos de Prueba') // Nombre del módulo
            
            ->setDescriptionForEvent(function(string $eventName) {
                // Traducimos el evento
                $eventoTraducido = match($eventName) {
                    'created' => 'creado',
                    'updated' => 'actualizado',
                    'deleted' => 'eliminado',
                    default => $eventName
                };
                
                return "El tipo de prueba '{$this->nombre}' ha sido {$eventoTraducido}";
            })
            
            // Rastrear todos los campos en $fillable automáticamente
            ->logFillable() 
            
            ->logOnlyDirty() 
            ->dontSubmitEmptyLogs();
    }
}