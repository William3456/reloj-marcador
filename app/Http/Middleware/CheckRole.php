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

    // Convertir roles permitidos en array (ej: "1-2" -> [1, 2])
    $rolesArray = array_filter(array_map('trim', explode('-', $roles)));
    
    // 1. Si el usuario no está autenticado, enviarlo al login
    if (!$user) {
        return redirect()->route('login');
    }

    // 2. LÓGICA DE REDIRECCIÓN: 
    // Si el usuario es Rol 3 y está intentando entrar a una ruta de Roles 1 o 2
    if ($user->id_rol == 3 && !in_array(3, $rolesArray)) {
        
        return redirect()->route('marcacion.inicio');
    }

    // 3. Verificación de seguridad estándar
    if (!in_array($user->id_rol, $rolesArray)) {
        abort(403, 'No autorizado');
    }

    return $next($request);
}
}
