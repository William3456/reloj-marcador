<?php

namespace App\Http\Controllers\Horarios;

use App\Http\Controllers\Controller;
use App\Models\Horario\horario;
use App\Models\Sucursales\Sucursal;
use Illuminate\Http\Request;

class HorarioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('horarios.index', [
            'horarios' => horario::all(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('horarios.create');
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
        ]);

        horario::create($validated);

        return redirect()->route('horarios.create')->with('success', 'Horario creado correctamente.');

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

        return view('horarios.edit', compact('horario'));
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
        $tieneSuc = Sucursal::where('id_horario', $horario->id)->first();
        if ($tieneSuc) {
            return redirect()->route('horarios.index')->with('error', 'No se puede eliminar el horario porque está asociado a una o más sucursales.');
        } else {
            $horario->delete();

            return redirect()->route('horarios.index')->with('success', 'Horario eliminado correctamente.');
        }

    }
}
