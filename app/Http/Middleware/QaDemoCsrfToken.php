<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

/**
 * El formulario usa su propio token de sesión; no altera la cookie XSRF del panel.
 */
class QaDemoCsrfToken extends ValidateCsrfToken
{
    protected $addHttpCookie = false;
}
