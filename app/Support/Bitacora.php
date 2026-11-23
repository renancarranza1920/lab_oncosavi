<?php

namespace App\Support;

use App\Models\Actividad;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

class Bitacora
{
    // El filtro se aplica a la consulta, antes de paginar, buscar o abrir un detalle.
    // No es un scope global: la auditoría privada y las reparaciones conservan acceso.
    public static function visibles(Builder $query): Builder
    {
        return $query->whereNot(fn (Builder $reservados) => self::reservados($reservados));
    }

    public static function reservados(Builder $query): Builder
    {
        $tiposUsuario = array_unique([User::class, (new User)->getMorphClass()]);
        $tiposRol = array_unique([Role::class, (new Role)->getMorphClass()]);

        return $query->where(function (Builder $reservados) use ($tiposUsuario, $tiposRol): void {
            $reservados->where(fn (Builder $evento) => $evento->whereNotNull('event')->where('event', Actividad::EVENTO_SOPORTE))
                ->orWhere(fn (Builder $autor) => $autor->whereNotNull('causer_type')->whereNotNull('causer_id')
                    ->whereIn('causer_type', $tiposUsuario)
                    ->whereIn('causer_id', AccesoSoporte::cuentasReservadas(User::query())->select('users.id')))
                ->orWhere(fn (Builder $usuario) => $usuario->whereNotNull('subject_type')->whereNotNull('subject_id')
                    ->whereIn('subject_type', $tiposUsuario)
                    ->whereIn('subject_id', AccesoSoporte::cuentasReservadas(User::query())->select('users.id')))
                ->orWhere(fn (Builder $rol) => $rol->whereNotNull('subject_type')->whereNotNull('subject_id')
                    ->whereIn('subject_type', $tiposRol)
                    ->whereIn('subject_id', AccesoSoporte::rolesReservados(Role::query())->select('roles.id')));
        });
    }

    public static function esSoporte(Activity $actividad): bool
    {
        return $actividad->event === Actividad::EVENTO_SOPORTE
            || ($actividad->causer instanceof User && AccesoSoporte::esCuentaReservada($actividad->causer))
            || ($actividad->subject instanceof User && AccesoSoporte::esCuentaReservada($actividad->subject))
            || ($actividad->subject instanceof Role && AccesoSoporte::rolReservado($actividad->subject->name));
    }

    public static function datosParaMostrar(array $datos): array
    {
        return array_map(static fn ($valor) => is_array($valor)
            ? json_encode($valor, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : (is_bool($valor) ? ($valor ? 'Sí' : 'No') : $valor), self::datosVisibles($datos));
    }

    public static function datosVisibles(array $datos): array
    {
        foreach ($datos as $campo => $valor) {
            if (preg_match('/password|token|secret|private.?key/i', (string) $campo)) {
                unset($datos[$campo]);
            } elseif (is_array($valor)) {
                $datos[$campo] = self::datosVisibles($valor);
            }
        }

        return $datos;
    }
}
