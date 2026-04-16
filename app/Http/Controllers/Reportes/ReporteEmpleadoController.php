<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use App\Models\Departamento\Departamento;
use App\Models\Empleado\Empleado;
use App\Models\Empresa\Empresa;
use App\Models\Puesto\Puesto;
use App\Models\Rol\Rol;
use App\Models\Sucursales\Sucursal;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReporteEmpleadoController extends Controller
{
    private function getEmpleadosFiltrados(Request $request)
    {
        $user = Auth::user();

        // 🌟 AGREGADO: Cargamos la relación 'trabajo_remoto' filtrada por 'es_actual'
        $query = Empleado::visiblePara($user)
            ->with(['sucursal', 'puesto', 'departamento', 'user.rol', 
                'horarios' => function ($q) {
                    $q->wherePivot('es_actual', 1);
                },
                'trabajo_remoto' => function ($q) {
                    $q->where('es_actual', 1);
                }
            ]);

        if ($request->filled('sucursal')) {
            $query->where('id_sucursal', $request->sucursal);
        }

        if ($request->filled('puesto')) {
            $query->where('id_puesto', $request->puesto);
        }

        if ($request->filled('departamento')) {
            $query->where('id_depto', $request->departamento);
        }

        if ($request->filled('estado')) {
            $query->where('estado', (int)$request->estado);
        }

        if ($request->filled('login')) {
            $query->where('login', $request->login);
        }

        if ($request->filled('rol')) {
            $query->where('login', 1)
                ->whereHas('user', function ($q) use ($request) {
                    $q->where('id_rol', $request->rol);
                });
        }

        $query->orderBy('nombres', 'asc')
              ->orderBy('apellidos', 'asc');

        return $query->get();
    }

    public function porSucursal(Request $request)
    {
        $user = Auth::user();

        $sucursales = Sucursal::visiblePara($user)->get();
        $puestos = Puesto::visiblePara($user)->get();
        $departamentos = Departamento::visiblePara($user)->get();

        if ($user->id_rol != 1) {
            $roles = Rol::where('id', '>=', $user->id_rol)->get();
        } else {
            $roles = Rol::all();
        }

        $empleados = $this->getEmpleadosFiltrados($request);

        return view('reportes.empleados.empleados_rep', compact(
            'sucursales',
            'roles',
            'puestos',
            'departamentos',
            'empleados'
        ));
    }

    public function generarPdf(Request $request)
    {
        $empleados = $this->getEmpleadosFiltrados($request);
        $empresa = Empresa::first();

        // Recopilar nombres legibles de los filtros para el encabezado del PDF
        $filtros = [
            'sucursal' => $request->filled('sucursal') ? Sucursal::find($request->sucursal)->nombre : 'Todas las Sucursales',
            'estado' => $request->filled('estado') ? ($request->estado == '1' ? 'Activos' : 'Inactivos') : 'Todos',
            'login' => $request->filled('login') ? ($request->login == '1' ? 'Con Acceso' : 'Sin Acceso') : 'Todos',
            'puesto' => $request->filled('puesto') ? Puesto::find($request->puesto)->desc_puesto : 'Todos',
            'departamento' => $request->filled('departamento') ? Departamento::find($request->departamento)->nombre_depto : 'Todos',
        ];

        $pdf = Pdf::loadView('reportes.empleados.pdf', compact('empleados', 'empresa', 'filtros'));
        
        $pdf->setPaper('letter', 'landscape');

        return $pdf->stream('reporte_empleados.pdf');
    }
}