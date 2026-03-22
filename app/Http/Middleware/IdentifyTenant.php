<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

class IdentifyTenant
{
    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost(); 

        // 1. Busca el dominio en la BD maestra
        $empresa = DB::connection('mysql')->table('dominios_empresas')->where('dominio', $host)->first();

        if (!$empresa) {
            abort(404, "El dominio {$host} no está registrado en el sistema.");
        }
        View::share('empresaGlobal', $empresa);
        // 2. Inyectamos las credenciales y conectamos a la BD del cliente
        Config::set('database.connections.tenant.database', $empresa->db_database);
        Config::set('database.connections.tenant.username', $empresa->db_username);
        Config::set('database.connections.tenant.password', $empresa->db_password);

        DB::purge('tenant');
        DB::reconnect('tenant');
        DB::setDefaultConnection('tenant');

        // 3. --- LÓGICA DE LICENCIA Y BLOQUEO ---
        $esPro = $empresa->tipo_licencia == 1;
        $licenciaVencida = false;

        // Si es Demo (0) y tiene fecha, comprobamos si ya pasó de hoy
        if (!$esPro && !is_null($empresa->fecha_exp_licencia)) {
            $hoy = \Carbon\Carbon::today();
            $vencimiento = \Carbon\Carbon::parse($empresa->fecha_exp_licencia);
            
            if ($hoy->greaterThan($vencimiento)) {
                $licenciaVencida = true;
            }
        }

        // Si está vencida, aplicamos el bloqueo inteligente
        if ($licenciaVencida) {
            // Rutas de escape para que puedan hacer Login/Logout y ver la vista de error bien
            if ($request->is('login', 'logout', 'refresh-csrf')) {
                return $next($request);
            }

            // Si hay alguien logueado y es el Super Admin (Rol 1), LO DEJAMOS PASAR
            if (Auth::check() && Auth::user()->id_rol == 1) {
                return $next($request);
            }

            // Si no está logueado, o es un empleado/gerente, lo mandamos a la pantalla de bloqueo
            return response()->view('empresas.licencia_expirada', ['empresa' => $empresa], 403);
        }

        return $next($request);
    }
}