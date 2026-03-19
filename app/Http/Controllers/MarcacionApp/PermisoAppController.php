<?php

namespace App\Http\Controllers\MarcacionApp;

use App\Http\Controllers\Controller;
use App\Mail\SolicitudPermisoAdminMail;
use App\Mail\SolicitudPermisoEmpleadoMail;
use App\Models\Permiso\Permiso;
use App\Models\Permiso\TipoPermiso;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PermisoAppController extends Controller
{
    // Muestra el formulario
    public function create()
    {
        // Solo enviamos los tipos de permiso activos
        $tiposPermiso = TipoPermiso::where('estado', 1)->get();

        return view('app_marcacion.permisos.solicitar', compact('tiposPermiso'));
    }

    // Guarda y envía correos
    public function store(Request $request)
    {
        $request->validate([
            'id_tipo_permiso' => 'required|exists:tipos_permiso,id',
            'motivo' => 'required|string|max:500',
        ]);

        $empleado = Auth::user()->empleado;

        // Regla 1: Verificar si ya tiene un permiso general en espera
        $permisoPendiente = Permiso::where('id_empleado', $empleado->id)
            ->where('estado_solicitud', 1)
            ->exists();

        if ($permisoPendiente) {
            return redirect()->route('marcacion.permisos.index')
                ->with('error', 'Ya tienes una solicitud en revisión. Por favor, espera a que sea procesada antes de solicitar otra.');
        }

        // 🌟 NUEVA REGLA 2: VALIDACIÓN DE CRUCE DE FECHAS (APP)
        $tipo = TipoPermiso::findOrFail($request->id_tipo_permiso);

        if ($tipo->requiere_fechas && $request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $cruceFechas = Permiso::where('id_empleado', $empleado->id)
                ->where('id_tipo_permiso', $tipo->id)
                ->where('estado', 1) // Solo aplica si choca con uno ya activo
                ->where(function ($query) use ($request) {
                    $query->where('fecha_inicio', '<=', $request->fecha_fin)
                        ->where('fecha_fin', '>=', $request->fecha_inicio);
                })
                ->exists();

            if ($cruceFechas) {
                // 🌟 CORRECCIÓN: Disparamos un error de validación oficial de Laravel
                // Esto hará que el error aparezca en tu recuadro rojo nativo sin necesidad de agregar HTML
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'fecha_inicio' => 'Ya tienes un permiso activo que coincide con estas fechas. Selecciona un rango diferente.',
                ]);
            }
        }

        // Creación del modelo
        $permiso = new Permiso;
        $permiso->id_empleado = $empleado->id;
        $permiso->id_tipo_permiso = $request->id_tipo_permiso;
        $permiso->motivo = $request->motivo;

        // LÓGICA DE ESTADOS
        $permiso->estado = 0;
        $permiso->app_creacion = 2;
        $permiso->estado_solicitud = 1;

        // Campos dinámicos
        if ($request->has('cantidad_mts')) {
            $permiso->cantidad_mts = $request->cantidad_mts;
        }
        if ($request->has('valor')) {
            $permiso->valor = $request->valor;
        }
        if ($request->has('fecha_inicio')) {
            $permiso->fecha_inicio = $request->fecha_inicio;
        }
        if ($request->has('fecha_fin')) {
            $permiso->fecha_fin = $request->fecha_fin;
        }
        if ($request->has('dias_activa')) {
            $permiso->dias_activa = $request->dias_activa;
        }

        if ($request->ubicacion_libre == '1') {
            $permiso->cantidad_mts = null;
        }

        $permiso->save();

        try {
            // 1. Correo al empleado solicitante
            if ($empleado->user && $empleado->user->email) {
                Mail::to($empleado->user->email)->send(new SolicitudPermisoEmpleadoMail($permiso));
            }

            // 2. Correo al encargado principal de la sucursal
            $correoAdmin = $empleado->sucursal->correo_encargado ?? 'admin@tuempresa.com';
            Mail::to($correoAdmin)->send(new SolicitudPermisoAdminMail($permiso, $empleado));

            // 3. NUEVO: Correos a usuarios Rol 2 Encargados de la misma sucursal, activos y que no sean el Admin
            $correosRol2 = User::join('empleados', 'users.id_empleado', '=', 'empleados.id')
                ->where('users.id_rol', 2)
                ->where('empleados.estado', 1) // Empleado activo
                ->where('empleados.id_sucursal', $empleado->id_sucursal) // De la misma sucursal
                ->where('users.email', '!=', $correoAdmin) // Evitar duplicar el correo del paso 2
                ->whereNotNull('users.email') // Prevenir errores si hay nulos
                ->pluck('users.email')
                ->unique() // Elimina posibles correos duplicados en el array final
                ->toArray();
            //dd($correosRol2);
            if (! empty($correosRol2)) {
                foreach ($correosRol2 as $correo) {
                    Mail::to($correo)->send(new SolicitudPermisoAdminMail($permiso, $empleado));
                }
            }

            // 4. Correo a Super Administradores (Rol 1)
            $correoSuperAdmin = User::where('id_rol', 1)
                ->whereNotNull('email')
                ->pluck('email')
                ->toArray();

            if (!empty($correoSuperAdmin)) {
                foreach ($correoSuperAdmin as $correoSuper) {
                    Mail::to($correoSuper)->send(new SolicitudPermisoAdminMail($permiso, $empleado));
                }
            }

        } catch (\Exception $e) {
            Log::error('Error al enviar correo de permiso: '.$e->getMessage());
        }

        return redirect()->route('marcacion.permisos.index')
            ->with('success', '¡Tu solicitud ha sido enviada a jefatura para revisión!');
    }

    public function index(Request $request)
    {
        $empleado = Auth::user()->empleado;

        // Valores por defecto
        $desde = $request->input('desde', now()->startOfMonth()->toDateString());
        $hasta = $request->input('hasta', now()->endOfMonth()->toDateString());
        $origen = $request->input('origen', 'todos');
        $estado_filtro = $request->input('estado_filtro', 'todos');

        // Iniciar la consulta base
        $query = Permiso::with('tipoPermiso')->where('id_empleado', $empleado->id);

        // 🌟 REGLA DE NEGOCIO: Omitir fechas y origen si se filtra por estado
        if ($estado_filtro !== 'todos') {

            if ($estado_filtro === 'activos') {
                $query->where('estado', 1);
            } elseif ($estado_filtro === 'pendientes') {
                $query->where('estado_solicitud', 1);
            } elseif ($estado_filtro === 'inactivos') {
                $query->where('estado', 0)->where('estado_solicitud', '!=', 1);
            }

        } else {
            // 🌟 SOLO APLICAR SI ESTADO ES "TODOS"

            // Filtro por Fechas
            if ($desde) {
                $query->whereDate('created_at', '>=', $desde);
            }
            if ($hasta) {
                $query->whereDate('created_at', '<=', $hasta);
            }

            // Filtro por Origen
            if ($origen === 'mios') {
                $query->where('app_creacion', 2);
            } elseif ($origen === 'admin') {
                $query->where(function ($q) {
                    $q->where('app_creacion', 1)
                        ->orWhere('estado_solicitud', 0)
                        ->orWhereNull('app_creacion')
                        ->orWhere('app_creacion', 0);
                });
            }
        }

        $permisos = $query->orderBy('created_at', 'desc')->get();

        return view('app_marcacion.permisos.mis_permisos', compact('permisos', 'desde', 'hasta', 'origen', 'estado_filtro'));
    }

    public function destroy($id)
    {
        $empleado = Auth::user()->empleado;

        $permiso = Permiso::where('id', $id)
            ->where('id_empleado', $empleado->id)
            ->firstOrFail();

        if ($permiso->estado_solicitud != 1) {
            return redirect()->route('marcacion.permisos.index')
                ->with('error', 'No puedes eliminar esta solicitud porque ya ha sido procesada por jefatura.');
        }

        $permiso->delete();

        return redirect()->route('marcacion.permisos.index')
            ->with('success', 'La solicitud de permiso ha sido cancelada correctamente.');
    }
}
