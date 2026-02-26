<?php

namespace App\Http\Controllers\HorariosSucursal;

use App\Http\Controllers\Controller;
use App\Models\Horario\horario;
use App\Models\HorarioSucursal\HorarioSucursal;
use App\Models\Sucursales\Sucursal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HorarioSucursalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $sucursales = Sucursal::visiblePara(Auth::user())->where('estado', '=', 1)->get();
        $horarios = horario::where('estado', 1)
            ->where('permitido_marcacion', 1)
            ->orderBy('turno_txt')
            ->get();

        return view('sucursales.asignar_horarios', compact('sucursales', 'horarios'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // 1. Validación
        $request->validate([
            'id_sucursal' => 'required|exists:sucursales,id',
            'horarios_ids' => 'nullable|array', // Puede ser null si borras todos
            'horarios_ids.*' => 'exists:horarios,id',
        ]);

        $idSucursal = $request->input('id_sucursal');
        $nuevosHorarios = $request->input('horarios_ids', []);

        try {
            DB::beginTransaction();

            $fechaHoy = now()->toDateString();
            $fechaAyer = now()->subDay()->toDateString();

            // Obtenemos los IDs de los horarios que la sucursal tiene activos actualmente
            $activosActualmente = HorarioSucursal::where('id_sucursal', $idSucursal)
                ->where('es_actual', true)
                ->pluck('id_horario')
                ->toArray();

            // Magia de PHP: Comparamos arrays para saber qué hacer
            // 1. A Cerrar: Los que están activos pero ya NO vienen en la nueva lista
            $horariosACerrar = array_diff($activosActualmente, $nuevosHorarios);

            // 2. A Agregar: Los que vienen en la nueva lista pero NO estaban activos
            $horariosAAgregar = array_diff($nuevosHorarios, $activosActualmente);

            // CERRAR ANTERIORES (Historial)
            if (! empty($horariosACerrar)) {
                HorarioSucursal::where('id_sucursal', $idSucursal)
                    ->whereIn('id_horario', $horariosACerrar)
                    ->where('es_actual', true)
                    ->update([
                            'fecha_fin' => DB::raw("
                                CASE 
                                    WHEN fecha_inicio = '{$fechaHoy}' THEN '{$fechaHoy}'
                                    ELSE '{$fechaAyer}'
                                END
                            "),
                        'es_actual' => false,
                    ]);
            }

            // CREAR NUEVOS
            if (! empty($horariosAAgregar)) {
                foreach ($horariosAAgregar as $idHorario) {
                    HorarioSucursal::create([
                        'id_sucursal' => $idSucursal,
                        'id_horario' => $idHorario,
                        'fecha_inicio' => $fechaHoy,
                        'fecha_fin' => null,
                        'es_actual' => true,
                    ]);
                }
            }

            DB::commit();

            return redirect()->back()
                ->with('success', 'Asignación de horarios actualizada correctamente.');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'Error al guardar la asignación: '.$e->getMessage());
        }
    }

    public function getBySucursal($idSucursal)
    {
        // IMPORTANTE: Agregamos el filtro ->where('es_actual', true) para que
        // el frontend (JS) solo cargue los horarios vigentes de la sucursal
        $asignaciones = HorarioSucursal::where('id_sucursal', $idSucursal)
            ->where('es_actual', true)
            ->whereNull('fecha_fin') // Doble seguridad
            ->with('horario') // Carga la relación definida en tu modelo
            ->get();

        // Mapeamos para devolver solo la info necesaria para tu tabla JS
        $data = $asignaciones->map(function ($item) {
            return [
                'id' => $item->horario->id,
                'hora_ini' => $item->horario->hora_ini,
                'hora_fin' => $item->horario->hora_fin,
                'turno_txt' => $item->horario->turno_txt,
                'dias' => $item->horario->dias,
            ];
        });

        return response()->json($data);
    }

    public function create()
    {
        //
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
