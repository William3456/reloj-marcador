<?php

namespace App\Http\Controllers\Departamentos;

use App\Http\Controllers\Controller;
use App\Models\Departamento\Departamento;
use App\Models\Sucursales\Sucursal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DepartamentosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $departamentos = Departamento::with('sucursal')
            ->whereHas('sucursal', function ($query) {
                $query->visiblePara(Auth::user());
            })
            ->get();

        return view('departamentos.index', compact('departamentos'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $sucursales = Sucursal::visiblePara(Auth::user())->where('estado', 1)->get();

        return view('departamentos.create', compact('sucursales'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'id_sucursal' => 'required',
            'estado' => 'required|in:0,1',
        ]);

        $depto = Departamento::create([
            'nombre_depto' => $validated['name'],
            'sucursal_id' => $validated['id_sucursal'],
            'estado' => $validated['estado'],
            'cod_depto' => '',
        ]);
        $codDepto = $codDepto = $this->generarCodigoDepartamento($validated['name'], $depto->id);
        $depto->cod_depto = $codDepto;
        $depto->save();

        return redirect()
            ->route('departamentos.create')
            ->with('success', 'Departamento creado correctamente.');
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
        $depto = Departamento::visiblePara(Auth::user())->
        findOrFail(id: $id);
        $sucursales = Sucursal::visiblePara(Auth::user())->where('estado', 1)->get();

        return view('departamentos.edit', compact('depto', 'sucursales'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'id_sucursal' => 'required',
            'estado' => 'required|in:0,1',
        ]);

        $depto = Departamento::visiblePara(Auth::user())->
        findOrFail(id: $id);

        $depto->nombre_depto = $validated['name'];
        $depto->sucursal_id = $validated['id_sucursal'];
        $depto->estado = $validated['estado'];
        $codDepto = $this->generarCodigoDepartamento($validated['name'], $depto->id);
        $depto->cod_depto = strtoupper($codDepto);
        $depto->save();

        return redirect()
            ->route('departamentos.index', $depto->id)
            ->with('success', 'Departamento actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $depto = Departamento::visiblePara(Auth::user())->
        findOrFail(id: $id);
        $depto->delete();

        return redirect()
            ->route('departamentos.index')
            ->with('success', 'Departamento eliminado correctamente.');
    }

    public function generarCodigoDepartamento(string $nombre, int $correlativo): string
    {
        // Limpiar espacios extra
        $nombre = trim($nombre);

        // Normalizar acentos (opcional pero recomendado)
        $nombre = iconv('UTF-8', 'ASCII//TRANSLIT', $nombre);

        // Separar palabras
        $palabras = preg_split('/\s+/', $nombre);

        if (count($palabras) === 1) {
            // Una sola palabra → primeras 3 letras
            $codigoTexto = strtoupper(substr($palabras[0], 0, 3));
        } else {
            // Varias palabras
            $codigoTexto = strtoupper(substr($palabras[0], 0, 3));

            // Tomar 2 letras de cada palabra siguiente
            for ($i = 1; $i < count($palabras); $i++) {
                $codigoTexto .= strtoupper(substr($palabras[$i], 0, 2));
            }
        }

        // Correlativo a 4 dígitos
        $codigoNumero = str_pad($correlativo, 4, '0', STR_PAD_LEFT);

        return $codigoTexto.$codigoNumero;
    }
}
