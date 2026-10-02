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
        'telefonos',
        'correo',
        'direccion',
        'estado',
    ];

protected static function booted(): void
{
    static::saving(function (Cliente $cliente): void {
        $telefonos = collect($cliente->telefonos ?? [])
            ->map(function ($telefono) {
                $telefono = is_array($telefono) ? $telefono : ['numero' => $telefono];
                $normalizado = self::normalizarTelefonoPorTipo(
                    $telefono['tipo'] ?? null,
                    $telefono['numero'] ?? '',
                );

                return $normalizado;
            })
            ->filter()
            ->unique(fn (array $telefono) => $telefono['tipo'] . ':' . $telefono['numero'])
            ->values()
            ->all();

        if ($telefonos !== []) {
            $cliente->telefonos = $telefonos;
            $cliente->telefono = self::numeroCompleto($telefonos[0]);
        }
    });

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

    public static function normalizarTelefono(?string $telefono): ?string
    {
        $telefono = trim((string) $telefono);
        $esInternacional = str_starts_with($telefono, '+');
        $digitos = preg_replace('/\D/', '', $telefono);

        if ($digitos === '') {
            return null;
        }

        return $esInternacional ? '+' . $digitos : $digitos;
    }

    public static function normalizarTelefonoPorTipo(?string $tipo, ?string $numero): ?array
    {
        $normalizado = self::normalizarTelefono($numero);

        if (!$normalizado) {
            return null;
        }

        $digitos = preg_replace('/\D/', '', $normalizado);
        $tipo ??= str_starts_with($normalizado, '+1') && !str_starts_with($normalizado, '+503')
            ? 'us'
            : (str_starts_with($normalizado, '+503') ? 'sv' : 'fijo');

        if ($tipo === 'sv') {
            $digitos = str_starts_with($digitos, '503') ? substr($digitos, 3) : $digitos;
        } elseif ($tipo === 'us') {
            $digitos = str_starts_with($digitos, '1') && strlen($digitos) === 11 ? substr($digitos, 1) : $digitos;
        }

        return ['tipo' => $tipo, 'numero' => $digitos];
    }

    public static function numeroCompleto(array $telefono): string
    {
        return match ($telefono['tipo'] ?? 'fijo') {
            'sv' => '+503' . $telefono['numero'],
            'us' => '+1' . $telefono['numero'],
            default => $telefono['numero'],
        };
    }

    public function getTelefonosAttribute($value): array
    {
        $telefonos = is_array($value) ? $value : (json_decode($value ?: '[]', true) ?: []);

        return collect($telefonos)
            ->map(fn ($telefono) => self::normalizarTelefonoPorTipo(
                is_array($telefono) ? ($telefono['tipo'] ?? null) : null,
                is_array($telefono) ? ($telefono['numero'] ?? '') : $telefono,
            ))
            ->filter()
            ->values()
            ->all();
    }

    public function setTelefonosAttribute($value): void
    {
        $this->attributes['telefonos'] = $value === null ? null : json_encode($value);
    }

    public static function formatearTelefono(?string $telefono): ?string
    {
        $telefono = self::normalizarTelefono($telefono);

        if (!$telefono) {
            return null;
        }

        $digitos = preg_replace('/\D/', '', $telefono);

        if (str_starts_with($telefono, '+1') && strlen($digitos) === 11) {
            return sprintf('+1 (%s) %s-%s', substr($digitos, 1, 3), substr($digitos, 4, 3), substr($digitos, 7, 4));
        }

        if (str_starts_with($telefono, '+503') && strlen($digitos) === 11) {
            return '+503 ' . substr($digitos, 3, 4) . '-' . substr($digitos, 7, 4);
        }

        if (!str_starts_with($telefono, '+') && strlen($digitos) === 8) {
            return substr($digitos, 0, 4) . '-' . substr($digitos, 4, 4);
        }

        return $telefono;
    }

    public function getTelefonosRegistradosAttribute(): array
    {
        $telefonos = $this->telefonos ?: ($this->telefono ? [['numero' => $this->telefono]] : []);

        return collect($telefonos)
            ->map(function ($telefono) {
                $normalizado = self::normalizarTelefonoPorTipo(
                    is_array($telefono) ? ($telefono['tipo'] ?? null) : null,
                    is_array($telefono) ? ($telefono['numero'] ?? '') : $telefono,
                );

                return $normalizado ? self::formatearTelefono(self::numeroCompleto($normalizado)) : null;
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function telefonosParaWhatsapp(): array
    {
        return collect($this->telefonos_registrados)
            ->mapWithKeys(function (string $telefono): array {
                $digitos = preg_replace('/\D/', '', $telefono);
                $destino = str_starts_with($telefono, '+') ? $digitos : '503' . $digitos;

                return [$destino => $telefono];
            })
            ->all();
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
