<?php

namespace App\ControlCalidad\Filament\Concerns;

use App\ControlCalidad\Models\Area;
use App\ControlCalidad\Models\Nivel;
use App\ControlCalidad\Models\Registro as ControlRegistro;
use App\ControlCalidad\Services\ControlCalidadService;
use App\Support\ControlCalidadPermissions as Permission;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

trait InteractsWithControlRecords
{
    protected array $nivelDescriptions = [];

    protected function esquemaRegistro(?ControlRegistro $registro = null): array
    {
        return [
            Section::make('Datos del control')->schema([
                Select::make('area_id')->label('Área')->required()->searchable()->native(false)
                    ->options(fn (): array => Area::query()->orderBy('nombre')->get()
                        ->mapWithKeys(fn (Area $area): array => [$area->id => $area->nombre.' · '.$area->equipo])->all())
                    ->disabled($registro !== null)->dehydrated()
                    ->live()->afterStateUpdated(function (Set $set, $state) use ($registro): void {
                        if ($registro === null) {
                            $set('resultados', $this->filasDelArea($state));
                        }
                    }),
            ]),
            Section::make('Registro de las pruebas')->schema([
                Repeater::make('resultados')->label('Lecturas')->required()->minItems(1)
                    ->addable(false)->deletable(false)->reorderable(false)
                    ->itemLabel(fn (array $state): string => $this->descripcionNivel((int) ($state['nivel_id'] ?? 0), $registro)['titulo'])
                    ->schema([
                        Hidden::make('nivel_id')->required()->rules(['integer']),
                        Placeholder::make('unidad')->label('Unidad')->content(fn (Get $get): string =>
                            $this->descripcionNivel((int) $get('nivel_id'), $registro)['unidad']),
                        Placeholder::make('rango')->label('Valores de control')->content(fn (Get $get): string =>
                            $this->descripcionNivel((int) $get('nivel_id'), $registro)['rango']),
                        TextInput::make('valor')->label('Resultado')->required()->inputMode('decimal')
                            ->maxLength(40)->autocomplete(false)
                            ->regex('/^[+-]?\d+(?:[.,]\d{1,6})?$/D')
                            ->validationMessages(['regex' => 'Escribe un número con coma o punto decimal.']),
                    ])->columns(['default' => 1, 'md' => 3]),
            ]),
            Section::make('Observaciones')->schema([
                Textarea::make('observaciones')->label('Observaciones (opcional)')->rows(3)->maxLength(4000),
            ]),
        ];
    }

    protected function filasDelArea(mixed $areaId): array
    {
        if (! is_scalar($areaId) || ! ctype_digit((string) $areaId)) {
            return [];
        }

        return Nivel::query()->whereHas('prueba', fn (Builder $query) => $query->where('area_id', (int) $areaId))
            ->whereHas('valorControlActual')->with('prueba')->orderBy('prueba_id')->orderBy('orden')->orderBy('id')
            ->get()->map(fn (Nivel $nivel): array => ['nivel_id' => $nivel->id, 'valor' => ''])->all();
    }

    protected function datosDelRegistro(ControlRegistro $registro): array
    {
        $registro->loadMissing('resultados');

        return [
            'area_id' => $registro->area_id,
            'observaciones' => $registro->observaciones,
            'resultados' => $registro->resultados->map(fn ($resultado): array => [
                'nivel_id' => $resultado->nivel_id,
                'valor' => $resultado->valor_original,
            ])->all(),
        ];
    }

