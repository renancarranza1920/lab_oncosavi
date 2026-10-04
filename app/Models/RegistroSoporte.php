<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistroSoporte extends Model
{
    protected $table = 'registros_soporte';
    protected $guarded = [];
    protected $casts = ['datos' => 'array', 'registrado_at' => 'datetime'];

    public function getConnectionName()
    {
        return config('activitylog.database_connection') ?: parent::getConnectionName();
    }
}
