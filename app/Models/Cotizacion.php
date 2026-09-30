<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cotizacion extends Model
{
 // Evita que intente buscar tabla
    protected $table = 'cotizacion_dummy';
    public $timestamps = false;
    protected $guarded = [];
}
