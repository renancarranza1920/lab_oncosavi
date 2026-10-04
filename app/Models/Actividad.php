<?php

namespace App\Models;

use App\Support\Bitacora;
use Spatie\Activitylog\Models\Activity;

class Actividad extends Activity
{
    public const EVENTO_SOPORTE = 'support_adjustment';

    public function save(array $options = []): bool
    {
        if (!$this->exists && $this->causer instanceof User && $this->causer->hasRole('super_admin')) {
            return $this->guardarProtegida($options);
        }

        return parent::save($options);
    }

    public function protegerRegistroExistente(): bool
    {
        if ($this->event === self::EVENTO_SOPORTE) {
            return false;
        }
        $timestamps = $this->timestamps;
        $this->timestamps = false;
        try {
            return $this->guardarProtegida();
        } finally {
            $this->timestamps = $timestamps;
        }
    }

    private function guardarProtegida(array $options = []): bool
    {
        return $this->getConnection()->transaction(function () use ($options): bool {
            $datos = $this->getAttributes();
            $datos['properties'] = Bitacora::datosVisibles($this->properties?->all() ?? []);
            $nombre = $this->causer?->name ?? 'Soporte técnico';
            $fecha = $this->created_at ?? now();
            $this->forceFill([
                'log_name' => 'Soporte técnico', 'event' => self::EVENTO_SOPORTE,
                'description' => 'Ajuste de soporte técnico', 'properties' => collect(),
                'subject_type' => null, 'subject_id' => null,
            ]);
            if (!parent::save($options)) {
                return false;
            }
            RegistroSoporte::create([
                'activity_id' => $this->id, 'nombre_usuario' => $nombre,
                'registrado_at' => $fecha, 'datos' => $datos,
            ]);
            return true;
        });
    }
}
