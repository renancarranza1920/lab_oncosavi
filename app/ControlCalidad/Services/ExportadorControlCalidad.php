<?php

namespace App\ControlCalidad\Services;

use App\ControlCalidad\Models\Area;
use App\ControlCalidad\Models\Lote;
use App\ControlCalidad\Models\Nivel;
use App\ControlCalidad\Models\Prueba;
use App\Models\User;
use App\Support\ControlCalidadPermissions as Cci;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ExportadorControlCalidad
{
    public function generar(string $tipo, array $params, User $user)
    {
        abort_unless($user->can('access_admin_panel') && $user->can(Cci::VIEW) && $user->can(Cci::EXPORT), 403);
        $permission = match ($tipo) {
            'resumen' => Cci::VIEW, 'catalogo' => Cci::MAINTAIN, 'registros' => Cci::REGISTER,
            'historial' => Cci::HISTORY, 'graficos', 'nivel' => Cci::CHARTS,
            'registro' => null, default => abort(404),
        };
        abort_unless($permission === null ? ($user->can(Cci::HISTORY) || $user->can(Cci::EDIT) || $user->can(Cci::DELETE)) : $user->can($permission), 403);
        abort_if($tipo === 'registros' && ! ($user->can(Cci::HISTORY) || $user->can(Cci::EDIT) || $user->can(Cci::DELETE)), 403);
        $valid = Validator::make($params, [
            'seccion' => ['nullable', Rule::in(['areas', 'productos', 'pruebas'])],
            'registro_id' => [Rule::requiredIf($tipo === 'registro'), 'nullable', 'integer', 'exists:cci_registros,id'],
            'nivel_id' => [Rule::requiredIf($tipo === 'nivel'), 'nullable', 'integer', 'exists:cci_niveles,id'],
            'prueba_id' => [Rule::requiredIf($tipo === 'graficos'), 'nullable', 'integer', 'exists:cci_pruebas,id'],
            'incluir_graficos' => ['nullable', 'boolean'],
        ])->validate();
        $filtros = app(ConsultaControlCalidad::class)->filtros($params);
        $incluirGraficos = (bool) ($valid['incluir_graficos'] ?? false);
        abort_if($incluirGraficos && ! $user->can(Cci::CHARTS), 403);
        $titulos = ['resumen' => 'Resumen', 'catalogo' => 'Mantenimiento', 'registros' => 'Últimos registros', 'historial' => 'Historial', 'registro' => 'Registro de control', 'graficos' => 'Gráficos', 'nivel' => 'Gráfico de nivel'];
        $tablas = [];
        $graficos = [];
        if ($tipo === 'catalogo') {
            $tablas = $this->catalogo($valid['seccion'] ?? 'areas');
        } else {
            if ($tipo === 'resumen') {
                $filtros['desde'] ??= now()->subDays(30)->toDateString();
                $filtros['hasta'] ??= now()->toDateString();
            }
            $query = app(ConsultaControlCalidad::class)->registros($filtros);
            if ($tipo === 'registro') {
                $query->whereKey($valid['registro_id']);
            }
            if ($tipo === 'graficos') {
                $query->whereHas('resultados', fn ($q) => $q->where('prueba_id', $valid['prueba_id']));
            }
            if ($tipo === 'nivel') {
                $query->whereHas('resultados', fn ($q) => $q->where('nivel_id', $valid['nivel_id']));
            }
            $limit = match ($tipo) {
                'resumen' => 8, 'registros' => 10, 'registro' => 1, default => 300
            };
            if ($query->count() > $limit && ! in_array($tipo, ['resumen', 'registros'], true)) {
                throw ValidationException::withMessages(['desde' => 'Selecciona un período de hasta 300 registros para este PDF.']);
            }
            $registros = $query->limit($limit)->get();
            abort_if($tipo === 'registro' && $registros->isEmpty(), 404);
            foreach ($registros as $registro) {
                $rows = [];
                foreach ($registro->resultados as $r) {
                    if ($tipo === 'graficos' && $r->prueba_id !== (int) $valid['prueba_id']) {
                        continue;
                    }
                    if ($tipo === 'nivel' && $r->nivel_id !== (int) $valid['nivel_id']) {
                        continue;
                    }
                    $rows[] = [$r->prueba_nombre, $r->nivel_nombre, $r->valor_original, $r->unidad,
                        $this->numero($r->minimo).' — '.$this->numero($r->maximo)."\nEsperado: ".$this->numero($r->esperado),
                        $r->lote_numero, bccomp($r->valor, $r->minimo, 6) < 0 || bccomp($r->valor, $r->maximo, 6) > 0 ? 'Fuera de rango' : 'Dentro de rango'];
                }
                $tablas[] = ['titulo' => '#'.$registro->id.' · '.$registro->fecha_hora->format('d/m/Y H:i').' · '.$registro->area->nombre.' · '.$registro->area->equipo,
                    'detalle' => 'Laboratorista: '.($registro->laboratorista?->name ?? '—'), 'nota' => $registro->observaciones,
                    'columnas' => ['Prueba', 'Nivel', 'Resultado', 'Unidad', 'Valores de control', 'Lote', 'Evaluación'], 'filas' => $rows];
            }
            if ($incluirGraficos || in_array($tipo, ['graficos', 'nivel'], true)) {
                $nivelIds = $registros->flatMap->resultados->pluck('nivel_id')->unique()->all();
                $niveles = Nivel::with('prueba')->whereIn('id', $nivelIds)
                    ->when($tipo === 'graficos', fn ($q) => $q->where('prueba_id', $valid['prueba_id']))
                    ->when($tipo === 'nivel', fn ($q) => $q->whereKey($valid['nivel_id']))
                    ->orderBy('prueba_id')->orderBy('orden')->get();
                foreach ($niveles as $nivel) {
                    $data = app(DatosGraficoControlCalidad::class)->obtener($nivel->id, $filtros['desde'] ?? null, $filtros['hasta'] ?? null, $registros->modelKeys());
                    foreach (app(GraficoControlCalidadSvg::class)->imagenes($data) as $image) {
                        $graficos[] = $image + ['titulo' => $nivel->prueba->nombre.' · '.$nivel->nombre];
                    }
                }
            }
        }

        return Pdf::setOptions(['isRemoteEnabled' => false, 'chroot' => base_path(), 'defaultFont' => 'DejaVu Sans'])
            ->loadView('pdf.control-calidad.exportacion', ['titulo' => $titulos[$tipo], 'tablas' => $tablas, 'graficos' => $graficos,
                'filtros' => $filtros, 'generado' => now()->format('d/m/Y H:i'), 'usuario' => $user->name])
            ->setPaper('a4', 'landscape');
    }

    private function catalogo(string $seccion): array
    {
        $rows = [];
        if ($seccion === 'areas') {
            $columns = ['Área', 'Equipo'];
            foreach (Area::orderBy('nombre')->get() as $a) {
                $rows[] = [$a->nombre, $a->equipo];
            }
        } elseif ($seccion === 'productos') {
            $columns = ['Producto de control', 'Número de lote', 'Caducidad'];
            foreach (Lote::with('producto')->orderBy('numero')->get() as $l) {
                $rows[] = [$l->producto->nombre, $l->numero, $l->caduca_el->format('d/m/Y')];
            }
        } else {
            $columns = ['Área y equipo', 'Prueba', 'Unidad', 'Nivel', 'Mínimo', 'Máximo', 'Esperado', 'Producto y lote'];
            foreach (Prueba::with(['area', 'niveles.valorControlActual.lote.producto'])->orderBy('nombre')->get() as $p) {
                foreach ($p->niveles->sortBy('orden') as $n) {
                    $v = $n->valorControlActual;
                    $rows[] = [$p->area->nombre.' · '.$p->area->equipo, $p->nombre, $p->unidad, $n->nombre,
                        $this->numero($v?->minimo), $this->numero($v?->maximo), $this->numero($v?->esperado),
                        $v?->lote?->producto?->nombre.' · '.$v?->lote?->numero];
                }
            }
        }

        return [['titulo' => match ($seccion) {
            'areas' => 'Áreas', 'productos' => 'Productos y lotes', default => 'Pruebas y niveles'
        }, 'columnas' => $columns, 'filas' => $rows]];
    }

    private function numero(mixed $value): string
    {
        if ($value === null) {
            return '—';
        }
        $text = (string) $value;

        return str_replace('.', ',', str_contains($text, '.') ? rtrim(rtrim($text, '0'), '.') : $text);
    }
}
