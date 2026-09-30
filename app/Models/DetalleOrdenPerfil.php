<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleOrdenPerfil extends Model
{
    // Define el nombre de la tabla
    protected $table = 'detalle_orden_perfils';
    // Define los campos que se pueden llenar
    protected $fillable = [
        'orden_id',
        'perfil_id',
    ];

    // Relación con la orden
    public function orden()
    {
        return $this->belongsTo(Orden::class);
    }
    // Relación con el perfil
    public function perfil()
    {
        return $this->belongsTo(Perfil::class);
    }

}

 