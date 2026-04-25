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
            // 1. Correo al empleado solicitante
            if ($empleado->user && $empleado->user->email) {
                Mail::to($empleado->user->email)->send(new SolicitudPermisoEmpleadoMail($permiso));
            }

            // 2. Correo al encargado principal de la sucursal
            $correoAdmin = $empleado->sucursal->correo_encargado ?? 'admin@tuempresa.com';
            Mail::to($correoAdmin)->send(new SolicitudPermisoAdminMail($permiso, $empleado));

            // 3. Correos a usuarios Rol 2 Encargados de la misma sucursal
            $correosRol2 = \App\Models\User::join('empleados', 'users.id_empleado', '=', 'empleados.id')
                ->where('users.id_rol', 2)
                ->where('empleados.estado', 1) 
                ->where('empleados.id_sucursal', $empleado->id_sucursal) 
                ->where('users.email', '!=', $correoAdmin) 
                ->whereNotNull('users.email') 
                ->pluck('users.email')
                ->unique() 
                ->toArray();
            
            if (! empty($correosRol2)) {
                foreach ($correosRol2 as $correo) {
                    Mail::to($correo)->send(new SolicitudPermisoAdminMail($permiso, $empleado));
                }
            }

            // 4. Correo a Super Administradores (Rol 1)
            $correoSuperAdmin = \App\Models\User::where('id_rol', 1)
                ->whereNotNull('email')
                ->pluck('email')
                ->toArray();

            if (!empty($correoSuperAdmin)) {
                foreach ($correoSuperAdmin as $correoSuper) {
                    Mail::to($correoSuper)->send(new SolicitudPermisoAdminMail($permiso, $empleado));
                }
            }

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error al enviar correo de permiso creado desde panel: '.$e->getMessage());
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

    // AÑADIMOS EL PARÁMETRO $id (por defecto null)
    private function buildPermisoData(Request $request, $id = null): array
    {
        $request->validate([
            'id_empleado' => 'required|exists:empleados,id',
            'id_tipo_permiso' => 'required|exists:tipos_permiso,id',
            'motivo' => 'nullable|string|max:255',
            'estado' => 'required|in:0,1',
        ]);

        $tipo = TipoPermiso::findOrFail($request->id_tipo_permiso);
        $esEditar = $id !== null;

        $esFueraRango = $tipo->requiere_distancia;
        $ubicacionLibre = $esFueraRango && $request->filled('ubicacion_libre') && $request->ubicacion_libre == 1;

        // 🌟 LÓGICA: DISTANCIA Y DÍAS
        if ($tipo->requiere_distancia && ! $ubicacionLibre) {
            $request->validate(['cantidad_mts' => 'required|integer|min:1']);
        }

        if ($tipo->requiere_dias) {
            $request->validate(['dias_activa' => 'required|integer|min:1']);
        }

        // =========================================================================
        // NUEVA LÓGICA DE VALIDACIÓN: PERMISO POR HORAS VS RANGO DE FECHAS
        // =========================================================================
        $esPermisoPorHoras = ($tipo->codigo === 'PERMISO_POR_HORAS');
        
        $fechaInicioFinal = null;
        $fechaFinFinal = null;

        if ($esPermisoPorHoras) {
            // Validación específica para permiso de un solo día por horas
            $rulesHoras = [
                'fecha_inicio' => ['required', 'date'],
                'hora_ini' => ['required', 'date_format:H:i'],
                'hora_fin' => ['required', 'date_format:H:i', 'after:hora_ini'],
            ];

            if (! $esEditar) {
                $rulesHoras['fecha_inicio'][] = 'after_or_equal:today';
            }

            $request->validate($rulesHoras);

            // Ambas fechas son la misma porque ocurre en un solo día
            $fechaInicioFinal = $request->fecha_inicio;
            $fechaFinFinal = $request->fecha_inicio;

        } elseif ($tipo->requiere_fechas) {
            // Validación para rango de fechas tradicional
            $rulesFechas = [
                'fecha_inicio' => ['required', 'date'],
                'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            ];
            
            if (! $esEditar) {
                $rulesFechas['fecha_inicio'][] = 'after_or_equal:today';
            }
            
            $request->validate($rulesFechas);

            $fechaInicioFinal = $request->fecha_inicio;
            $fechaFinFinal = $request->fecha_fin;
        }

        // =========================================================================
        //  REGLA: VALIDACIÓN DE CRUCE DE FECHAS (PANEL)
        // =========================================================================
        if ($fechaInicioFinal && $fechaFinFinal) {
            $cruce = Permiso::where('id_empleado', $request->id_empleado)
                ->where('id_tipo_permiso', $tipo->id)
                ->where('estado', 1) // Solo comparamos contra los activos
                ->where(function($query) use ($fechaInicioFinal, $fechaFinFinal) {
                    $query->where('fecha_inicio', '<=', $fechaFinFinal)
                          ->where('fecha_fin', '>=', $fechaInicioFinal);
                });

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

        // =========================================================================
        // 🌟 CONSTRUCCIÓN DEL ARREGLO DE DATOS
        // =========================================================================
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

        // 🌟 Asignación de Fechas y Horas
        if ($esPermisoPorHoras || $tipo->requiere_fechas) {
            $data['fecha_inicio'] = $fechaInicioFinal;
            $data['fecha_fin'] = $fechaFinFinal;
            $data['dias_activa'] = null;
            
            // Solo guardamos horas si es el permiso específico
            $data['hora_ini'] = $esPermisoPorHoras ? $request->hora_ini : null;
            $data['hora_fin'] = $esPermisoPorHoras ? $request->hora_fin : null;
        }

        if ($tipo->requiere_dias) {
            $data['dias_activa'] = $request->dias_activa;
            $data['fecha_inicio'] = Carbon::today();
            $data['fecha_fin'] = null;
            $data['hora_ini'] = null;
            $data['hora_fin'] = null;
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
