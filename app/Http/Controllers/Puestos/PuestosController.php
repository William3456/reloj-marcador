<?php

namespace App\Http\Controllers\Puestos;

use App\Http\Controllers\Controller;
use App\Models\Puesto\Puesto;
use App\Models\Sucursales\Sucursal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PuestosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index() {
        $puestos = Puesto::with('sucursal')->visiblePara(Auth::user())->get();

        return view('puestos.index', compact('puestos'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $sucursales = Sucursal::visiblePara(Auth::user())->where('estado', 1)->get();

        return view('puestos.create', compact('sucursales'));
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

        $puesto = Puesto::create([
            'desc_puesto' => $validated['name'],
            'sucursal_id' => $validated['id_sucursal'],
            'estado' => $validated['estado'],
            'cod_puesto' => '',
        ]);
        $codPuesto = $this->generarCodigoPuesto($validated['name'], $puesto->id);
        $puesto->cod_puesto = $codPuesto;
        $puesto->save();

        return redirect()
            ->route('puestos.create')
            ->with('success', 'Puesto creado correctamente.');
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
        $puesto = Puesto::visiblePara(Auth::user())->
        findOrFail(id: $id);
        $sucursales = Sucursal::visiblePara(Auth::user())->where('estado', 1)->get();

        return view('puestos.edit', compact('puesto', 'sucursales'));
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

        $puesto = Puesto::visiblePara(Auth::user())->
        findOrFail(id: $id);

        $puesto->desc_puesto = $validated['name'];
        $puesto->sucursal_id = $validated['id_sucursal'];
        $puesto->estado = $validated['estado'];
        $codPuesto = $this->generarCodigoPuesto($validated['name'], $puesto->id);
        $puesto->cod_puesto = strtoupper($codPuesto);
        $puesto->save();

        return redirect()
            ->route('puestos.index')
            ->with('success', 'Puesto actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $puesto = Puesto::visiblePara(Auth::user())->
        findOrFail(id: $id);
        $puesto->delete();

        return redirect()
            ->route('puestos.index')
            ->with('success', 'Puesto eliminado correctamente.');
    }

    public function generarCodigoPuesto(string $nombre, int $correlativo): string
    {
        // Limpiar espacios extra
        $nombre = trim($nombre);

        // Normalizar acentos (opcional pero recomendado)
        $nombre = iconv('UTF-8', 'ASCII//TRANSLIT', $nombre);

        // Separar palabras
        $palabras = preg_split('/\s+/', $nombre);

        if (count($palabras) === 1) {
            // Una sola palabra → primeras 3 letras
            $codigoTexto = strtoupper(substr($palabras[0], 0, 2));
        } else {
            // Varias palabras
            $codigoTexto = strtoupper(substr($palabras[0], 0, 2));

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
