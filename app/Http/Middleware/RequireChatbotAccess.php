<?php

namespace App\Http\Middleware;

use App\Support\ChatbotAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireChatbotAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Inicia sesión para consultar el asistente.'], 401)
                : redirect()->guest(route('filament.admin.auth.login'));
        }
        abort_unless(ChatbotAccess::allowed($request->user()), 403);

        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'same-origin');
        $response->headers->set('Content-Security-Policy', "default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; connect-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'none'; form-action 'self'");

        return $response;
    }
}
