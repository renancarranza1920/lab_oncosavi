<?php

namespace App\Support;

use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;

class TelefonoCliente
{
    public static function campo(string $nombre = 'telefono', string $etiqueta = 'Teléfono'): Group
    {
        $codigo = $nombre . '_codigo_pais';

        return Group::make([
            Select::make($codigo)
                ->label('Código de país')
                ->options(['503' => '+503 El Salvador', '1' => '+1 Estados Unidos'])
                ->default('503')
                ->selectablePlaceholder(false)
                ->required()
                ->live()
                ->dehydrated(false),
            TextInput::make($nombre)
                ->label($etiqueta)
                ->tel()
                ->nullable()
                ->live(onBlur: true)
                ->maxLength(20)
                ->placeholder(fn (Get $get) => $get($codigo) === '1' ? '202-555-0123' : '7777-7777')
                ->afterStateHydrated(function (TextInput $component, $state, Set $set) use ($codigo) {
                    [$pais, $numero] = self::separar($state);
                    $set($codigo, $pais);
                    $component->state($numero);
                })
                ->rules(fn (Get $get) => [
                    'regex:/^[0-9 ()-]+$/',
                    function (string $attribute, $value, \Closure $fail) use ($get, $codigo) {
                        $longitud = $get($codigo) === '1' ? 10 : 8;
                        if (strlen(preg_replace('/\D/', '', (string) $value)) !== $longitud) {
                            $fail("El número debe contener {$longitud} dígitos, sin el código de país.");
                        }
                    },
                ])
                ->dehydrateStateUsing(fn ($state, Get $get) => self::internacional($state, $get($codigo))),
        ])->columns(2);
    }

    public static function separar(?string $telefono): array
    {
        $numero = preg_replace('/\D/', '', $telefono ?? '');

        if (strlen($numero) === 11 && str_starts_with($numero, '503')) {
            return ['503', substr($numero, 3)];
        }
        if (strlen($numero) === 11 && str_starts_with($numero, '1')) {
            return ['1', substr($numero, 1)];
        }
        if (strlen($numero) === 10) {
            return ['1', $numero];
        }

        return ['503', $numero === '' ? null : $numero];
    }

    public static function internacional(?string $numero, ?string $codigo): ?string
    {
        $digitos = preg_replace('/\D/', '', $numero ?? '');

        return $digitos === '' ? null : ($codigo === '1' ? '1' : '503') . $digitos;
    }
}
