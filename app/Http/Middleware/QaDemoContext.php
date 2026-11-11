<?php

namespace App\Http\Middleware;

use App\QaDemo\QaDemoService;
use Closure;
use Illuminate\Cache\FileStore;
use Illuminate\Cache\RateLimiter;
use Illuminate\Cache\Repository;
use Illuminate\Http\Request;
use Illuminate\Routing\Pipeline;
use Symfony\Component\HttpFoundation\Response;

class QaDemoContext
{
    public function __construct(private readonly QaDemoService $qa) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->qa->enabled()) {
            return $this->headers(response('No encontrado.', 404));
        }
        if (! $this->qa->ready()) {
            return $this->headers(response('Control de Calidad Interno está en preparación. Contacta con administración.', 503));
        }
        if (strlen($request->getContent()) > 524288) {
            return $this->headers(response('El registro supera el tamaño permitido.', 413));
        }
        $limited = $this->rateLimit($request);
        if ($limited !== null) {
            return $this->headers($limited);
        }

        // La autenticación usa la sesión web de Oncosavi. Los datos de CCI
        // continúan en SQLite privado sin reemplazar la sesión del usuario.
        $originalDebug = config('app.debug');
        try {
            config(['app.debug' => false]);
            $response = (new Pipeline(app()))->send($request)->then($next);
            return $this->headers($response);
        } finally {
            config(['app.debug' => $originalDebug]);
        }
    }

    private function rateLimit(Request $request): ?Response
    {
        $write = ! in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true);
        $export = $request->routeIs('qa-demo.export.*');
        if (! $write && ! $export) {
            return null;
        }
        $directory = $this->qa->root().'/rate';
        if (! is_dir($directory)) {
            mkdir($directory, 0700, true);
        }
        $limiter = new RateLimiter(new Repository(new FileStore(app('files'), $directory)));
        $key = 'cci:'.($export ? 'export:' : 'write:').hash('sha256', (string) $request->ip());
        $maximum = $export ? 12 : 60;
        if ($limiter->tooManyAttempts($key, $maximum)) {
            return response('Se han enviado muchas solicitudes. Intenta de nuevo en un minuto.', 429)
                ->header('Retry-After', (string) $limiter->availableIn($key));
        }
        $limiter->hit($key, 60);
        return null;
    }

    private function headers(Response $response): Response
    {
        $response->headers->set('Cache-Control', 'no-store, private, max-age=0');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'same-origin');
        return $response;
    }
}
