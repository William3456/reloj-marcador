<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Empresa\Empresa;
use Illuminate\Http\Request;

class EmpresaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
        $validated = $request->validate([
            'nombre'          => 'required|string|max:100|min:10',
            'direccion'       => 'required|string|max:250',
            'telefono'        => 'required|string|max:13',
            'registro_fiscal' => 'required|string|max:20',
            'nit'             => 'required|string|max:17',
            'dui'             => 'required|string|max:10',
            'correo'          => 'required|email|max:150',
        ]);
        
        // 1. Buscamos el primer registro de la tabla (la única empresa)
        $empresa = Empresa::first();

        if ($empresa) {
            // 2. Si ya existe, simplemente actualizamos sus datos
            $empresa->update($validated);
            $mensaje = 'Empresa actualizada correctamente.';
        } else {
            // 3. Si la tabla está vacía, creamos el primer y único registro
            $empresa = Empresa::create($validated);
            $mensaje = 'Empresa creada correctamente.';
        }

        // 4. Retornamos la respuesta a tu AJAX (jQuery)
        return response()->json([
            'success' => true,
            'empresa' => $empresa,
            'message' => $mensaje
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show()
    {
        $empresa = Empresa::first();

        if (! $empresa) {
            return response()->json([
                'success' => false,
                'message' => 'No hay empresa registrada aún.',
            ], 200);
        }

        return response()->json([
            'success' => true,
            'empresa' => $empresa,
        ]);

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
