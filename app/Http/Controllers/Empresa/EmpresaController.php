<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Empresa\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

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
            'nombre' => 'required|string|max:100|min:10',
            'direccion' => 'required|string|max:250',
            'telefono' => 'required|string|max:13',
            'registro_fiscal' => 'required|string|max:20',
            'nit' => 'required|string|max:17',
            'dui' => 'required|string|max:10',
            'correo' => 'required|email|max:150',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'favicon' => 'nullable|image|mimes:png,ico|max:1024', 
        ]);

        // 1. Guardar en la BD del Tenant
        // Excluimos ambas imágenes para no causar errores en la tabla local
        $empresa = Empresa::first();
        if ($empresa) {
            $empresa->update($request->except(['logo', 'favicon']));
            $mensaje = 'Empresa actualizada correctamente.';
        } else {
            $empresa = Empresa::create($request->except(['logo', 'favicon']));
            $mensaje = 'Empresa creada correctamente.';
        }

        // 2. Info de la BD Maestra
        $empresaMaster = \Illuminate\Support\Facades\DB::connection('mysql')
            ->table('dominios_empresas')
            ->where('dominio', $request->getHost())
            ->first();

        $datosMaster = ['nombre' => $request->nombre];
        $nombreLimpio = \Illuminate\Support\Str::slug($request->nombre);

        // 3. PROCESAR LOGO
        if ($request->hasFile('logo')) {
            $extLogo = $request->file('logo')->getClientOriginalExtension();
            $nombreLogo = 'logo_'.$empresaMaster->id.'_'.$nombreLimpio.'.'.$extLogo;
            $datosMaster['logo'] = $request->file('logo')->storeAs('logos', $nombreLogo, 'public');
        }

        // 4. PROCESAR FAVICON 
        if ($request->hasFile('favicon')) {
            $file = $request->file('favicon');
            
            // Forzamos a que siempre se guarde como PNG para conservar las transparencias
            $nombreFavicon = 'favicon_'.$empresaMaster->id.'_'.$nombreLimpio.'.png';
            $rutaFavicon = "logos/$nombreFavicon";

            try {
                $manager = new ImageManager(new Driver);
                
                // cover(128, 128) ajusta y recorta del centro para hacer un cuadrado perfecto 1:1
                $encodedFavicon = $manager->read($file)->cover(128, 128)->toPng();
                
                Storage::disk('public')->put($rutaFavicon, (string) $encodedFavicon);
                
                $datosMaster['favicon'] = $rutaFavicon;
            } catch (\Exception $e) {
                Log::error('Error al redimensionar favicon: ' . $e->getMessage());
                
                // Fallback: Si Intervention falla por algo, guardamos el original
                $datosMaster['favicon'] = $file->storeAs('logos', $nombreFavicon, 'public');
            }
        }

        // 5. Actualizar Maestra
        \Illuminate\Support\Facades\DB::connection('mysql')
            ->table('dominios_empresas')
            ->where('id', $empresaMaster->id)
            ->update($datosMaster);

        return response()->json([
            'success' => true,
            'empresa' => $empresa,
            'message' => $mensaje,
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
