<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Cookie;

class MatarCookieFantasma
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
       $response = $next($request);

        // El nombre exacto de la cookie (basado en el hash de tu Guard)
        $cookieName = 'remember_web_59ba36addc2b2f9401580f014c7f58ea4e30989d';

        // Disparamos la orden de eliminación apuntando estrictamente al dominio viejo
        // Esto NO afectará a la cookie nueva que se crea en www.tecnologiassv.org
        $response->withCookie(cookie()->forget($cookieName, '/', '.tecnologiassv.org'));

        return $response;
    }
}
