<?php

namespace App\Http\Controllers\Sucursales;

use App\Http\Controllers\Controller;
use App\Models\Empresa\Empresa;
use App\Models\Estado\Estado;
use App\Models\Horario\horario;
use App\Models\Sucursales\Sucursal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SucursalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $sucursales = Sucursal::visiblePara(Auth::user())
        ->with('empresa')->get();

        return view('sucursales.index', compact('sucursales'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $empresas = Empresa::all();
        $horarios = horario::where('permitido_marcacion', '=', 1)->get();

        // $estados = Estado::all();
        $estados = collect([
            (object) ['id' => 1, 'nombre_estado' => 'activo'],
            (object) ['id' => 0, 'nombre_estado' => 'inactivo'],
        ]);
        $dias = config('dias.laborales');

        return view('sucursales.create', compact('empresas', 'horarios', 'estados', 'dias'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100',
            'direccion' => 'required|string|max:500',
            'correo_encargado' => 'required|email|max:150',
            'id_empresa' => 'required|exists:empresas,id',
            'id_horario' => 'required|exists:horarios,id',
            'cant_empleados' => 'required|integer|min:1',
            'rango_marcacion_mts' => 'required|integer|min:1',
            'estado' => 'required|exists:estados,id',
            'latitud' => 'required|numeric',
            'longitud' => 'required|numeric',
            'dias_laborales' => 'array|required|min:1',
            'telefono'=>'required|max:10',
            'margen_error_gps_mts' => 'required|integer|min:1',
        ]);
        Sucursal::create($validated);

        return redirect()->route('sucursales.create')->with('success', 'Sucursal creada exitosamente.');
        // return view('sucursales.create', compact('empresas', 'horarios', 'estados'));
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

        $sucursal = Sucursal::visiblePara(Auth::user())->findOrFail($id);
        $horarios = horario::where('permitido_marcacion', '=', 1)->get();
        $estados = collect([
            (object) ['id' => 1, 'nombre_estado' => 'activo'],
            (object) ['id' => 0, 'nombre_estado' => 'inactivo'],
        ]);
        $empresas = Empresa::all();

        $dias = config('dias.laborales');

        return view('sucursales.edit', compact('sucursal', 'horarios', 'estados', 'empresas', 'dias'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Validación
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'direccion' => 'required|string',
            'correo_encargado' => 'required|email',
            'id_empresa' => 'required|exists:empresas,id',
            'id_horario' => 'required|exists:horarios,id',
            'cant_empleados' => 'required|integer|min:1',
            'rango_marcacion_mts' => 'required|integer|min:1',
            'estado' => 'required|exists:estados,id',
            'telefono'=>'required|max:10',
            'margen_error_gps_mts' => 'required|integer|min:1',

            // días laborales: al menos 1 requerido
            'dias_laborales' => 'required|array|min:1',

            'latitud' => 'required|numeric',
            'longitud' => 'required|numeric',
        ]);

        // 2. Buscar la sucursal
        $sucursal = Sucursal::findOrFail($id);

        // 3. Actualizar
        $actualizado = $sucursal->update($validated);

        if (! $actualizado) {
            return back()->with('error', 'No se pudo actualizar la sucursal.');
        }

        // 4. Redirigir
        return redirect()
            ->route('sucursales.index', $id)
            ->with('success', 'Sucursal actualizada correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $sucursal = Sucursal::findOrFail($id);
        $sucursal->estado = 0;

        if (! $sucursal->save()) {
            return back()->with('error', 'No se pudo desactivar la sucursal.');
        }

        return redirect()
            ->route('sucursales.index')
            ->with('success', 'Sucursal inactivada correctamente.');
    }
}
