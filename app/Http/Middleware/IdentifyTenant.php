<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class IdentifyTenant
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost(); 

        // Busca el dominio en la BD maestra
        $empresa = DB::connection('mysql')->table('dominios_empresas')->where('dominio', $host)->first();

        if (!$empresa) {
            abort(404, "El dominio {$host} no está registrado en el sistema.");
        }

        // Inyectamos las credenciales directamente (en local la contraseña vendrá vacía)
        Config::set('database.connections.tenant.database', $empresa->db_database);
        Config::set('database.connections.tenant.username', $empresa->db_username);
        Config::set('database.connections.tenant.password', $empresa->db_password);

        // Purgamos la memoria caché de la conexión y reconectamos
        DB::purge('tenant');
        DB::reconnect('tenant');
        
        // Le decimos a Laravel que use esta conexión para el resto del ciclo de vida de la petición
        DB::setDefaultConnection('tenant');

        return $next($request);
    }
}
