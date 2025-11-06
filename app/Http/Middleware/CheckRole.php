<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $roles=null): Response
    {
        $user = $request->user();
        
        // Convierte roles en array si es un solo valor
        $roles = is_array($roles) ? $roles : explode(',', $roles);

        if (!$user || !in_array($user->id_rol, $roles)) {
            abort(403, 'No autorizado');
        }
        return $next($request);
    }
}
