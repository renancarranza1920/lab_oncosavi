<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class RetirarUsuariosPrueba
{
    private const CUENTAS = ['prueba.lab', 'prueba.recepcion', 'prueba.laboratorista'];

    private const NOMBRES = ['Prueba Lab', 'Prueba Recepcion', 'Prueba Recepción', 'Prueba Laboratorista'];

    public function eliminar(): array
    {
        return DB::transaction(function (): array {
            $usuarios = User::query()
                ->where(function (Builder $query): void {
                    $query->whereIn('name', self::NOMBRES);
                    foreach (self::CUENTAS as $nickname) {
                        $query->orWhere(fn (Builder $q) => $q->where('nickname', $nickname)
                            ->where('email', $nickname.'@oncosavi.test'));
                    }
                })
                ->where(fn (Builder $q) => $q->whereNull('nickname')
                    ->orWhereNotIn('nickname', ['prueba.admin', 'oncosavi', 'saulmerino']))
                ->whereDoesntHave('roles', fn (Builder $q) => $q->whereRaw('LOWER(name) = ?', ['admin']))
                ->lockForUpdate()->get();

            foreach ($usuarios as $usuario) {
                // Se conservan los registros clínicos y sus PDFs; no se atribuyen al admin.
                DB::table('ordens')->where('toma_muestra_user_id', $usuario->id)->update(['toma_muestra_user_id' => null]);
                DB::table('resultados')->where('user_id', $usuario->id)->update(['user_id' => null]);
                DB::table('sessions')->where('user_id', $usuario->id)->delete();
                DB::table('password_reset_tokens')->where('email', $usuario->email)->delete();
                // Los eventos del modelo conservan la bitácora y retiran solo sus asignaciones.
                $usuario->delete();
            }

            return $usuarios->pluck('nickname')->all();
        });
    }

    public function limpiarCredenciales(): void
    {
        $disk = Storage::disk('local');
        if (! $disk->exists('usuarios-prueba.json')) {
            return;
        }

        $cuentas = json_decode($disk->get('usuarios-prueba.json'), true, flags: JSON_THROW_ON_ERROR);
        $vigentes = array_values(array_filter($cuentas, fn (array $cuenta) =>
            User::where('nickname', $cuenta['usuario'])->exists()
        ));
        if ($vigentes === []) {
            if (! $disk->delete('usuarios-prueba.json')) {
                throw new RuntimeException('No se pudo retirar el archivo de credenciales de prueba.');
            }
        } elseif (! $disk->put('usuarios-prueba.json', json_encode($vigentes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            throw new RuntimeException('No se pudo actualizar el archivo de credenciales de prueba.');
        } else {
            chmod($disk->path('usuarios-prueba.json'), 0600);
        }
    }
}
