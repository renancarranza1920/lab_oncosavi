<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrupoEtario extends Model
{
    use HasFactory;
    protected $table = 'grupos_etarios';
    protected $fillable = ['nombre', 'edad_min', 'edad_max', 'unidad_tiempo', 'genero', 'estado'];
}