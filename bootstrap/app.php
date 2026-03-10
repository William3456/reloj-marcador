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

        // 1. Aquí registraste tus alias
        $middleware->alias([
            'check.role' => \App\Http\Middleware\CheckRole::class,
            'prevent-back-history' => \App\Http\Middleware\PreventBackHistory::class,
        ])->web(prepend: [
            \App\Http\Middleware\IdentifyTenant::class,// Esto agrega el middleware a TODAS las rutas de web.php y auth.php automáticamente
            \App\Http\Middleware\MatarCookieFantasma::class,
        ]);

        // 3. Tu redirección de login (lo que hicimos antes)
        $middleware->redirectTo(           
            guests: '/login',
            users: '/dashboard'
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, \Illuminate\Http\Request $request) {
            
            // 🌟 FORZAR EL LOG DEL ERROR 419
            \Illuminate\Support\Facades\Log::warning('Error 419 CSRF detectado', [
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
                'dispositivo' => $request->userAgent(),
            ]);

            return redirect()->back()
                ->withInput($request->except('_token'))
                ->with('error', 'La sesión expiró por seguridad. Por favor, intenta de nuevo.');
        });
    })->create();