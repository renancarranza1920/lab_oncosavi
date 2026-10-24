<?php

namespace Tests\Feature;

use App\Exceptions\QzFirmaException;
use App\Models\User;
use App\Services\QzFirmaService;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class QzFirmaTest extends TestCase
{
    use RefreshDatabase;

    private string $almacenOriginal;
    private string $almacenPrueba;

    protected function setUp(): void
    {
        parent::setUp();

        $this->almacenOriginal = storage_path();
        $this->almacenPrueba = sys_get_temp_dir().'/lameri-qz-tests-'.bin2hex(random_bytes(8));
        File::ensureDirectoryExists($this->almacenPrueba, 0700);
        $this->app->useStoragePath($this->almacenPrueba);

        $this->seed(RolesPermisosSeeder::class);
        $operadora = User::factory()->create();
        $operadora->assignRole('Recepcion');
        $this->actingAs($operadora);
    }

    protected function tearDown(): void
    {
        if (isset($this->almacenOriginal)) {
            $this->app->useStoragePath($this->almacenOriginal);
        }

        if (isset($this->almacenPrueba) && str_starts_with($this->almacenPrueba, sys_get_temp_dir().'/lameri-qz-tests-')) {
            File::deleteDirectory($this->almacenPrueba);
        }

        parent::tearDown();
    }

    /** @return array{string, string} Certificado y clave efímeros, nunca versionados. */
    private function generarPar(): array
    {
        $clave = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 2048]);
        $this->assertNotFalse($clave);
        $solicitud = openssl_csr_new(['commonName' => 'Lameri QZ pruebas'], $clave, ['digest_alg' => 'sha256']);
        $this->assertNotFalse($solicitud);
        $certificado = openssl_csr_sign($solicitud, null, $clave, 365, ['digest_alg' => 'sha256'], random_int(1, 1000000));
        $this->assertNotFalse($certificado);
        $this->assertTrue(openssl_pkey_export($clave, $clavePem));
        $this->assertTrue(openssl_x509_export($certificado, $certificadoPem));

        return [$certificadoPem, $clavePem];
    }

    private function mensaje(): string
    {
        return json_encode([
            'call' => 'print',
            'params' => ['data' => "^XA^FDLABORATORIO MERINO — PACIENTE DEMO^FS^XZ\n"],
            'timestamp' => 1792792800000,
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    private function comprobarFirma(string $certificado, string $mensaje, string $firma): void
    {
        $binaria = base64_decode($firma, true);
        $this->assertNotFalse($binaria);
        $this->assertSame(1, openssl_verify($mensaje, $binaria, $certificado, OPENSSL_ALGO_SHA512));
        $this->assertSame(0, openssl_verify($mensaje.' cambiado', $binaria, $certificado, OPENSSL_ALGO_SHA512));
        $this->assertSame(0, openssl_verify($mensaje, $binaria, $certificado, OPENSSL_ALGO_SHA256));
    }

    private function cadenaJavascript(string $contenido): string
    {
        return implode(" +\n", array_map(
            fn (string $linea): string => json_encode($linea."\n", JSON_THROW_ON_ERROR),
            explode("\n", trim($contenido)),
        ));
    }

    public function test_firma_sha512_corresponde_al_certificado_y_la_clave_se_conserva_fuera_del_publico(): void
    {
        [$certificado, $clave] = $this->generarPar();
        $servicio = app(QzFirmaService::class);
        $estado = $servicio->configurar($certificado, $clave);

        $this->assertSame(strtolower(openssl_x509_fingerprint($certificado, 'sha256')), $estado['fingerprint_sha256']);
        $this->assertSame(openssl_x509_parse($certificado)['validTo_time_t'], strtotime($estado['valid_until']));
        $this->assertSame(storage_path('app/private/qz/configuracion.json'), $estado['ruta_configuracion']);
        $this->assertFileExists($estado['ruta_configuracion']);
        $this->assertSame(0600, fileperms($estado['ruta_configuracion']) & 0777);
        $this->assertFalse(str_starts_with($estado['ruta_configuracion'], public_path().DIRECTORY_SEPARATOR));
        $this->assertSame(trim($certificado), trim($servicio->certificado()));
        $this->comprobarFirma($certificado, $this->mensaje(), $servicio->firmar($this->mensaje()));
        $this->assertSame($estado['fingerprint_sha256'], $servicio->estado()['fingerprint_sha256']);

        $servicio->configurar($certificado.$clave, $clave);
        $publico = $this->get(route('qz.certificado'))->assertOk()->getContent();
        $this->assertSame(trim($certificado), trim($publico));
        $this->assertFalse(str_contains($publico, 'PRIVATE KEY'));

        $javascript = file_get_contents(public_path('js/sign-message.js'));
        $this->assertFalse(str_contains($javascript, 'BEGIN PRIVATE KEY'));
        $this->assertFalse(str_contains($javascript, 'BEGIN RSA PRIVATE KEY'));
    }

    public function test_un_par_incompatible_no_reemplaza_una_configuracion_que_ya_firma(): void
    {
        [$certificado, $clave] = $this->generarPar();
        [, $claveAjena] = $this->generarPar();
        $servicio = app(QzFirmaService::class);
        $estado = $servicio->configurar($certificado, $clave);
        $hashAnterior = hash_file('sha256', $estado['ruta_configuracion']);

        try {
            $servicio->configurar($certificado, $claveAjena);
            $this->fail('La clave de otro certificado debía rechazarse.');
        } catch (QzFirmaException $exception) {
            $this->assertNotSame('', $exception->getMessage());
            $this->assertFalse(str_contains($exception->getMessage(), 'PRIVATE KEY'));
        }

        $this->assertSame($hashAnterior, hash_file('sha256', $estado['ruta_configuracion']));
        $this->comprobarFirma($certificado, $this->mensaje(), $servicio->firmar($this->mensaje()));
    }

    public function test_certificado_y_firma_http_se_entregan_sin_cache_y_sin_clave_privada(): void
    {
        [$certificado, $clave] = $this->generarPar();
        $estado = app(QzFirmaService::class)->configurar($certificado, $clave);
        $respuestaCertificado = $this->get(route('qz.certificado'))->assertOk()
            ->assertHeader('X-QZ-Certificate-SHA256', $estado['fingerprint_sha256']);

        $this->assertSame(trim($certificado), trim($respuestaCertificado->getContent()));
        $this->assertStringContainsString('no-store', $respuestaCertificado->headers->get('Cache-Control'));
        $this->assertFalse(str_contains($respuestaCertificado->getContent(), 'PRIVATE KEY'));

        $respuestaFirma = $this->postJson(route('qz.firmar'), ['request' => $this->mensaje()], [
            'X-QZ-Certificate-SHA256' => $estado['fingerprint_sha256'],
        ])->assertOk();

        $this->assertStringContainsString('no-store', $respuestaFirma->headers->get('Cache-Control'));
        $this->assertFalse(str_contains($respuestaFirma->getContent(), 'PRIVATE KEY'));
        $this->comprobarFirma($certificado, $this->mensaje(), trim($respuestaFirma->getContent()));
    }

    public function test_rutas_de_firma_exigen_sesion_y_permiso_de_impresion(): void
    {
        $usuarioSinPermiso = User::factory()->create();
        $this->actingAs($usuarioSinPermiso);
        $this->getJson(route('qz.certificado'))->assertForbidden();
        $this->postJson(route('qz.firmar'), ['request' => $this->mensaje()])->assertForbidden();

        auth()->logout();
        $this->getJson(route('qz.certificado'))->assertUnauthorized();
        $this->postJson(route('qz.firmar'), ['request' => $this->mensaje()])->assertUnauthorized();
    }

    public function test_sin_configuracion_o_con_archivo_invalido_no_hay_firma_anonima_ni_se_filtran_secretos(): void
    {
        foreach ([route('qz.certificado'), route('qz.firmar')] as $ruta) {
            $respuesta = $ruta === route('qz.certificado')
                ? $this->getJson($ruta)
                : $this->postJson($ruta, ['request' => $this->mensaje()]);
            $respuesta->assertStatus(503)->assertJsonStructure(['message']);
            $this->assertFalse(str_contains($respuesta->getContent(), $this->almacenPrueba));
            $this->assertFalse(str_contains($respuesta->getContent(), 'PRIVATE KEY'));
        }

        File::ensureDirectoryExists(storage_path('app/private/qz'), 0700);
        file_put_contents(storage_path('app/private/qz/configuracion.json'), '{no es un JSON válido');
        $this->postJson(route('qz.firmar'), ['request' => $this->mensaje()])->assertStatus(503);
    }

    public function test_un_navegador_con_el_certificado_anterior_debe_recargar_antes_de_firmar(): void
    {
        [$anterior, $claveAnterior] = $this->generarPar();
        [$actual, $claveActual] = $this->generarPar();
        $servicio = app(QzFirmaService::class);
        $estadoAnterior = $servicio->configurar($anterior, $claveAnterior);
        $estadoActual = $servicio->configurar($actual, $claveActual);

        $respuesta = $this->postJson(route('qz.firmar'), ['request' => $this->mensaje()], [
            'X-QZ-Certificate-SHA256' => $estadoAnterior['fingerprint_sha256'],
        ])->assertStatus(409);
        $this->assertFalse(str_contains($respuesta->getContent(), 'PRIVATE KEY'));

        $firma = $this->postJson(route('qz.firmar'), ['request' => $this->mensaje()], [
            'X-QZ-Certificate-SHA256' => $estadoActual['fingerprint_sha256'],
        ])->assertOk()->getContent();
        $this->comprobarFirma($actual, $this->mensaje(), trim($firma));
    }

    public function test_solo_firma_mensajes_de_texto_no_vacios(): void
    {
        [$certificado, $clave] = $this->generarPar();
        app(QzFirmaService::class)->configurar($certificado, $clave);

        $this->postJson(route('qz.firmar'), [])->assertUnprocessable()->assertJsonValidationErrors('request');
        $this->postJson(route('qz.firmar'), ['request' => ''])->assertUnprocessable()->assertJsonValidationErrors('request');
        $this->postJson(route('qz.firmar'), ['request' => ['texto' => 'no válido']])->assertUnprocessable()->assertJsonValidationErrors('request');
    }

    public function test_comando_configura_un_par_pem_sin_escribir_la_clave_en_la_salida(): void
    {
        [$certificado, $clave] = $this->generarPar();
        $certificadoRuta = $this->almacenPrueba.'/entrada-certificado.pem';
        $claveRuta = $this->almacenPrueba.'/entrada-clave.pem';
        file_put_contents($certificadoRuta, $certificado);
        file_put_contents($claveRuta, $clave);

        $estado = Artisan::call('lameri:qz-configurar', ['--certificado' => $certificadoRuta, '--clave' => $claveRuta]);
        $this->assertSame(0, $estado);
        $salida = Artisan::output();
        $this->assertFalse(str_contains($salida, 'PRIVATE KEY'));
        $this->assertFalse(str_contains($salida, $clave));
        $this->comprobarFirma($certificado, $this->mensaje(), app(QzFirmaService::class)->firmar($this->mensaje()));
    }

    public function test_comando_importa_el_par_antiguo_sin_ejecutar_su_javascript(): void
    {
        [$certificado, $clave] = $this->generarPar();
        $javascriptRuta = $this->almacenPrueba.'/sign-message-anterior.js';
        $vistaRuta = $this->almacenPrueba.'/kanban-anterior.blade.php';
        file_put_contents($javascriptRuta, 'var privateKey = '.$this->cadenaJavascript($clave).";\nthrow new Error('Este código no debe ejecutarse');\n");
        file_put_contents($vistaRuta, '<script>qz.security.setCertificatePromise(function(resolve) { resolve('.$this->cadenaJavascript($certificado).'); });</script>');

        $estado = Artisan::call('lameri:qz-configurar', ['--desde-js' => $javascriptRuta, '--desde-vista' => $vistaRuta]);
        $this->assertSame(0, $estado);
        $salida = Artisan::output();
        $this->assertFalse(str_contains($salida, 'PRIVATE KEY'));
        $this->assertFalse(str_contains($salida, $clave));
        $this->assertSame(trim($certificado), trim(app(QzFirmaService::class)->certificado()));
        $this->comprobarFirma($certificado, $this->mensaje(), app(QzFirmaService::class)->firmar($this->mensaje()));
    }
}
