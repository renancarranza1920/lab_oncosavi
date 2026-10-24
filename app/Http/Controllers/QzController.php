<?php

namespace App\Http\Controllers;

use App\Exceptions\QzFirmaException;
use App\Services\QzFirmaService;
use Illuminate\Http\Request;

class QzController extends Controller
{
    private const HEADERS = ['Cache-Control' => 'no-store, private', 'Pragma' => 'no-cache'];

    public function certificado(QzFirmaService $firma)
    {
        try {
            $publico = $firma->certificadoPublico();

            return response($publico['certificado'], 200, self::HEADERS + [
                'Content-Type' => 'text/plain; charset=UTF-8',
                'X-QZ-Certificate-SHA256' => $publico['fingerprint_sha256'],
            ]);
        } catch (QzFirmaException $exception) {
            return $this->error($exception);
        }
    }

    public function firmar(Request $request, QzFirmaService $firma)
    {
        $datos = $request->validate(['request' => ['required', 'string', 'max:1048576']]);

        try {
            $firmaBase64 = $firma->firmar($datos['request'], $request->header('X-QZ-Certificate-SHA256'));

            return response($firmaBase64, 200, self::HEADERS + ['Content-Type' => 'text/plain; charset=UTF-8']);
        } catch (QzFirmaException $exception) {
            return $this->error($exception);
        }
    }

    public function estado(QzFirmaService $firma)
    {
        try {
            return response()->json($firma->estado(), 200, self::HEADERS);
        } catch (QzFirmaException $exception) {
            return $this->error($exception);
        }
    }

    private function error(QzFirmaException $exception)
    {
        $estado = $exception->getCode() === 409 ? 409 : 503;

        return response()->json(['message' => $exception->getMessage()], $estado, self::HEADERS);
    }
}