    protected function descripcionNivel(int $nivelId, ?ControlRegistro $registro): array
    {
        $key = ($registro?->id ?? 'nuevo').':'.$nivelId;
        if (isset($this->nivelDescriptions[$key])) {
            return $this->nivelDescriptions[$key];
        }

        $resultado = $registro?->resultados->firstWhere('nivel_id', $nivelId);
        if ($resultado !== null) {
            return $this->nivelDescriptions[$key] = [
                'titulo' => $resultado->prueba_nombre.' · '.$resultado->nivel_nombre,
                'unidad' => $resultado->unidad,
                'rango' => $this->numeroVisible($resultado->minimo).' — '.$this->numeroVisible($resultado->maximo)
                    .' · Esperado '.$this->numeroVisible($resultado->esperado),
            ];
        }

        $nivel = Nivel::query()->with(['prueba', 'valorControlActual'])->find($nivelId);
        $control = $nivel?->valorControlActual;

        return $this->nivelDescriptions[$key] = [
            'titulo' => $nivel === null ? 'Lectura' : $nivel->prueba->nombre.' · '.$nivel->nombre,
            'unidad' => $nivel?->prueba?->unidad ?? '—',
            'rango' => $control === null ? 'Sin valores de control' : $this->numeroVisible($control->minimo)
                .' — '.$this->numeroVisible($control->maximo).' · Esperado '.$this->numeroVisible($control->esperado),
        ];
    }

    private function numeroVisible(mixed $value): string
    {
        if ($value === null) {
            return '—';
        }

        $text = (string) $value;
        if (str_contains($text, '.')) {
            $text = rtrim(rtrim($text, '0'), '.');
        }

        return $text === '-0' ? '0' : str_replace('.', ',', $text);
    }

