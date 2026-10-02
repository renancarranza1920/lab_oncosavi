<?php

namespace Tests\Feature;

use App\Support\EstadoVisual;
use Filament\Facades\Filament;
use Filament\Support\Facades\FilamentColor;
use Tests\TestCase;

class PaletaInterfazTest extends TestCase
{
    public function test_los_tonos_de_botones_y_textos_mantienen_contraste(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
        $paletas = FilamentColor::getColors();

        foreach (['primary', 'success', 'warning', 'danger', 'info'] as $color) {
            // Botones rellenos: normal (600) y hover (500), también en oscuro.
            foreach ([500, 600] as $tono) {
                $this->assertGreaterThanOrEqual(4.5, $this->contraste($paletas[$color][$tono], '255, 255, 255'), "$color-$tono sobre blanco");
            }

            // Iconos y botones outlined de Filament usan el tono 400 en oscuro.
            $this->assertGreaterThanOrEqual(4.5, $this->contraste($paletas[$color][400], $paletas['gray'][900]), "$color-400 sobre fondo oscuro");
        }
    }

    public function test_los_estados_reversibles_no_se_presentan_como_errores(): void
    {
        $this->assertSame('warning', EstadoVisual::color(' PAUSADA '));
        $this->assertSame('warning', EstadoVisual::color('pendiente'));
        $this->assertSame('gray', EstadoVisual::color('Inactivo'));
        $this->assertSame('danger', EstadoVisual::color('cancelado'));
        $this->assertSame('danger', EstadoVisual::color('rechazada'));
        $this->assertSame('success', EstadoVisual::color('finalizado'));
        $this->assertSame('info', EstadoVisual::color('en_proceso'));
        $this->assertSame('gray', EstadoVisual::color(null));
    }

    public function test_el_enlace_del_perfil_es_legible_y_conserva_su_destino(): void
    {
        $html = view('filament.resources.perfil-resource.partials.examenes', [
            'getState' => fn () => [['id' => 123, 'tipo' => 'Química', 'nombre' => 'Examen de prueba']],
        ])->render();

        $this->assertStringContainsString('ui-chip ui-chip-link', $html);
        $this->assertStringContainsString(route('filament.admin.resources.examens.view', 123), $html);
        $this->assertStringContainsString('Examen de prueba', $html);
        $this->assertStringNotContainsString('onmouseover=', $html);
    }

    public function test_la_tabla_de_resultados_renderiza_la_accion_destructiva_accesible(): void
    {
        $html = view('filament.forms.components.resultados-table', [
            'getState' => fn () => [1 => [
                'examen_nombre' => 'Examen de prueba',
                'pruebas' => [[
                    'prueba_nombre' => 'Hemoglobina', 'es_externo' => false,
                    'valor_referencia_display' => '12 - 16', 'unidades_display' => 'g/dL',
                    'nota_display' => '', 'resultado_id' => 42,
                ]],
            ]],
        ])->render();

        $this->assertStringContainsString('fi-icon-btn', $html);
        $this->assertStringContainsString('fi-color-danger', $html);
        $this->assertStringContainsString('Eliminar resultado', $html);
        $this->assertStringContainsString('deleteResultado(42)', $html);
        $this->assertStringContainsString('wire:confirm=', $html);
    }

    public function test_shield_puede_leer_el_titulo_financiero_sin_montar_la_pagina(): void
    {
        $pagina = new \App\Filament\Pages\CierreCaja;

        $this->assertSame('Reporte Financiero', $pagina->getTitle());
    }

    private function contraste(string $a, string $b): float
    {
        $luminancia = function (string $rgb): float {
            $canales = array_map(function (string $canal): float {
                $valor = (float) trim($canal) / 255;

                return $valor <= 0.04045 ? $valor / 12.92 : (($valor + 0.055) / 1.055) ** 2.4;
            }, explode(',', $rgb));

            return $canales[0] * 0.2126 + $canales[1] * 0.7152 + $canales[2] * 0.0722;
        };
        $valores = [$luminancia($a), $luminancia($b)];

        return (max($valores) + 0.05) / (min($valores) + 0.05);
    }
}
