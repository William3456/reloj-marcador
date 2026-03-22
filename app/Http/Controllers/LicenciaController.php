<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LicenciaController extends Controller
{
    public function index(Request $request)
    {
        
        $currentHost = $request->getHost();

        $empresas = DB::connection('mysql')
            ->table('dominios_empresas')
            ->orderBy('nombre')
            ->get();

        
        return view('empresas.licencias', compact('empresas', 'currentHost'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'tipo_licencia' => 'required|in:0,1',
            'fecha_exp_licencia' => 'required_if:tipo_licencia,0|date|nullable',
        ]);

        DB::connection('mysql')->table('dominios_empresas')->where('id', $id)->update([
            'tipo_licencia' => $request->tipo_licencia,
            'fecha_exp_licencia' => $request->tipo_licencia == 1 ? null : $request->fecha_exp_licencia,
        ]);

        return back()->with('success', 'Licencia actualizada correctamente.');
    }
}
