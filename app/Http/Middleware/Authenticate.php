<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // Para APIs, siempre retornar null (no redirigir)
        // Las APIs deben manejar la autenticación con JSON responses
        if ($request->expectsJson() || $request->is('api/*')) {
            return null;
        }
        
        // Para rutas web, intentar redirigir a login (si existe la ruta)
        try {
            return route('login');
        } catch (\Exception $e) {
            // Si la ruta no existe, retornar null
            return null;
        }
    }
}

