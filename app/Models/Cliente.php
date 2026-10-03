<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Orden;
use App\Models\GrupoEtario;
use Carbon\Carbon;

class Cliente extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'clientes';
    
    // Agregamos 'edad' al fillable como pediste
    protected $fillable = [
        'NumeroExp',
        'nombre',
        'apellido',
        'dui',
        'edad', 
        'fecha_nacimiento',
        'grupo_etario',
        'genero',
        'telefono',
        'correo',
        'direccion',
        'estado',
    ];

protected static function booted(): void
{
    static::creating(function ($cliente) {

        // Generar prefijo (iniciales + año)
        $prefijo = strtoupper(substr($cliente->nombre, 0, 1) . substr($cliente->apellido, 0, 1));
        $año = date('y');
        $base = $prefijo . $año;

        // Obtener el último registro
        $ultimo = self::where('NumeroExp', 'LIKE', "$base%")
            ->orderBy('NumeroExp', 'desc')
            ->first();

        if ($ultimo) {
            $numero = (int) substr($ultimo->NumeroExp, -3);
            $correlativo = str_pad($numero + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $correlativo = '001';
        }

        // Asignar SIEMPRE el nuevo número
        $cliente->NumeroExp = $base . $correlativo;

        // Estado por defecto
        if (!$cliente->estado) {
            $cliente->estado = 'Activo';
        }
    });
}

    public function ordenes(): HasMany
    {
        return $this->hasMany(Orden::class);
    }

    public function setDuiAttribute($value): void
    {
        $digits = preg_replace('/\D/', '', (string) $value);

        $this->attributes['dui'] = $digits === ''
            ? null
            : substr($digits, 0, 8) . '-' . substr($digits, 8, 1);
    }


    public function getActivitylogOptions(): LogOptions

    {

        return LogOptions::defaults()

            ->uselogName('Clientes') // Nombre del módulo en la bitácora

            

            // Descripción más útil y en español

            ->setDescriptionForEvent(function(string $eventName) {

                $eventoTraducido = match($eventName) {

                    'created' => 'creado',

                    'updated' => 'actualizado',

                    'deleted' => 'eliminado',

                    default => $eventName

                };

                

                return "El cliente '{$this->nombre} {$this->apellido}' (ID: {$this->id}) ha sido {$eventoTraducido}";

            })

            

            // Rastrear todos los campos en $fillable automáticamente

            ->logFillable() 

            

            // Registrar solo los campos que realmente cambiaron

            ->logOnlyDirty() 

            

            // No guardar logs vacíos (ej. si solo se toca 'updated_at')

            ->dontSubmitEmptyLogs();

    }



    /**
     * Obtiene el grupo etario basado en:
     * 1. Fecha de nacimiento (Cálculo exacto)
     * 2. ID de grupo etario seleccionado
     * 3. Edad manual ingresada (Fallback en años)
     */
    public function getGrupoEtario(): ?GrupoEtario
    {
        // CASO 1: Si tiene fecha de nacimiento, calculamos con precisión
        if ($this->fecha_nacimiento) {
            $fechaNacimiento = Carbon::parse($this->fecha_nacimiento);
            $ahora = Carbon::now();
            
            // Calcular edades en distintas unidades
            $edadDias = $fechaNacimiento->diffInDays($ahora);
            $edadMeses = $fechaNacimiento->diffInMonths($ahora);
            $edadAnios = $fechaNacimiento->diffInYears($ahora);

            // 1.1 INTENTO POR DÍAS (Neonatos)
            $grupo = GrupoEtario::where('unidad_tiempo', 'días')
                ->where('edad_min', '<=', $edadDias)
                ->where('edad_max', '>=', $edadDias)
                ->whereIn('genero', [$this->genero, 'Ambos'])
                ->first();

            if ($grupo) return $grupo;

            // 1.2 INTENTO POR MESES (Bebés)
            $grupo = GrupoEtario::where('unidad_tiempo', 'meses')
                ->where('edad_min', '<=', $edadMeses)
                ->where('edad_max', '>=', $edadMeses)
                ->whereIn('genero', [$this->genero, 'Ambos'])
                ->first();

            if ($grupo) return $grupo;

            // 1.3 INTENTO POR AÑOS (Estándar)
            $grupo = GrupoEtario::where('unidad_tiempo', 'años')
                ->where('edad_min', '<=', $edadAnios)
                ->where('edad_max', '>=', $edadAnios)
                ->whereIn('genero', [$this->genero, 'Ambos'])
                ->first();

            return $grupo;
        }

        // CASO 2: Si NO tiene fecha, ver si tiene un ID de grupo etario seleccionado manualmente
        if ($this->grupo_etario) {
            $grupo = GrupoEtario::find($this->grupo_etario);
            // Si lo encuentra, retornarlo. Si no (ej. borraron el grupo), sigue al siguiente paso.
            if ($grupo) return $grupo;
        }

        // CASO 3: Si NO tiene fecha NI grupo seleccionado, ver si tiene EDAD manual
        // Asumimos que la edad manual ingresada es en "AÑOS"
        if ($this->edad) {
            return GrupoEtario::where('unidad_tiempo', 'años')
                ->where('edad_min', '<=', $this->edad)
                ->where('edad_max', '>=', $this->edad)
                ->whereIn('genero', [$this->genero, 'Ambos'])
                ->first();
        }

        return null;
    }
    // En app/Models/Cliente.php

        public function getEdadLegibleAttribute()
        {
            // Si no hay fecha, devolvemos lo que haya en el campo 'edad' manual
            if (!$this->fecha_nacimiento) {
                return $this->edad . ' años';
            }
        
            $nacimiento = \Carbon\Carbon::parse($this->fecha_nacimiento);
            $ahora = \Carbon\Carbon::now();
        
            // Calculamos las diferencias como ENTEROS (int)
            $anios = (int) $nacimiento->diffInYears($ahora);
            $meses = (int) $nacimiento->diffInMonths($ahora);
            $dias = (int) $nacimiento->diffInDays($ahora);
        
            // LÓGICA DE PRIORIDAD:
            if ($anios > 0) {
                return $anios . ' años'; // Ej: 30 años
            } elseif ($meses > 0) {
                return $meses . ' meses'; // Ej: 5 meses (Melody)
            } else {
                return $dias . ' días'; // Ej: 3 días (Recién nacido)
            }
        }
}
