<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Convert an authentication exception into a response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Auth\AuthenticationException  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        // Para APIs, siempre retornar JSON
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado. Por favor, inicia sesión.',
            ], 401);
        }

        // Para rutas web, intentar redirigir (pero no fallar si no existe la ruta)
        try {
            return redirect()->guest(route('login'));
        } catch (\Exception $e) {
            // Si la ruta login no existe, retornar JSON o mensaje simple
            return response()->json([
                'success' => false,
                'message' => 'No autenticado. Por favor, inicia sesión.',
            ], 401);
        }
    }
}

