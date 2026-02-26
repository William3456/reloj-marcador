<?php

namespace App\Http\Controllers\Horarios;

use App\Http\Controllers\Controller;
use App\Models\Horario\horario;
use App\Models\Horario\HorarioHistorico;
use App\Models\HorarioSucursal\HorarioSucursal;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HorarioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $horarios = horario::orderBy('permitido_marcacion', 'asc')->get();

        return view('horarios.index', [
            'horarios' => $horarios,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $dias = config('dias.laborales');

        return view('horarios.create', compact('dias'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'hora_ini' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i', 'different:hora_ini'],
            'permitido_marcacion' => ['required', 'in:0,1'],
            'estado' => ['required', 'in:0,1'],
            'tolerancia' => ['required', 'integer', 'min:0'],
            'requiere_salida' => ['required', 'in:0,1'],
            'turno_txt' => ['required', 'string'],
            'turno' => ['required', 'integer'],
            'dias' => 'array|required|min:1',
        ]);
        $user = Auth::user();
        if (! isset($user->empleado->id_sucursal)) {
            $validated['sucursal_creacion'] = 0;
        } else {
            $validated['sucursal_id'] = $user->empleado->id_sucursal;
            $validated['sucursal_creacion'] = $validated['sucursal_id'];
        }
     
        $contaErrr = 0;

        // 1. Forzamos el orden y aseguramos que las tildes no se codifiquen (JSON_UNESCAPED_UNICODE)
        $diasJson = collect($validated['dias'])
            ->map(fn ($d) => mb_strtolower($d)) // Normalizamos a minúsculas
            ->sort()
            ->values()
            ->toJson(JSON_UNESCAPED_UNICODE);

        // 2. Buscamos la coincidencia
        $existe = Horario::where('hora_ini', $validated['hora_ini'])
            ->where('hora_fin', $validated['hora_fin'])
            ->where('permitido_marcacion', $validated['permitido_marcacion'])
            ->where('tolerancia', $validated['tolerancia'])
            ->where('requiere_salida', $validated['requiere_salida'])
            ->where('estado', $validated['estado'])
            // Usamos la función JSON de la BD para comparar objetos, no texto plano
            ->whereRaw('JSON_CONTAINS(dias, ?) AND JSON_LENGTH(dias) = ?', [
                $diasJson,
                count($validated['dias']),
            ])
            ->exists();

        if ($existe) {
            $contaErrr = 1;
        }

        if ($contaErrr > 0) {
            return back()->with('error', 'No se puede crear un horario con los mismos parámetros')->withInput();
        } else {
            horario::create($validated);

            return redirect()->route('horarios.create')->with('success', 'Horario creado correctamente.');
        }
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
        $horario = horario::findOrFail($id);
        $dias = config('dias.laborales');

        return view('horarios.edit', compact('horario', 'dias'));
    }

    /**
     * Update the specified resource in storage.
     */
public function update(Request $request, string $id)
{
    $validated = $request->validate([
        'hora_ini' => ['required', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
        'hora_fin' => ['required', 'regex:/^\d{2}:\d{2}(:\d{2})?$/', 'different:hora_ini'],
        'permitido_marcacion' => ['required', 'in:0,1'],
        'estado' => ['required', 'in:0,1'],
        'tolerancia' => ['required', 'integer', 'min:0'],
        'requiere_salida' => ['required', 'in:0,1'],
        'turno_txt' => ['required', 'string'],
        'turno' => ['required', 'integer'],
        'dias' => 'array|required|min:1',
    ]);

    $horario = horario::findOrFail($id);

    try {
        DB::transaction(function () use ($horario, $validated) {

            // 2. Actualizar el Maestro
            $horario->update($validated);

            // CORRECCIÓN: Formatear horas a H:i:s para evitar el error de "Not enough data"
            // Usamos $validated para tomar el dato crudo que envió el usuario
            $horaEntradaFormat = Carbon::parse($validated['hora_ini'])->format('H:i:s');
            $horaSalidaFormat = Carbon::parse($validated['hora_fin'])->format('H:i:s');

            // 3. Manejo del Histórico

            // A. Cerrar vigente actual
            HorarioHistorico::where('id_horario', $horario->id)
                ->vigente() // Asegúrate que este scope solo haga whereNull('vigente_hasta')
                ->update(['vigente_hasta' => now()]);

            // B. Crear nuevo histórico
            $nuevoHistorico = HorarioHistorico::create([
                'id_horario'      => $horario->id,
                'tipo_horario'    => $horario->permitido_marcacion,
                'hora_entrada'    => $horaEntradaFormat, 
                'hora_salida'     => $horaSalidaFormat,  
                'tolerancia'      => $horario->tolerancia,
                'dias'            => $horario->dias, 
                'vigente_desde'   => now(),
                'vigente_hasta'   => null,
            ]);

            // 4. ACTUALIZACIÓN DE TABLAS INTERMEDIAS
            $dataPivot = [
                'id_horario_historico' => $nuevoHistorico->id,
                'updated_at' => now(),
            ];

            if ($horario->permitido_marcacion == 1) { // Sucursal
                DB::table('horarios_sucursales')
                    ->where('id_horario', $horario->id)
                    ->update($dataPivot);
            } elseif ($horario->permitido_marcacion == 0) { // Trabajador
                DB::table('horarios_trabajadores')
                    ->where('id_horario', $horario->id)
                    ->update($dataPivot);
            }
        });

        return redirect()->route('horarios.index')->with('success', 'Horario actualizado y versionado correctamente.');

    } catch (\Exception $e) {
        return back()->with('error', 'Error al actualizar: '.$e->getMessage());
    }
}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $horario = horario::findOrFail($id);
        $tieneSuc = HorarioSucursal::where('id_horario', $horario->id)->first();
        if ($tieneSuc) {
            return redirect()->route('horarios.index')->with('error', 'No se puede eliminar el horario porque está asociado a una o más sucursales.');
        } else {
            $horario->delete();

            return redirect()->route('horarios.index')->with('success', 'Horario eliminado correctamente.');
        }

    }
}
