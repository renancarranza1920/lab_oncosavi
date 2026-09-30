<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

// 1. IMPORTAR LAS CLASES DE SPATIE
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Codigo extends Model
{
    // 2. USAR LOS TRAITS
    use HasFactory, LogsActivity;

    protected $fillable = [
        'codigo', 'tipo_descuento', 'valor_descuento',
        'es_limitado', // Nuevo
        'limite_usos',
        'usos_actuales',
        'tiene_vencimiento', // Nuevo
        'fecha_vencimiento', 'estado',
    ];

    protected $casts = [
        'fecha_vencimiento' => 'date',
        'es_limitado' => 'boolean', // Nuevo
        'tiene_vencimiento' => 'boolean', // Nuevo
    ];

    public function esValido(): bool
    {
        if ($this->estado !== 'Activo') return false;

        // Ahora la lógica revisa primero la bandera
        if ($this->tiene_vencimiento && $this->fecha_vencimiento && Carbon::parse($this->fecha_vencimiento)->isPast()) {
            $this->update(['estado' => 'Inactivo']);
            return false;
        }

        // Y aquí también
        if ($this->es_limitado && !is_null($this->limite_usos) && $this->usos_actuales >= $this->limite_usos) {
            $this->update(['estado' => 'Inactivo']);
            return false;
        }

        return true;
    }

protected static function booted()
    {
        static::creating(function ($codigo) {
            $codigo->estado = 'Activo';
            $codigo->usos_actuales = 0;
        });

        // --- AQUI AGREGAMOS LA LÓGICA DE REACTIVACIÓN ---
        static::updating(function ($codigo) {
            // Solo ejecutamos esto si se tocaron campos sensibles
            if ($codigo->isDirty(['limite_usos', 'fecha_vencimiento', 'es_limitado', 'tiene_vencimiento'])) {
                
                $cumpleLimite = true;
                $cumpleFecha = true;

                // 1. Revisar si ahora cumple el límite (o si se volvió ilimitado)
                if ($codigo->es_limitado && !is_null($codigo->limite_usos)) {
                    // Si Usos < Límite, entonces cumple
                    $cumpleLimite = $codigo->usos_actuales < $codigo->limite_usos;
                }

                // 2. Revisar si ahora cumple la fecha (o si se quitó el vencimiento)
                if ($codigo->tiene_vencimiento && $codigo->fecha_vencimiento) {
                    // Usamos endOfDay() para que el cupón valga hasta el último segundo del día
                    $cumpleFecha = Carbon::parse($codigo->fecha_vencimiento)->endOfDay()->isFuture();
                }

                // 3. Si cumple ambas condiciones, ¡RESUCITALO!
                if ($cumpleLimite && $cumpleFecha) {
                    $codigo->estado = 'Activo';
                }
            }
        });
    }

    public function registrarUso(): void
    {
        // La lógica también se actualiza para usar la bandera
        if ($this->es_limitado) {
            $this->increment('usos_actuales');
            if (!is_null($this->limite_usos) && $this->usos_actuales >= $this->limite_usos) {
                $this->update(['estado' => 'Inactivo']);
            }
        }
    }

    public static function desactivarVencidos(): void
    {
        static::whereNotNull('fecha_vencimiento')
            ->where('fecha_vencimiento', '<', Carbon::today())
            ->where('estado', 'Activo')
            ->update(['estado' => 'Inactivo']);
    }

    public function aplicarDescuento(float $total): float
    {
        if ($this->tipo_descuento === 'porcentaje') {
            return max($total - ($total * $this->valor_descuento / 100), 0);
        }
        if ($this->tipo_descuento === 'monto') {
            return max($total - $this->valor_descuento, 0);
        }
        return $total;
    }

    // 3. AÑADIR EL MÉTODO DE CONFIGURACIÓN DE LA BITÁCORA
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            // ¡Corregido con tu sintaxis!
            ->useLogName('Cupones') 
            
            ->setDescriptionForEvent(function(string $eventName) {
                $eventoTraducido = match($eventName) {
                    'created' => 'creado',
                    'updated' => 'actualizado',
                    'deleted' => 'eliminado',
                    default => $eventName
                };
                
                return "El cupón '{$this->codigo}' ha sido {$eventoTraducido}";
            })
            
            // Rastrear todos los campos en $fillable automáticamente
            ->logFillable() 
            
            ->logOnlyDirty() 
            ->dontSubmitEmptyLogs();
    }
}
