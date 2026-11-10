<?php

namespace App\Http\Middleware;

use App\QaDemo\QaDemoService;
use Closure;
use Illuminate\Cache\FileStore;
use Illuminate\Cache\RateLimiter;
use Illuminate\Cache\Repository;
use Illuminate\Cookie\CookieJar;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Pipeline;
use Illuminate\Routing\Redirector;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Symfony\Component\HttpFoundation\Response;

/**
 * Contexto independiente del grupo web: no abre sesiones ni cachés de producción.
 *
 * La restauración también permite varios requests en un mismo proceso de pruebas.
 */
class QaDemoContext
{
    public function __construct(private readonly QaDemoService $qa) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->qa->enabled()) {
            return $this->headers(response('No encontrado.', 404));
        }

        if (! $this->qa->ready()) {
            return $this->headers(response('La prueba de control de calidad está en preparación. Avise a quien administra el sitio.', 503));
        }

        if (strlen($request->getContent()) > 524288) {
            return $this->headers(response('El registro supera el tamaño permitido.', 413));
        }

        $root = $this->qa->root();
        foreach (['sessions', 'rate'] as $directory) {
            if (! is_dir($root.'/'.$directory)) {
                mkdir($root.'/'.$directory, 0700, true);
            }
        }

        $limited = $this->rateLimit($request, $root);
        if ($limited !== null) {
            return $this->headers($limited);
        }

        $app = app();
        $originalConfig = config('session');
        $originalDebug = config('app.debug');
        $originalBindings = [];
        foreach (['session', 'session.store', 'cookie', 'redirect'] as $binding) {
            $originalBindings[$binding] = $app->resolved($binding) ? $app->make($binding) : null;
        }
        $oldErrors = $app->make('view')->getShared()['errors'] ?? new ViewErrorBag;

        try {
            // La ruta pública de prueba no muestra detalles internos de errores.
            config(['app.debug' => false]);
            config(['session' => array_replace($originalConfig, [
                'driver' => 'file',
                'connection' => null,
                'files' => $root.'/sessions',
                'cookie' => 'oncosavi_qa_demo_session',
                'path' => '/qa-quimica',
                'domain' => null,
                'secure' => $request->isSecure(),
                'same_site' => 'lax',
                'http_only' => true,
                'block' => false,
                'block_store' => null,
            ])]);

            $manager = new SessionManager($app);
            $store = $manager->driver('file');
            $jar = (new CookieJar)->setDefaultPathAndDomain('/qa-quimica', null, $request->isSecure(), 'lax');
            $redirect = new Redirector($app->make('url'));
            $redirect->setSession($store);

            foreach (['session' => $manager, 'session.store' => $store, 'cookie' => $jar, 'redirect' => $redirect] as $key => $object) {
                $app->instance($key, $object);
                Facade::clearResolvedInstance($key);
            }

            // Routing\Pipeline convierte errores de validación mientras la sesión
            // privada aún está activa; StartSession guarda allí los mensajes.
            $response = (new Pipeline($app))->send($request)->through([
                $app->make(EncryptCookies::class),
                new AddQueuedCookiesToResponse($jar),
                new StartSession($manager),
                $app->make(ShareErrorsFromSession::class),
                $app->make(QaDemoCsrfToken::class),
                $app->make(SubstituteBindings::class),
            ])->then($next);

            return $this->headers($response);
        } finally {
            config(['session' => $originalConfig]);
            config(['app.debug' => $originalDebug]);
            foreach ($originalBindings as $binding => $instance) {
                $app->forgetInstance($binding);
                if ($instance !== null) {
                    $app->instance($binding, $instance);
                }
                Facade::clearResolvedInstance($binding);
            }
            $app->make('view')->share('errors', $oldErrors);
        }
    }

    private function rateLimit(Request $request, string $root): ?Response
    {
        $write = ! in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true);
        $export = $request->routeIs('qa-demo.export.*');
        if (! $write && ! $export) {
            return null;
        }

        $limiter = new RateLimiter(new Repository(new FileStore(app('files'), $root.'/rate')));
        $key = 'qa-demo:'.($export ? 'export:' : 'write:').hash('sha256', (string) $request->ip());
        $maximum = $export ? 12 : 60;
        if ($limiter->tooManyAttempts($key, $maximum)) {
            return response('Se han enviado muchas solicitudes. Intente de nuevo en un minuto.', 429)
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
