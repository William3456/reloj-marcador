<?php

namespace App\Http\Controllers\HorariosEmpleados;

use App\Http\Controllers\Controller;
use App\Models\Empleado\Empleado;
use App\Models\Empleado\HomeOffice;
use App\Models\Horario\horario;
use App\Models\HorarioEmpleado\HorarioEmpleado;
use App\Models\HorarioSucursal\HorarioSucursal;
use App\Models\Sucursales\Sucursal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HorarioEmpleadoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $sucursales = Sucursal::visiblePara(Auth::user())->where('estado', '=', 1)->get();
        $horarios = horario::where('estado', 1)
            ->where('permitido_marcacion', 0)
            ->orderBy('turno_txt')
            ->get();

        return view('empleados.asignar_horarios', compact('sucursales', 'horarios'));
    }

    public function getSucursalDetails($id)
    {
        try {
            $sucursal = Sucursal::visiblePara(Auth::user())->findOrFail($id);
            $horarios = HorarioSucursal::where('id_sucursal', $sucursal->id)->with('horario')->get();
            $data = [
                'sucursal' => $sucursal,
                'horarios' => $horarios,
            ];

            return response()->json($data);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo los detalles de la sucursal',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getEmpleadosBySucursal($id)
    {
        try {
            $empleados = Empleado::where('id_sucursal', $id)
                ->where('estado', 1)
                ->with([
                    'puesto',
                    'departamento',
                    'sucursal',
                    'empresa',
                    'horarios' => function ($query) {
                        // FILTRO CRÍTICO: Solo cargar los horarios vigentes
                        $query->where('es_actual', true)
                            ->whereNull('fecha_fin');
                    },
                    'trabajo_remoto' => function ($query) {
                        $query->where('es_actual', true)->whereNull('fecha_fin');
                    },
                ])
                ->get();

            $empleados->each(function ($emp) {
                $emp->horarios->each(function ($h) {
                    $h->origen = 'Actual';
                });

                $emp->horarios_nuevos = [];
                $emp->horarios_eliminados = [];
                $emp->remoto_accion = null;
                $emp->remoto_pendiente = [];
            });

            return response()->json($empleados);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo los empleados de la sucursal',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        DB::transaction(function () use ($request) {

            $fechaHoy = now()->toDateString();
            $fechaAyer = now()->subDay()->toDateString();

            // 1. PROCESAR ELIMINADOS (Horarios presenciales)
            if ($request->filled('eliminados')) {
                foreach ($request->eliminados as $empleadoID => $horarios) {
                    HorarioEmpleado::where('id_empleado', $empleadoID)
                        ->whereIn('id_horario', $horarios)
                        ->where('es_actual', true)
                        ->update([
                            'fecha_fin' => DB::raw("CASE WHEN fecha_inicio = '{$fechaHoy}' THEN '{$fechaHoy}' ELSE '{$fechaAyer}' END"),
                            'es_actual' => false,
                        ]);
                }
            }

            // 2. PROCESAR NUEVOS (Horarios presenciales)
            if ($request->filled('nuevos')) {
                foreach ($request->nuevos as $empleadoID => $horarios) {
                    foreach ($horarios as $horarioID) {
                        $horarioOriginal = Horario::find($horarioID);
                        $id_historico = $horarioOriginal ? $horarioOriginal->id_horario_historico : null;

                        HorarioEmpleado::updateOrCreate(
                            [
                                'id_empleado' => $empleadoID,
                                'id_horario' => $horarioID,
                                'es_actual' => true,
                            ],
                            [
                                'id_horario_historico' => $id_historico,
                                'fecha_inicio' => $fechaHoy,
                                'fecha_fin' => null,
                            ]
                        );
                    }
                }
            }

            // ====================================================================
            // 3. PROCESAR "HOME OFFICE" USANDO EL MODELO
            // ====================================================================
            if ($request->filled('remoto_accion')) {
                foreach ($request->remoto_accion as $empleadoID => $accion) {

                    if ($accion === 'eliminar') {
                        // ACCIÓN: ELIMINAR (Cerrar ciclo, no borrar de la BD)
                        HomeOffice::where('id_empleado', $empleadoID)
                            ->where('es_actual', true)
                            ->update([
                                // Si se asignó hoy mismo y se elimina hoy mismo, la fecha de fin es hoy.
                                // Si viene de días atrás, la fecha de fin es ayer.
                                'fecha_fin' => DB::raw("CASE WHEN fecha_inicio = '{$fechaHoy}' THEN '{$fechaHoy}' ELSE '{$fechaAyer}' END"),
                                'es_actual' => false,
                            ]);

                    } elseif ($accion === 'asignar' && isset($request->remoto_dias[$empleadoID])) {
                        // ACCIÓN: ASIGNAR NUEVOS DÍAS

                        // 1. Cerramos el registro anterior (si existía alguno vigente)
                        HomeOffice::where('id_empleado', $empleadoID)
                            ->where('es_actual', true)
                            ->update([
                                'fecha_fin' => DB::raw("CASE WHEN fecha_inicio = '{$fechaHoy}' THEN '{$fechaHoy}' ELSE '{$fechaAyer}' END"),
                                'es_actual' => false,
                            ]);

                        // 2. Creamos el nuevo registro vigente con la nueva configuración de días
                        HomeOffice::create([
                            'id_empleado' => $empleadoID,
                            'dias' => $request->remoto_dias[$empleadoID],
                            'fecha_inicio' => $fechaHoy,
                            'fecha_fin' => null,
                            'es_actual' => true,
                            'estado' => 1,
                        ]);
                    }
                }
            }
        });

        return back()->with('success', 'Configuraciones de horarios y trabajo remoto actualizadas correctamente.');
    }
}
