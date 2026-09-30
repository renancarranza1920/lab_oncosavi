<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetallePerfil extends Model
{
    protected $table = 'detalle_perfil';

    protected $fillable = [
        'perfil_id',
        'examen_id',
        'precio'
    ];

    public function perfil()
    {
        return $this->belongsTo(Perfil::class);
    }

    public function examen()
    {
        return $this->belongsTo(Examen::class);
    }
}
