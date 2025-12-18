<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Departamento\Departamento;
use App\Models\Puesto\Puesto;
use Illuminate\Http\Request;

class DeptosPuestosController extends Controller
{
    public function puestosAndDeptos($sucursalId)
    {
        return response()->json([
            'departamentos' => Departamento::where('sucursal_id', $sucursalId)
                ->select('id', 'nombre_depto')
                ->orderBy('nombre_depto')
                ->get(),

            'puestos' => Puesto::where('sucursal_id', $sucursalId)
                ->select('id', 'desc_puesto')
                ->orderBy('desc_puesto')
                ->get(),
        ]);
    }
}
