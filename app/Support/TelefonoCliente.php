<?php

namespace App\Support;

use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;

class TelefonoCliente
{
    public static function campo(string $nombre = 'telefono', string $etiqueta = 'Teléfono', bool $obligatorio = false): Group
    {
        $codigo = $nombre . '_codigo_pais';

        return Group::make([
            Select::make($codigo)
                ->label('Código de país')
                ->options(self::paises())
                ->default('503')
                ->selectablePlaceholder(false)
                ->required()
                ->searchable()
                ->live()
                ->dehydrated(false),
            TextInput::make($nombre.'_codigo_otro')
                ->label('Código internacional')->prefix('+')->placeholder('Ej. 81')
                ->visible(fn (Get $get) => $get($codigo) === 'otro')
                ->required(fn (Get $get) => $get($codigo) === 'otro')
                ->rules(['regex:/^[1-9][0-9]{0,2}$/'])->dehydrated(false),
            TextInput::make($nombre)
                ->label($etiqueta)
                ->tel()
                ->nullable(! $obligatorio)
                ->required($obligatorio)
                ->live(onBlur: true)
                ->maxLength(20)
                ->placeholder(fn (Get $get) => $get($codigo) === '1' ? '202-555-0123' : '7777-7777')
                ->afterStateHydrated(function (TextInput $component, $state, Set $set, Get $get) use ($codigo, $nombre) {
                    $guardado = $nombre === 'numero' ? $get('codigo_pais') : null;
                    if ($guardado && filled($state) && $component->getRecord() instanceof \App\Models\ClienteTelefono) {
                        $pais = (string) $guardado;
                        $numero = preg_replace('/\D/', '', $state);
                        $numero = str_starts_with($numero, $pais) ? substr($numero, strlen($pais)) : $numero;
                    } else {
                        [$pais, $numero] = self::separar($state);
                    }
                    $set($codigo, isset(config('telefonos')[$pais]) ? $pais : 'otro');
                    $set($nombre.'_codigo_otro', $pais);
                    $component->state($numero);
                })
                ->rules(fn (Get $get) => [
                    'regex:/^[0-9 ()-]+$/',
                    function (string $attribute, $value, \Closure $fail) use ($get, $codigo) {
                        $pais = self::codigo($get, $codigo);
                        $longitud = config('telefonos.'.$pais.'.digitos');
                        $digitos = strlen(preg_replace('/\D/', '', (string) $value));
                        if ($longitud && $digitos !== $longitud) {
                            $fail("El número debe contener {$longitud} dígitos, sin el código de país.");
                        } elseif (! $longitud && ($digitos < 6 || $digitos + strlen($pais) > 15)) {
                            $fail('Ingresa un teléfono válido: al menos 6 dígitos y hasta 15 contando el código de país.');
                        }
                    },
                ])
                ->dehydrateStateUsing(fn ($state, Get $get) => self::internacional($state, self::codigo($get, $codigo))),
        ])->columns(2);
    }

    public static function paises(): array
    {
        $paises = [];
        foreach (config('telefonos') as $codigo => $pais) {
            $paises[(string) $codigo] = '+'.$codigo.' '.$pais['nombre'];
        }

        return $paises + ['otro' => 'Otro país'];
    }

    private static function codigo(Get $get, string $campo): string
    {
        return (string) ($get($campo) === 'otro' ? $get(str_replace('_codigo_pais', '_codigo_otro', $campo)) : $get($campo));
    }

