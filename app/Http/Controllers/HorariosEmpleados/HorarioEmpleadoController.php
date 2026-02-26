<?php

namespace App\Http\Controllers\HorariosEmpleados;

use App\Http\Controllers\Controller;
use App\Models\Empleado\Empleado;
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
                ->with(['puesto', 'departamento', 'sucursal', 'empresa', 'horarios' => function($query) {
                    // FILTRO CRÍTICO: Solo cargar los horarios vigentes
                    $query->where('es_actual', true)
                          ->whereNull('fecha_fin'); 
                }])
                ->get();

            $empleados->each(function ($emp) {
                $emp->horarios->each(function ($h) {
                    $h->origen = 'Actual';
                });

                $emp->horarios_nuevos = [];
                $emp->horarios_eliminados = [];
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
            
            // Definimos las fechas de vigencia
            $fechaHoy = now()->toDateString();
            $fechaAyer = now()->subDay()->toDateString();

       
            if ($request->filled('eliminados')) {
                
                foreach ($request->eliminados as $empleadoID => $horarios) {
                    //dd($horarios);
                    // En lugar de hacer delete(), cerramos la vigencia del horario
                    HorarioEmpleado::where('id_empleado', $empleadoID)
                        ->whereIn('id_horario', $horarios)
                        ->where('es_actual', true) // Solo tocamos los que están vigentes
                        ->update([
                            'fecha_fin' => DB::raw("
                                CASE 
                                    WHEN fecha_inicio = '{$fechaHoy}' THEN '{$fechaHoy}'
                                    ELSE '{$fechaAyer}'
                                END
                            "),
                            'es_actual' => false
                        ]);
                }
            }

            // 2. PROCESAR "NUEVOS" (Abrir nuevo ciclo)
            if ($request->filled('nuevos')) {
                foreach ($request->nuevos as $empleadoID => $horarios) {
                    foreach ($horarios as $horarioID) {

                        // Obtenemos los detalles del horario original para guardar el id_horario_historico
                        $horarioOriginal = Horario::find($horarioID);
                        $id_historico = $horarioOriginal ? $horarioOriginal->id_horario_historico : null;

                        // Verificamos si ya existe uno igual activo para no duplicar
                        $existeActivo = HorarioEmpleado::where('id_empleado', $empleadoID)
                            ->where('id_horario', $horarioID)
                            ->where('es_actual', true)
                            ->exists();

                        // Si no existe uno activo, creamos el nuevo registro con la fecha de inicio
                        if (!$existeActivo) {
                            HorarioEmpleado::create([
                                'id_empleado' => $empleadoID,
                                'id_horario' => $horarioID,
                                'id_horario_historico' => $id_historico, // Si lo usas
                                'fecha_inicio' => $fechaHoy,
                                'fecha_fin' => null,
                                'es_actual' => true
                            ]);
                        }
                    }
                }
            }
        });

        return back()->with('success', 'Horarios actualizados y guardados en el historial correctamente');
    }


}
