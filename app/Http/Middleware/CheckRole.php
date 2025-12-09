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
    public function handle(Request $request, Closure $next, $roles = null): Response
    {
        $user = $request->user();
    
        // Convertir roles permitidos en array
        $roles = array_filter(array_map('trim', explode('-', $roles)));
        
        if (! $user || ! in_array($user->id_rol, $roles)) {
            abort(403, 'No autorizado');
        }

        return $next($request);
    }
}