    public static function lista(): Repeater
    {
        return Repeater::make('telefonos')->label('Teléfonos')->relationship()
            ->schema([
                Placeholder::make('encabezado_telefono')
                    ->label(new \Illuminate\Support\HtmlString('Número <span class="telefono-requerido">*</span>'))
                    ->content('')
                    ->hint(fn (Get $get): \Illuminate\Support\HtmlString => new \Illuminate\Support\HtmlString(match ($get('formato')) {
                        'us' => '<span class="telefono-badge telefono-badge-us">Internacional</span>',
                        'fijo' => '<span class="telefono-badge telefono-badge-fijo">Residencial</span>',
                        default => '<span class="telefono-badge telefono-badge-sv">Móvil</span>',
                    }))
                    ->columnSpanFull(),
                Select::make('formato')
                    ->hiddenLabel()
                    ->options(['sv' => '+503', 'us' => '+1', 'fijo' => 'Fijo'])
                    ->default('sv')
                    ->selectablePlaceholder(false)
                    ->native(false)
                    ->live()
                    ->required()
                    ->dehydrated(false)
                    ->afterStateHydrated(function (Select $component): void {
                        $telefono = $component->getRecord();
                        $component->state(match (true) {
                            $telefono?->codigo_pais === '1' => 'us',
                            $telefono?->tipo === 'fijo' => 'fijo',
                            default => 'sv',
                        });
                    })
                    ->extraFieldWrapperAttributes(['class' => 'telefono-prefijo'])
                    ->columnSpan(2),
                TextInput::make('numero')
                    ->hiddenLabel()
                    ->tel()
                    ->placeholder(fn (Get $get): string => $get('formato') === 'us' ? '(999) 999-9999' : '9999-9999')
                    ->mask(fn (Get $get): string => $get('formato') === 'us' ? '(999) 999-9999' : '9999-9999')
                    ->required()
                    ->afterStateHydrated(function (TextInput $component, $state): void {
                        $telefono = $component->getRecord();
                        $digitos = preg_replace('/\D/', '', (string) $state);
                        $codigo = (string) ($telefono?->codigo_pais ?? '');
                        $component->state($codigo !== '' && str_starts_with($digitos, $codigo) ? substr($digitos, strlen($codigo)) : $digitos);
                    })
                    ->rules(fn (Get $get): array => [
                        function (string $attribute, $value, \Closure $fail) use ($get): void {
                            $esperados = $get('formato') === 'us' ? 10 : 8;
                            if (strlen(preg_replace('/\D/', '', (string) $value)) !== $esperados) {
                                $fail('El número no tiene la longitud correspondiente al tipo seleccionado.');
                            }
                        },
                    ])
                    ->dehydrateStateUsing(fn ($state, Get $get): string => match ($get('formato')) {
                        'us' => '1' . preg_replace('/\D/', '', (string) $state),
                        default => '503' . preg_replace('/\D/', '', (string) $state),
                    })
                    ->extraFieldWrapperAttributes(['class' => 'telefono-numero'])
                    ->columnSpan(10),
                Hidden::make('codigo_pais')->dehydrateStateUsing(fn ($state, Get $get): string => $get('formato') === 'us' ? '1' : '503'),
                Hidden::make('tipo')->dehydrateStateUsing(fn ($state, Get $get): string => $get('formato') === 'fijo' ? 'fijo' : 'movil'),
            ])->columns(12)->columnSpanFull()
            ->extraAttributes(['class' => 'telefono-compuesto'])
            ->defaultItems(1)->orderColumn('orden')->reorderable(false)
            ->addActionLabel('Agregar otro teléfono')
            ->mutateRelationshipDataBeforeCreateUsing(fn (array $data) => filled($data['numero'] ?? null) ? $data : null)
            ->mutateRelationshipDataBeforeSaveUsing(fn (array $data) => filled($data['numero'] ?? null) ? $data : null)
            ->afterStateHydrated(function (Repeater $component): void {
                $cliente = $component->getRecord();
                if (! empty($component->getState())) {
                    return;
                }

                if ($cliente?->telefono) {
                    [$codigo, $numero] = self::separar($cliente->telefono);
                    $component->state([\Illuminate\Support\Str::uuid()->toString() => [
                        'formato' => $codigo === '1' ? 'us' : 'sv',
                        'numero' => $numero,
                        'codigo_pais' => $codigo,
                        'tipo' => 'movil',
                        'orden' => 0,
                    ]]);

                    return;
                }

                $component->state([\Illuminate\Support\Str::uuid()->toString() => [
                    'formato' => 'sv',
                    'numero' => null,
                    'codigo_pais' => '503',
                    'tipo' => 'movil',
                    'orden' => 0,
                ]]);
            });
    }

    public static function separar(?string $telefono): array
    {
        $numero = preg_replace('/\D/', '', $telefono ?? '');

        if (! str_starts_with(trim($telefono ?? ''), '+') && strlen($numero) === 8) {
            return ['503', $numero];
        }

        if (strlen($numero) === 11 && str_starts_with($numero, '503')) {
            return ['503', substr($numero, 3)];
        }
        if (strlen($numero) === 11 && str_starts_with($numero, '1')) {
            return ['1', substr($numero, 1)];
        }
        if (! str_starts_with(trim($telefono ?? ''), '+') && strlen($numero) === 10) {
            return ['1', $numero];
        }

        $codigos = explode(' ', '1 7 20 27 30 31 32 33 34 36 39 40 41 43 44 45 46 47 48 49 51 52 53 54 55 56 57 58 60 61 62 63 64 65 66 81 82 84 86 90 91 92 93 94 95 98 211 212 213 216 218 220 221 222 223 224 225 226 227 228 229 230 231 232 233 234 235 236 237 238 239 240 241 242 243 244 245 246 247 248 249 250 251 252 253 254 255 256 257 258 260 261 262 263 264 265 266 267 268 269 290 291 297 298 299 350 351 352 353 354 355 356 357 358 359 370 371 372 373 374 375 376 377 378 380 381 382 383 385 386 387 389 420 421 423 500 501 502 503 504 505 506 507 508 509 590 591 592 593 594 595 596 597 598 599 670 672 673 674 675 676 677 678 679 680 681 682 683 685 686 687 688 689 690 691 692 850 852 853 855 856 880 886 960 961 962 963 964 965 966 967 968 970 971 972 973 974 975 976 977 992 993 994 995 996 998');
        foreach ($codigos as $codigo) {
            $codigo = (string) $codigo;
            if (strlen($numero) >= strlen($codigo) + 6 && str_starts_with($numero, $codigo)) {
                return [$codigo, substr($numero, strlen($codigo))];
            }
        }

        return ['503', $numero === '' ? null : $numero];
    }

    public static function internacional(?string $numero, ?string $codigo): ?string
    {
        $digitos = preg_replace('/\D/', '', $numero ?? '');

        return $digitos === '' ? null : preg_replace('/\D/', '', $codigo ?? '503').$digitos;
    }

    public static function normalizarExistente(?string $telefono): ?string
    {
        if (! filled($telefono)) {
            return null;
        }
        if (str_starts_with(trim($telefono), '+')) {
            return preg_replace('/\D/', '', $telefono);
        }
        [$codigo, $numero] = self::separar($telefono);

        return self::internacional($numero, $codigo);
    }
}
