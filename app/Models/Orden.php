<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory; // <-- AÑADIR
use Spatie\Activitylog\Traits\LogsActivity; // <-- AÑADIR
use Spatie\Activitylog\LogOptions; // <-- AÑADIR
use App\Models\User; // <-- AÑADIR
use App\Models\Codigo; // <-- AÑADIR

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Orden extends Model
{
    use HasFactory, LogsActivity; // <-- AÑADIR
    
    protected $table = 'ordens';
    protected $primaryKey = 'id';
    
    protected $fillable = [
        'cliente_id',
        'total',
        'descuento', // <-- AÑADIR
        'codigo_id', // <-- AÑADIR
        'fecha',
        'observaciones',
        'observaciones_por_area',
        'estado',
        'muestras_recibidas', 
        'semanas_gestacion',
        'toma_muestra_user_id',
        'fecha_toma_muestra',
        'medico_id',
    ];

    protected $casts = [
        'muestras_recibidas' => 'array',
        'fecha_toma_muestra' => 'datetime',
         'fecha' => 'datetime',
         'observaciones_por_area' => 'array',
    ];
   

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function tomaMuestraUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'toma_muestra_user_id');
    }
    
    public function medico()
    {
        return $this->belongsTo(Medico::class);
    }

    // --- ¡NUEVA RELACIÓN! ---
    public function codigo(): BelongsTo
    {
        return $this->belongsTo(Codigo::class);
    }
    // --- FIN DEL BLOQUE ---

    public function detalleOrden()
    {
        return $this->hasMany(DetalleOrden::class);
    }

    public function resultados(): HasManyThrough
    {
        return $this->hasManyThrough(Resultado::class, DetalleOrden::class);
    }

    public function reporteGuardadoFileName(bool $parcial = false): string
    {
        $cliente = $this->relationLoaded('cliente')
            ? $this->cliente
            : $this->cliente()->first(['id', 'nombre', 'apellido']);

        $nombreCliente = trim(($cliente?->nombre ?? '') . ' ' . ($cliente?->apellido ?? ''));
        $nombreCliente = $nombreCliente !== '' ? Str::slug($nombreCliente) : 'sin-nombre';
        $suffix = $parcial ? ' P' : '';

        return strtoupper("{$nombreCliente} - {$this->id}{$suffix}.pdf");
    }

    public function reporteGuardadoPath(bool $parcial = false): string
    {
        return 'reportes/' . $this->reporteGuardadoFileName($parcial);
    }

    public function reporteGuardadoExists(bool $parcial = false): bool
    {
        return Storage::disk('public')->exists($this->reporteGuardadoPath($parcial));
    }

    public function reporteGuardadoFullPath(bool $parcial = false): string
    {
        return Storage::disk('public')->path($this->reporteGuardadoPath($parcial));
    }
    // 3. AÑADIR EL MÉTODO DE CONFIGURACIÓN DE LA BITÁCORA
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('Órdenes') // Nombre del módulo en la bitácora
            
            ->setDescriptionForEvent(function(string $eventName) {
                // Traducimos el evento
                $eventoTraducido = match($eventName) {
                    'created' => 'creada',
                    'updated' => 'actualizada',
                    'deleted' => 'eliminada',
                    default => $eventName
                };
                
                // Usamos el ID de la orden para la descripción
                return "La orden #{$this->id} ha sido {$eventoTraducido}";
            })
            
            // Rastrear todos los campos en $fillable automáticamente
            // Esto incluye 'estado', 'total', 'observaciones', etc.
            ->logFillable() 
            
            // Guardar el "antes" y "después"
            ->logOnlyDirty() 
            ->dontSubmitEmptyLogs();
    }
}
