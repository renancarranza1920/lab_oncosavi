<?php
declare(strict_types=1);

// Simular exclusivamente el transporte HTTP; estas pruebas nunca llaman a OpenAI.
namespace LameriPublica {
    function curl_init(string $url): object { $GLOBALS['test_url'] = $url; return new \stdClass(); }
    function curl_setopt_array(object $handle, array $options): bool { $GLOBALS['test_options'] = $options; return true; }
    function curl_exec(object $handle): bool {
        if ($GLOBALS['test_network_failure'] ?? false) return false;
        $response = json_encode($GLOBALS['test_provider_response']);
        return ($GLOBALS['test_options'][CURLOPT_WRITEFUNCTION])($handle, $response) === strlen($response);
    }
    function curl_getinfo(object $handle, int $option): int { return $GLOBALS['test_status'] ?? 200; }
    function curl_close(object $handle): void {}
}

namespace {
    define('LAMERI_PUBLIC_API', true);
    require __DIR__.'/../public_html/api/lib/configuracion.php';
    require __DIR__.'/../public_html/api/lib/interpretacion.php';
    $passed = 0;
    function check(bool $condition): void {
        if (!$condition) throw new RuntimeException('Comprobación fallida en '.json_encode(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)));
        $GLOBALS['passed']++;
    }
    function rejects(callable $fn): void {
        try { $fn(); } catch (Throwable $error) { $GLOBALS['passed']++; return; }
        throw new RuntimeException('Se aceptó un dato no válido.');
    }
    $settings = \LameriPublica\configuracionIa(['enabled' => true, 'api_key' => 'sk-test-fixture', 'daily_request_limit' => 10]);
    check($settings['enabled'] && $settings['model'] === 'gpt-4.1-mini');
    check(!\LameriPublica\configuracionIa([])['enabled']);
    check(!\LameriPublica\configuracionIa(['enabled' => true])['enabled']);
    check(!\LameriPublica\configuracionIa(['enabled' => true, 'api_key' => "sk-x\r\nheader"])['enabled']);
    check(!\LameriPublica\configuracionIa(['enabled' => 'true', 'api_key' => 'sk-test'])['enabled']);
    check(\LameriPublica\configuracionIa(['daily_request_limit' => 0])['daily_request_limit'] === 100);
    $query = \LameriPublica\validarConsultaIa(['consulta' => '¿Cuánto sale el antígeno prostático?', 'scope' => 'exams', 'contexto' => ['exam:1']]);
    check($query['scope'] === 'exams');
    foreach ([null, [], ['consulta' => ''], ['consulta' => '   '], ['consulta' => str_repeat('x', 401)],
        ['consulta' => "psa\n"], ['consulta' => 'PSA', 'scope' => 'clientes'],
        ['consulta' => 'PSA', 'contexto' => ['cliente:1']], ['consulta' => 'PSA', 'contexto' => [['nombre' => 'Paciente']]]] as $invalid) {
        rejects(fn () => \LameriPublica\validarConsultaIa($invalid));
    }
    foreach (['resultado de PSA', 'DUI 12345678-9', 'tengo dolor', 'Paciente Juan', 'mi correo ejemplo@example.test', '+503 7595 4210', 'interpretar resultados'] as $private) {
        check(!\LameriPublica\consultaPublica($private));
    }
    foreach (['PSA libre', 'CA 19-9', 'cuánto cuesta T3', 'hepatitis B superficie'] as $public) {
        check(\LameriPublica\consultaPublica($public));
    }
    $catalog = ['examens' => [
        ['id' => 1, 'nombre' => 'PSA TOTAL', 'categoria' => 'Marcadores', 'precio' => '22.35'],
        ['id' => 2, 'nombre' => 'PSA LIBRE', 'categoria' => 'Marcadores', 'precio' => '38.15'],
    ], 'perfiles' => [['id' => 1, 'nombre' => 'PERFIL', 'precio' => '40.00', 'incluidos' => ['PSA TOTAL', 'PSA LIBRE']]]];
    $refs = \LameriPublica\referenciasCatalogo($catalog, 'exams');
    check(array_keys($refs) === ['exam:1', 'exam:2']);
    check(array_keys(\LameriPublica\referenciasCatalogo($catalog, 'profiles')) === ['profile:1']);
    check(count(\LameriPublica\referenciasCatalogo($catalog, 'all')) === 3);
    check(!array_key_exists('precio', $refs['exam:1']));
    $payload = \LameriPublica\construirPeticionIa($settings, $query, $refs);
    check($payload['store'] === false && $payload['max_output_tokens'] === 500);
    check($payload['text']['format']['type'] === 'json_schema' && $payload['text']['format']['strict'] === true);
    check($payload['text']['format']['schema']['properties']['keys']['items']['enum'] === ['exam:1', 'exam:2']);
    check(!str_contains($payload['input'], $settings['api_key']) && !str_contains($payload['input'], '22.35'));
    $match = ['kind' => 'matches', 'keys' => ['exam:1'], 'needs_clarification' => false];
    check(\LameriPublica\validarInterpretacion($match, $refs) === $match);
    check(\LameriPublica\validarInterpretacion(['kind' => 'matches', 'keys' => ['exam:1', 'exam:2'], 'needs_clarification' => false], $refs)['needs_clarification']);
    foreach ([['kind' => 'matches', 'keys' => ['exam:999'], 'needs_clarification' => false],
        ['kind' => 'matches', 'keys' => ['profile:1'], 'needs_clarification' => false],
        ['kind' => 'none', 'keys' => ['exam:1'], 'needs_clarification' => false],
        ['kind' => 'matches', 'keys' => [], 'needs_clarification' => false],
        $match + ['precio' => '0.00'], ['kind' => 'matches', 'keys' => ['exam:1'], 'needs_clarification' => 'false']] as $invalid) {
        rejects(fn () => \LameriPublica\validarInterpretacion($invalid, $refs));
    }
    $GLOBALS['test_provider_response'] = ['status' => 'completed', 'output' => [
        ['type' => 'message', 'role' => 'assistant', 'content' => [['type' => 'output_text', 'text' => json_encode($match)]]],
    ]];
    check(\LameriPublica\consultarOpenAI($settings, $query, $refs) === $match);
    check($GLOBALS['test_url'] === 'https://api.openai.com/v1/responses');
    check($GLOBALS['test_options'][CURLOPT_SSL_VERIFYPEER] === true && $GLOBALS['test_options'][CURLOPT_SSL_VERIFYHOST] === 2);
    check($GLOBALS['test_options'][CURLOPT_FOLLOWLOCATION] === false);
    $GLOBALS['test_status'] = 429;
    rejects(fn () => \LameriPublica\consultarOpenAI($settings, $query, $refs));
    $GLOBALS['test_status'] = 200;
    $GLOBALS['test_network_failure'] = true;
    rejects(fn () => \LameriPublica\consultarOpenAI($settings, $query, $refs));
    $GLOBALS['test_network_failure'] = false;
    rejects(fn () => \LameriPublica\consultarOpenAI(\LameriPublica\configuracionIa([]), $query, $refs));
    rejects(fn () => \LameriPublica\leerRespuestaIa(['status' => 'incomplete', 'output' => []], $refs));
    rejects(fn () => \LameriPublica\leerRespuestaIa(['status' => 'completed', 'output' => [
        ['type' => 'message', 'role' => 'assistant', 'content' => [['type' => 'refusal', 'refusal' => 'No']]],
    ]], $refs));
    $tmp = sys_get_temp_dir().'/merino-ia-tests-'.bin2hex(random_bytes(6));
    mkdir($tmp, 0700);
    try {
        for ($i = 0; $i < 6; $i++) check(\LameriPublica\reservarConsultaIa($tmp, $settings, '192.0.2.1', 1800000000));
        check(!\LameriPublica\reservarConsultaIa($tmp, $settings, '192.0.2.1', 1800000000));
        for ($i = 0; $i < 4; $i++) check(\LameriPublica\reservarConsultaIa($tmp, $settings, '192.0.2.2', 1800000000));
        check(!\LameriPublica\reservarConsultaIa($tmp, $settings, '192.0.2.3', 1800000060));
        $path = $tmp.'/ia-limites.json';
        check((fileperms($path) & 0777) === 0600);
        $raw = file_get_contents($path);
        check(!str_contains($raw, '192.0.2.') && !str_contains($raw, $query['consulta']) && !str_contains($raw, $settings['api_key']));
        check(\LameriPublica\reservarConsultaIa($tmp, $settings, '192.0.2.1', 1800086400));
        file_put_contents($path, '{corrupt');
        check(!\LameriPublica\reservarConsultaIa($tmp, $settings, '192.0.2.1', 1800086460));
        check(!\LameriPublica\reservarConsultaIa('/dev/null/cache', $settings, '192.0.2.1'));
    } finally { @unlink($tmp.'/ia-limites.json'); @rmdir($tmp); }
    $oldConfigPath = tempnam(sys_get_temp_dir(), 'merino-old-config-');
    try {
        file_put_contents($oldConfigPath, '<?php return '.var_export([
            'mysql' => ['host' => 'localhost', 'database' => 'fixture', 'username' => 'fixture', 'password' => 'fixture'],
            'contacto' => ['nombre' => 'Merino'],
        ], true).';');
        $loaded = \LameriPublica\cargarConfiguracion($oldConfigPath);
        check($loaded['mysql']['database'] === 'fixture' && !$loaded['ia']['enabled']);
        check($loaded['contacto']['nombre'] === 'Merino');
    } finally { unlink($oldConfigPath); }
    echo "$passed comprobaciones: configuración antigua compatible, consultas públicas, catálogo acotado, transporte simulado, rechazos y límites privados.\n";
}
