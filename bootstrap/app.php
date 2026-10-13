<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');
    })
     ->withEvents(discover: false)
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
     ->withProviders([
        \App\Providers\EventServiceProvider::class,
    ])->create();

// Hostinger sirve esta carpeta, mientras el código y las credenciales quedan fuera.
$app->usePublicPath(dirname(__DIR__).'/public_html');

return $app;
