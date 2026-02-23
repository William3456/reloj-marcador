<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException; // <-- 1. Importación obligatoria agregada

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        // 1. Aquí registraste tus alias (lo que ya tenías)
        $middleware->alias([
            'check.role' => \App\Http\Middleware\CheckRole::class,
            'prevent-back-history' => \App\Http\Middleware\PreventBackHistory::class,
        ]);

        // 2. AGREGA ESTA LÍNEA: Confiar en Ngrok para detectar HTTPS
        $middleware->trustProxies(at: '*');
        $middleware->web(append: [
            \App\Http\Middleware\MatarCookieFantasma::class,
        ]);
        // 3. Tu redirección de login (lo que hicimos antes)
        $middleware->redirectTo(           
            guests: '/login',
            users: function (Request $request) {
                if ($request->user() && $request->user()->id_rol === 3) {
                    return '/marcacion/inicio'; // <--- Esta es la URL correcta definida en web.php
                }

                return '/dashboard';
            }
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        
        // 4. EL INTERCEPTOR DEL ERROR 419
        $exceptions->render(function (TokenMismatchException $e, Request $request) {
            
            // Si la petición viene por AJAX/Fetch (útil a futuro)
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Token CSRF expirado. Recarga la página.'], 419);
            }

            // Si es una petición tradicional (Formulario web)
            return redirect()->back()
                ->withInput($request->except('_token', 'password', 'password_confirmation'))
                ->with('error', 'Tu sesión expiró por inactividad. La página se actualizó por seguridad, por favor intenta de nuevo.');
        });

    })->create();