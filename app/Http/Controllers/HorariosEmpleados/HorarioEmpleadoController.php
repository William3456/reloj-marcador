<?php

namespace App\Http\Controllers\HorariosEmpleados;

use App\Http\Controllers\Controller;
use App\Models\Empleado\Empleado;
use App\Models\Horario\horario;
use App\Models\HorarioEmpleado\HorarioEmpleado;
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
            $horarios = horario::where('id', $sucursal->id_horario)->first();
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
                ->with(['puesto', 'departamento', 'sucursal', 'empresa', 'horarios'])
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

            // ELIMINAR
            if ($request->filled('eliminados')) {
                foreach ($request->eliminados as $empleadoID => $horarios) {
                    HorarioEmpleado::where('id_empleado', $empleadoID)
                        ->whereIn('id_horario', $horarios)
                        ->delete();
                }
            }

            // INSERTAR
            if ($request->filled('nuevos')) {
                foreach ($request->nuevos as $empleadoID => $horarios) {
                    foreach ($horarios as $horarioID) {

                        HorarioEmpleado::firstOrCreate([
                            'id_empleado' => $empleadoID,
                            'id_horario' => $horarioID,
                        ]);
                    }
                }
            }
        });

        return back()->with('success', 'Horarios actualizados correctamente');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
