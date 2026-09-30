<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
class Examen extends Model
{
    use HasFactory, LogsActivity;
    protected $table = "examens";
    protected $fillable = [
        'tipo_examen_id',
        'nombre',
        'es_externo',
        'precio',
        'recipiente',
        'estado',
    ];

    protected $casts = [
    'es_externo' => 'boolean', // <-- AÑADIDO
];
    public function tipoExamen()
    {
        return $this->belongsTo(TipoExamen::class, 'tipo_examen_id');
    }
    public function perfiles()
    {
        return $this->belongsToMany(Perfil::class, 'detalle_perfil', 'examen_id', 'perfil_id');
    }

    public function ordenes()
    {
        return $this->belongsToMany(Orden::class, 'detalle_orden_examens', 'examen_id', 'orden_id');
    }

      public function muestras()
    {
        return $this->belongsToMany(Muestra::class, 'examen_muestra', 'examen_id', 'muestra_id');
    }

    
    public function pruebas(): HasMany
{
    return $this->hasMany(Prueba::class);
}


   public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->uselogName('Exámenes')
            
            // --- ¡LÓGICA DE TRADUCCIÓN AÑADIDA! ---
            ->setDescriptionForEvent(function(string $eventName) {
                // Traducimos el nombre del evento
                $eventoTraducido = match($eventName) {
                    'created' => 'creado',
                    'updated' => 'actualizado',
                    'deleted' => 'eliminado',
                    default => $eventName
                };
                
                return "El examen '{$this->nombre}' ha sido {$eventoTraducido}";
            })
            // ------------------------------------
            
            ->logFillable() 
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
