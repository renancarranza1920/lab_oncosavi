<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\DetalleOrden;
use App\Models\Examen;
use App\Models\Medico;
use App\Models\Orden;
use App\Models\Resultado;
use App\Models\User;
use App\Support\SelloLaboratorio;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EntornoTestSeeder extends Seeder
{
    public function run(): void
    {
        if (!app()->environment(['staging', 'testing']) || !config('test_demo.enabled')) {
            throw new \LogicException('Solo permitido con APP_ENV=staging y TEST_DEMO_ENABLED=true.');
        }
        foreach (['users', 'clientes', 'ordens', 'examens', 'pruebas', 'muestras', 'resultados'] as $table) {
            if (DB::table($table)->exists()) {
                throw new \LogicException('La base debe estar vacía. No se borró ni modificó ningún registro. Use una base nueva y exclusiva para test.');
            }
        }
        if (Medico::whereNull('portal_usuario')->exists() || SelloLaboratorio::path()) {
            throw new \LogicException('Ya existen médicos o un sello institucional. Use una base y storage exclusivos de test.');
        }
        DB::transaction(function (): void {
            $this->call(DatabaseSeeder::class);
            $passwordAdmin = \Illuminate\Support\Str::password(24);
            User::where('nickname', 'oncosavi')->firstOrFail()->update([
                'name' => 'Prueba administrador', 'nickname' => 'prueba.admin',
                'email' => 'prueba.admin@oncosavi.test', 'password' => $passwordAdmin,
            ]);
            Artisan::call('oncosavi:usuarios-prueba');
            $credenciales = json_decode(Storage::disk('local')->get('usuarios-prueba.json'), true);
            array_unshift($credenciales, ['usuario' => 'prueba.admin', 'rol' => 'admin', 'password' => $passwordAdmin]);
            Storage::disk('local')->put('usuarios-prueba.json', json_encode($credenciales, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            chmod(Storage::disk('local')->path('usuarios-prueba.json'), 0600);
            Artisan::call('oncosavi:crear-superadmin');
            $lab = User::where('nickname', 'prueba.lab')->firstOrFail();
            $admin = User::where('nickname', 'prueba.admin')->firstOrFail();
            Auth::login($admin);
            try {
                $sello = $this->imagen('sellos/'.$lab->id.'/ejemplo.png', 'SELLO LABORATORISTA', false);
                $firma = $this->imagen('firmas/'.$lab->id.'/ejemplo.png', 'FIRMA DE EJEMPLO', true);
                $lab->update(['sello_path' => $sello, 'firma_path' => $firma]);
                $institucional = $this->imagen('sellos/laboratorio/ejemplo.png', 'SELLO DEL LABORATORIO', false);
                SelloLaboratorio::guardar($institucional);
                $medico = Medico::create(['nombre' => 'Dra. de Ejemplo · DEMOSTRACIÓN']);
                $examen = Examen::with(['pruebas.tipoPrueba', 'muestras', 'tipoExamen'])->where('nombre', 'GLUCOSA')->firstOrFail();
                $estados = ['pendiente', 'en proceso', 'pausada', 'finalizado', 'cancelado', 'finalizado'];
                for ($i = 1; $i <= 12; $i++) {
                    $cliente = Cliente::create([
                        'nombre' => 'Paciente de ejemplo '.$i, 'apellido' => 'DEMOSTRACIÓN',
                        'genero' => $i % 2 ? 'Femenino' : 'Masculino',
                        'fecha_nacimiento' => now()->subYears(20 + $i)->format('Y-m-d'),
                        'telefono' => '1202555'.sprintf('%04d', 100 + $i),
                        'correo' => 'paciente'.$i.'@example.invalid', 'direccion' => 'Dirección ficticia para pruebas',
                    ]);
                    $cliente->telefonos()->createMany([
                        ['codigo_pais' => '1', 'numero' => '1202555'.sprintf('%04d', 100 + $i), 'tipo' => 'movil', 'orden' => 0],
                        ['codigo_pais' => '1', 'numero' => '1202555'.sprintf('%04d', 150 + $i), 'tipo' => 'fijo', 'orden' => 1],
                    ]);
                    $estado = $estados[($i - 1) % count($estados)];
                    $fecha = now()->subDays(($i - 1) % 7)->setTime(8 + $i % 8, 15);
                    $orden = Orden::create(['cliente_id' => $cliente->id, 'medico_id' => $medico->id,
                        'fecha' => $fecha, 'total' => $examen->precio, 'estado' => $estado,
                        'observaciones' => 'DEMOSTRACIÓN: datos ficticios, sin validez clínica.',
                    ]);
                    $orden->forceFill(['created_at' => $fecha])->save();
                    $detalle = DetalleOrden::create(['orden_id' => $orden->id, 'examen_id' => $examen->id,
                        'nombre_examen' => $examen->nombre, 'precio_examen' => $examen->precio,
                        'status' => $estado === 'finalizado' ? 'completado' : 'pendiente',
                    ]);
                    if ($estado === 'finalizado') {
                        foreach ($examen->pruebas as $prueba) {
                            Resultado::create(['detalle_orden_id' => $detalle->id, 'prueba_id' => $prueba->id,
                                'resultado' => (string) (80 + $i), 'prueba_nombre_snapshot' => $prueba->nombre,
                                'unidades_snapshot' => 'mg/dL', 'valor_referencia_snapshot' => '70 - 110',
                                'user_id' => $lab->id, 'observaciones' => 'Resultado ficticio de ejemplo.',
                            ]);
                        }
                        $this->pdf($orden, $examen, $sello, $firma);
                    }
                }
            } finally {
                Auth::logout();
            }
        });
    }

    private function imagen(string $path, string $texto, bool $firma): string
    {
        $image = imagecreatetruecolor(440, 180);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 255, 255, 255, 127));
        imagealphablending($image, true);
        $azul = imagecolorallocate($image, 32, 53, 82);
        if ($firma) {
            imagesetthickness($image, 3);
            imageopenpolygon($image, [35, 90, 80, 40, 105, 108, 155, 65, 185, 105, 285, 70, 245, 115, 385, 85], $azul);
        } else {
            imagerectangle($image, 8, 8, 431, 171, $azul);
            imagestring($image, 5, 50, 38, $texto, $azul);
        }
        imagestring($image, 5, 110, 125, 'SOLO DEMOSTRACION', $azul);
        ob_start();
        imagepng($image);
        $bytes = ob_get_clean();
        imagedestroy($image);
        if (!Storage::disk('public')->put($path, $bytes)) {
            throw new \RuntimeException('No se pudo guardar una imagen de ejemplo.');
        }
        return $path;
    }

    private function pdf(Orden $orden, Examen $examen, string $sello, string $firma): void
    {
        $toBase64 = fn (string $path) => 'data:image/png;base64,'.base64_encode(Storage::disk('public')->get($path));
        $pruebas = $orden->resultados->map(fn ($resultado) => [
            'nombre' => $resultado->prueba_nombre_snapshot, 'resultado' => $resultado->resultado,
            'referencia' => $resultado->valor_referencia_snapshot, 'unidades' => $resultado->unidades_snapshot,
            'tipo_prueba' => '',
        ])->all();
        $data = ['orden' => $orden->load(['cliente', 'medico', 'detalleOrden.examen.muestras']),
            'logo_b64' => 'data:image/png;base64,'.base64_encode(file_get_contents(public_path(config('laboratorio.logo')))), 'sello_registro_b64' => SelloLaboratorio::base64(),
            'grupos_por_usuario' => [['sello_b64' => $toBase64($sello), 'firma_b64' => $toBase64($firma),
                'datos' => [$examen->tipoExamen->nombre => [['nombre' => $examen->nombre,
                    'pruebas_unitarias' => $pruebas, 'matrices' => [],
                    'observaciones' => 'DEMOSTRACIÓN: sin validez clínica.',
                ]]],
            ]],
        ];
        Storage::disk('public')->put('reportes/'.$orden->reporteGuardadoFileName(), Pdf::loadView('pdf.reporte_resultados', $data)->setPaper('letter')->output());
    }
}