    protected function tablaRegistros(Table $table, bool $historial = false): Table
    {
        $query = ControlRegistro::query()->with(['area', 'laboratorista', 'registradoPor']);
        if (! static::canAccess()) {
            $query->whereRaw('1 = 0');
        }
        if (! $historial) {
            $query->latest('fecha_hora')->limit(10);
        }

        return $table->query($query)->heading($historial ? 'Historial de registros' : 'Últimos registros')
            ->defaultSort('fecha_hora', 'desc')->recordUrl(null)->recordAction(null)
            ->columns([
                TextColumn::make('id')->label('Registro')->prefix('#')->sortable(),
                TextColumn::make('fecha_hora')->label('Fecha y hora')->dateTime('d/m/Y H:i', 'America/El_Salvador')->sortable(),
                TextColumn::make('area.nombre')->label('Área')->searchable(),
                TextColumn::make('area.equipo')->label('Equipo')->searchable()->wrap(),
                TextColumn::make('laboratorista.name')->label('Laboratorista')->searchable()->wrap()->visible($historial),
                TextColumn::make('estado')->label('Estado')->badge()
                    ->getStateUsing(fn (ControlRegistro $record): string => $record->trashed() ? 'Eliminado' : 'Registrado')
                    ->color(fn (ControlRegistro $record): string => $record->trashed() ? 'danger' : 'success'),
            ])->filters($historial ? [
                SelectFilter::make('area_id')->label('Área')
                    ->options(fn (): array => Area::query()->orderBy('nombre')->get()
                        ->mapWithKeys(fn (Area $area): array => [$area->id => $area->nombre.' · '.$area->equipo])->all()),
                SelectFilter::make('laboratorista_id')->label('Laboratorista')->relationship('laboratorista', 'name')->searchable(),
                Filter::make('fecha_hora')->label('Periodo')->form([
                    DatePicker::make('desde')->label('Desde'),
                    DatePicker::make('hasta')->label('Hasta')->afterOrEqual('desde'),
                ])->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when($data['desde'] ?? null, fn (Builder $query, $date): Builder =>
                            $query->where('fecha_hora', '>=', \Carbon\Carbon::parse($date, 'America/El_Salvador')->startOfDay()))
                        ->when($data['hasta'] ?? null, fn (Builder $query, $date): Builder =>
                            $query->where('fecha_hora', '<=', \Carbon\Carbon::parse($date, 'America/El_Salvador')->endOfDay()));
                }),
            ] : [])
            ->actions([
                ViewAction::make('ver')->label('Ver registro')->modalHeading(fn (ControlRegistro $record): string => 'Registro #'.$record->id)
                    ->authorize(fn (): bool => static::canAccess()
                        && (static::allowed(Permission::HISTORY) || static::allowed(Permission::EDIT) || static::allowed(Permission::DELETE)))
                    ->modalWidth('7xl')->infolist([
                        InfoSection::make('Datos del control')->columns(['default' => 1, 'md' => 3])->schema([
                            TextEntry::make('area.nombre')->label('Área'),
                            TextEntry::make('area.equipo')->label('Equipo'),
                            TextEntry::make('fecha_hora')->label('Fecha y hora')->dateTime('d/m/Y H:i', 'America/El_Salvador'),
                            TextEntry::make('laboratorista.name')->label('Laboratorista')->visible($historial),
                            TextEntry::make('registradoPor.name')->label('Registrado por')->visible($historial),
                        ]),
                        RepeatableEntry::make('resultados')->label('Resultados')->schema([
                            TextEntry::make('prueba_nombre')->label('Prueba'),
                            TextEntry::make('nivel_nombre')->label('Nivel'),
                            TextEntry::make('valor_original')->label('Resultado'),
                            TextEntry::make('unidad')->label('Unidad'),
                            TextEntry::make('lote_numero')->label('Lote'),
                            TextEntry::make('esperado')->label('Esperado')->formatStateUsing(fn ($state): string => $this->numeroVisible($state)),
                            TextEntry::make('minimo')->label('Mínimo')->formatStateUsing(fn ($state): string => $this->numeroVisible($state)),
                            TextEntry::make('maximo')->label('Máximo')->formatStateUsing(fn ($state): string => $this->numeroVisible($state)),
                        ])->columns(['default' => 1, 'md' => 4]),
                        TextEntry::make('observaciones')->label('Observaciones')->placeholder('Sin observaciones')->columnSpanFull(),
                    ]),
                Action::make('editar')->label('Editar')->icon('heroicon-o-pencil-square')->color('primary')
                    ->visible(fn (ControlRegistro $record): bool => ! $record->trashed() && static::allowed(Permission::EDIT))
                    ->authorize(fn (): bool => static::allowed(Permission::EDIT))
                    ->modalHeading('Editar registro')->modalWidth('7xl')->modalSubmitActionLabel('Guardar cambios')
                    ->fillForm(fn (ControlRegistro $record): array => $this->datosDelRegistro($record))
                    ->form(fn (ControlRegistro $record): array => $this->esquemaRegistro($record))
                    ->action(function (array $data, ControlRegistro $record): void {
                        abort_unless(static::allowed(Permission::EDIT), 403);
                        try {
                            app(ControlCalidadService::class)->guardar($data, auth()->user(), $record);
                        } catch (ValidationException $exception) {
                            Notification::make()->danger()->title('Revisa los datos ingresados')
                                ->body($exception->validator->errors()->first())->send();
                            throw $exception;
                        }
                        Notification::make()->title('Registro actualizado')->success()->send();
                    }),
                Action::make('eliminar')->label('Eliminar')->icon('heroicon-o-trash')->color('danger')
                    ->visible(fn (ControlRegistro $record): bool => ! $record->trashed() && static::allowed(Permission::DELETE))
                    ->authorize(fn (): bool => static::allowed(Permission::DELETE))
                    ->requiresConfirmation()->modalHeading('Eliminar registro')
                    ->modalDescription('El registro dejará de aparecer en el historial y en los gráficos.')
                    ->modalSubmitActionLabel('Eliminar registro')->action(function (ControlRegistro $record): void {
                        abort_unless(static::allowed(Permission::DELETE), 403);
                        app(ControlCalidadService::class)->eliminar($record, auth()->user());
                        Notification::make()->title('Registro eliminado')->success()->send();
                    }),
            ])->paginated($historial ? [10, 25, 50] : false)
            ->emptyStateHeading('No hay registros')->emptyStateDescription('Los controles guardados aparecerán aquí.');
    }
}
