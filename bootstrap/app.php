<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

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
        //
    })->create();
