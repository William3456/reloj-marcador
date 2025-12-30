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
    /**
     * Método privado para centralizar la lógica de filtros.
     * Retorna la colección de empleados ya filtrada y ordenada.
     */
    private function getEmpleadosFiltrados(Request $request)
    {
        $user = Auth::user();

        // 1. Iniciar la consulta base con relaciones y scopes
        $query = Empleado::visiblePara($user)
            ->with(['sucursal', 'puesto', 'departamento', 'user.rol']);

        // 2. Aplicar Filtros Dinámicos
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
            // Casteamos a int por seguridad, como tenías en el PDF
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

        // 3. Ordenamiento
        $query->orderBy('nombres', 'asc')
              ->orderBy('apellidos', 'asc');

        // 4. Retornar la colección de datos
        return $query->get();
    }

    public function porSucursal(Request $request)
    {
        $user = Auth::user();

        // 1. Obtener listas para los Selects (Filtros de la Vista)
        $sucursales = Sucursal::visiblePara($user)->get();
        $puestos = Puesto::visiblePara($user)->get();
        $departamentos = Departamento::visiblePara($user)->get();

        // Lógica de roles para el select
        if ($user->id_rol != 1) {
            $roles = Rol::where('id', '>=', $user->id_rol)->get();
        } else {
            $roles = Rol::all();
        }

        // 2. Obtener empleados usando el método unificado
        $empleados = $this->getEmpleadosFiltrados($request);

        // 3. Retornar vista
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
        // 1. Obtener empleados usando el mismo método unificado
        $empleados = $this->getEmpleadosFiltrados($request);
        $empresa = Empresa::first();
        // 2. Generamos el PDF usando el layout maestro
        $pdf = Pdf::loadView('reportes.empleados.pdf', compact('empleados','empresa'));

        // 3. Configuración de papel
        $pdf->setPaper('letter', 'portrait');

        
        // 4. Abrir PDF
        return $pdf->stream('reporte_empleados.pdf');
    }
}