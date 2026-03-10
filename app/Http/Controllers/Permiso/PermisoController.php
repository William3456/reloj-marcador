<?php

namespace App\Http\Controllers\Permiso;

use App\Http\Controllers\Controller;
use App\Mail\SolicitudPermisoAdminMail;
use App\Mail\SolicitudPermisoEmpleadoMail;
use App\Models\Empleado\Empleado;
use App\Models\Permiso\Permiso;
use App\Models\Permiso\TipoPermiso;
use App\Models\Sucursales\Sucursal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PermisoController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // 1. OBTENER SOLICITUDES PENDIENTES (App Móvil)
        // Buscamos permisos en estado_solicitud = 1 de las sucursales visibles para este admin
        $sucursalesVisiblesIds = Sucursal::visiblePara($user)->pluck('id');

        $pendientes = Permiso::with(['empleado.sucursal', 'tipo'])
            ->where('estado_solicitud', 1)
            ->whereHas('empleado', function ($q) use ($sucursalesVisiblesIds) {
                $q->whereIn('id_sucursal', $sucursalesVisiblesIds);
            })
            ->orderBy('created_at', 'asc') // Los más antiguos primero (First In, First Out)
            ->get();

        // 2. OBTENER EL DIRECTORIO NORMAL (Omitiendo los pendientes)
        $sucursales = Sucursal::visiblePara($user)->whereHas('empleados.permisos')
            ->with([
                'empleados' => function ($q) {
                    $q->whereHas('permisos', function ($p) {
                        $p->where('estado_solicitud', '!=', 1); // Excluimos los que están en revisión
                    })
                        ->with([
                            'permisos' => function ($p) {
                                $p->where('estado_solicitud', '!=', 1)
                                    ->orderBy('estado', 'desc'); // 1 primero, luego 0
                            },
                            'permisos.tipo',
                        ]);
                },
            ])
            ->orderBy('nombre')
            ->get();

        return view('permisos.index', compact('sucursales', 'pendientes'));
    }

    public function create()
    {
        $empleados = Empleado::where('estado', 1)->get();
        $tiposPermiso = TipoPermiso::where('estado', 1)->get();
        $sucursales = Sucursal::visiblePara(Auth::user())->where('estado', 1)->get();

        return view('permisos.create', compact('empleados', 'tiposPermiso', 'sucursales'));
    }

    public function store(Request $request)
    {
        // Pasamos null porque no hay ID al crear
        $data = $this->buildPermisoData($request, null); 

        $permiso = Permiso::create($data);
        $empleado = Empleado::with('user', 'sucursal')->findOrFail($permiso->id_empleado);

        try {
            if ($empleado->user && $empleado->user->email) {
                Mail::to($empleado->user->email)->send(new SolicitudPermisoEmpleadoMail($permiso));
            }

            $correoAdmin = $empleado->sucursal->correo ?? 'admin@tuempresa.com';
            Mail::to($correoAdmin)->send(new SolicitudPermisoAdminMail($permiso, $empleado));

            $correoSuperAdmin = User::where('id_rol', 1)->pluck('email')->toArray();
            Mail::to($correoSuperAdmin)->send(new SolicitudPermisoAdminMail($permiso, $empleado));

        } catch (\Exception $e) {
            Log::error('Error al enviar correo de permiso creado desde panel: '.$e->getMessage());
        }

        return redirect()->route('permisos.index')->with('success', 'Permiso asignado correctamente.');
    }

    public function update(Request $request, string $id)
    {
        $permiso = Permiso::findOrFail($id);
        
        // Pasamos el ID para que la validación lo excluya del cruce de fechas
        $data = $this->buildPermisoData($request, $id); 
        
        $permiso->update($data);

        return redirect()->route('permisos.index')->with('success', 'Permiso actualizado correctamente.');
    }

    // 🌟 AÑADIMOS EL PARÁMETRO $id (por defecto null)
    private function buildPermisoData(Request $request, $id = null): array
    {
        $request->validate([
            'id_empleado' => 'required|exists:empleados,id',
            'id_tipo_permiso' => 'required|exists:tipos_permiso,id',
            'motivo' => 'nullable|string|max:255',
            'estado' => 'required|in:0,1',
        ]);

        $tipo = TipoPermiso::findOrFail($request->id_tipo_permiso);
        $esEditar = $id !== null; // Evaluamos si es edición de forma más segura

        $esFueraRango = $tipo->requiere_distancia;
        $ubicacionLibre = $esFueraRango && $request->filled('ubicacion_libre') && $request->ubicacion_libre == 1;

        if ($tipo->requiere_distancia && ! $ubicacionLibre) {
            $request->validate(['cantidad_mts' => 'required|integer|min:1']);
        }

        if ($tipo->requiere_dias) {
            $request->validate(['dias_activa' => 'required|integer|min:1']);
        }

        if ($tipo->requiere_fechas) {
            $rules = [
                'fecha_inicio' => ['required', 'date'],
                'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            ];
            
            if (! $esEditar) {
                $rules['fecha_inicio'][] = 'after_or_equal:today';
            }
            
            $request->validate($rules);

            // 🌟 NUEVA REGLA: VALIDACIÓN DE CRUCE DE FECHAS (PANEL)
            $cruce = Permiso::where('id_empleado', $request->id_empleado)
                ->where('id_tipo_permiso', $tipo->id)
                ->where('estado', 1) // Solo comparamos contra los activos
                ->where(function($query) use ($request) {
                    $query->where('fecha_inicio', '<=', $request->fecha_fin)
                          ->where('fecha_fin', '>=', $request->fecha_inicio);
                });

            // Si estamos editando, excluimos el permiso actual para no bloquearnos a nosotros mismos
            if ($esEditar) {
                $cruce->where('id', '!=', $id);
            }

            if ($cruce->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'fecha_inicio' => 'El empleado ya tiene un permiso activo de este tipo que se cruza con estas fechas.',
                ]);
            }
        }

        if (in_array($tipo->codigo, ['LLEGADA_TARDE', 'SALIDA_TEMPRANA']) && ! ($esFueraRango && $ubicacionLibre)) {
            $request->validate(['valor' => 'required|integer|min:1']);
        }

        if ($tipo->requiere_dias && $tipo->requiere_fechas) {
            abort(422, 'Configuración inválida del tipo de permiso.');
        }

        $data = [
            'id_empleado' => $request->id_empleado,
            'id_tipo_permiso' => $tipo->id,
            'motivo' => $request->motivo,
            'estado' => $request->estado,
            'app_creacion' => 1, 
            'estado_solicitud' => 0, 
        ];

        $data['cantidad_mts'] = ($tipo->requiere_distancia && ! $ubicacionLibre) ? $request->cantidad_mts : null;
        $data['valor'] = in_array($tipo->codigo, ['LLEGADA_TARDE', 'SALIDA_TEMPRANA']) ? $request->valor : null;

        if ($tipo->requiere_fechas) {
            $data['fecha_inicio'] = $request->fecha_inicio;
            $data['fecha_fin'] = $request->fecha_fin;
            $data['dias_activa'] = null;
        }

        if ($tipo->requiere_dias) {
            $data['dias_activa'] = $request->dias_activa;
            $data['fecha_inicio'] = Carbon::today();
            $data['fecha_fin'] = null;
        }

        return $data;
    }

    public function edit(string $id)
    {
        $permiso = Permiso::visiblePara(Auth::user())->with('empleado.sucursal')->findOrFail($id);
        $tiposPermiso = TipoPermiso::where('estado', 1)->get();
        $sucursales = Sucursal::visiblePara(Auth::user())->where('estado', 1)->get();

        return view('permisos.edit', compact('permiso', 'tiposPermiso', 'sucursales'));
    }

    public function destroy(string $id)
    {
        $permiso = Permiso::findOrFail($id);
        $permiso->delete();

        return redirect()->route('permisos.index')->with('success', 'Permiso eliminado correctamente.');
    }

    // 🌟 NUEVO MÉTODO: Procesar solicitud de la App
    public function procesar(Request $request, $id)
    {
        $request->validate(['accion' => 'required|in:aprobar,rechazar']);

        // Cargamos con el usuario para poder mandarle el correo
        $permiso = Permiso::with('empleado.user')->findOrFail($id);

        if ($request->accion === 'aprobar') {
            $permiso->estado_solicitud = 2; // Aprobado
            $permiso->estado = 1; // Activo
            $mensaje = 'Solicitud aprobada correctamente.';
        } else {
            $permiso->estado_solicitud = 3; // Rechazado
            $permiso->estado = 0; // Inactivo
            $mensaje = 'Solicitud rechazada.';
        }

        $permiso->save();

        // 🌟 ENVIAR CORREO DE RESPUESTA AL EMPLEADO
        try {
            $empleado = $permiso->empleado;
            if ($empleado->user && $empleado->user->email) {
                Mail::to($empleado->user->email)->send(new SolicitudPermisoEmpleadoMail($permiso));
            }
        } catch (\Exception $e) {
            Log::error('Error al enviar correo de procesamiento de permiso: '.$e->getMessage());
        }

        return redirect()->route('permisos.index')->with('success', $mensaje);
    }
}
