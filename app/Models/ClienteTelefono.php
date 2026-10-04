<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClienteTelefono extends Model
{
    protected $table = 'cliente_telefonos';

    protected $fillable = ['numero', 'codigo_pais', 'tipo', 'orden'];

    protected function casts(): array
    {
        return ['orden' => 'integer'];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    protected static function booted(): void
    {
        $sincronizar = function (ClienteTelefono $telefono): void {
            $telefono->cliente?->sincronizarTelefonoPrincipal();
        };
        static::saved($sincronizar);
        static::deleted($sincronizar);
    }
}
