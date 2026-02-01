<?php

namespace App\Http\Controllers\Horarios;

use App\Http\Controllers\Controller;
use App\Models\Horario\horario;
use App\Models\HorarioSucursal\HorarioSucursal;
use App\Models\Sucursales\Sucursal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HorarioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $horarios = horario::get();
        
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
        if(!isset($user->empleado->id_sucursal)){
            $validated['sucursal_creacion'] = 0;
        }else{
            $validated['sucursal_id'] = $user->empleado->id_sucursal;
        }
        

        $contaErrr = 0;
        if (horario::where('hora_ini', $validated['hora_ini'])->exists() && 
            horario::where('hora_fin', $validated['hora_fin'])->exists() && 
            horario::where('permitido_marcacion', $validated['permitido_marcacion'])->exists() &&
            horario::where('tolerancia', $validated['tolerancia'])->exists() &&
            horario::where('requiere_salida', $validated['requiere_salida'])->exists() &&
            horario::where('estado', $validated['estado'])->exists()) {
            $contaErrr++;
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
        $actualizado = $horario->update($validated);
        if (! $actualizado) {
            return back()->with('error', 'No se pudo actualizar el horario.');
        }

        return redirect()->route('horarios.index')->with('success', 'Horario actualizado correctamente.');
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
