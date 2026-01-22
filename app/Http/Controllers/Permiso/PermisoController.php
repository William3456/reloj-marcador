<?php

namespace App\Http\Controllers\Permiso;

use App\Http\Controllers\Controller;
use App\Models\Empleado\Empleado;
use App\Models\Permiso\Permiso;
use App\Models\Permiso\TipoPermiso;
use App\Models\Sucursales\Sucursal;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class PermisoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $sucursales = Sucursal::visiblePara(Auth::user())->whereHas('empleados.permisos')
            ->with([
                'empleados' => function ($q) {
                    $q->whereHas('permisos')
                        ->with([
                            'permisos' => function ($p) {
                                $p->orderBy('estado', 'desc'); // 1 primero, luego 0
                            },
                            'permisos.tipo',
                        ]);
                },
            ])
            ->orderBy('nombre')
            ->get();

        return view('permisos.index', compact('sucursales'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $empleados = Empleado::where('estado', 1)->get();
        $tiposPermiso = TipoPermiso::where('estado', 1)->get();
        $sucursales = Sucursal::visiblePara(Auth::user())->where('estado', 1)->get();

        return view('permisos.create', compact('empleados', 'tiposPermiso', 'sucursales'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $this->buildPermisoData($request);

        $existeActivo = Permiso::where('id_empleado', $data['id_empleado'])
            ->where('estado', 1)
            ->exists();
        if ($existeActivo) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['id_empleado' => 'El empleado ya tiene un permiso activo. Favor inactivar el permiso antes de asignar uno nuevo.']);
        } else {
            Permiso::create($data);

            return redirect()
                ->route('permisos.index')
                ->with('success', 'Permiso asignado correctamente.');
        }

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
        $permiso = Permiso::visiblePara(Auth::user())->with('empleado.sucursal')->findOrFail($id);

        $tiposPermiso = TipoPermiso::where('estado', 1)->get();
        $sucursales = Sucursal::visiblePara(Auth::user())->where('estado', 1)->get();

        return view('permisos.edit', compact('permiso', 'tiposPermiso', 'sucursales'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $permiso = Permiso::findOrFail($id);

        $data = $this->buildPermisoData($request);

        $existeActivo = Permiso::where('id_empleado', $data['id_empleado'])
            ->where('estado', 1)
            ->where('id', '!=', $permiso->id)
            ->exists();

        if ($existeActivo) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['id_empleado' => 'El empleado ya tiene un permiso activo.']);
        } else {
            $permiso->update($data);

            return redirect()
                ->route('permisos.index')
                ->with('success', 'Permiso actualizado correctamente.');
        }

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $permiso = Permiso::findOrFail($id);
        $permiso->delete();

        return redirect()
            ->route('permisos.index')
            ->with('success', 'Permiso eliminado correctamente.');
    }

    private function buildPermisoData(Request $request): array
    {
        $request->validate([
            'id_empleado' => 'required|exists:empleados,id',
            'id_tipo_permiso' => 'required|exists:tipos_permiso,id',
            'motivo' => 'nullable|string|max:255',
            'estado' => 'required|in:0,1',
        ]);

        $tipo = TipoPermiso::findOrFail($request->id_tipo_permiso);
        $esEditar = request()->routeIs('permisos.update');

        $esFueraRango = $tipo->requiere_distancia;
        $ubicacionLibre = $esFueraRango && $request->filled('ubicacion_libre') && $request->ubicacion_libre == 1;

        if ($tipo->requiere_distancia && ! $ubicacionLibre) {
            $request->validate([
                'cantidad_mts' => 'required|integer|min:1',
            ]);
        }

        if ($tipo->requiere_dias) {
            $request->validate([
                'dias_activa' => 'required|integer|min:1',
            ]);
        }

        if ($tipo->requiere_fechas) {

            $rules = [
                'fecha_inicio' => ['required', 'date'],
                'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            ];

            // SOLO en create se valida contra hoy
            if (! $esEditar) {
                $rules['fecha_inicio'][] = 'after_or_equal:today';
            }

            $request->validate($rules);
        }

        if (
            in_array($tipo->codigo, ['LLEGADA_TARDE', 'SALIDA_TEMPRANA'])
            && ! ($esFueraRango && $ubicacionLibre)
        ) {
            $request->validate([
                'valor' => 'required|integer|min:1',
            ]);
        }

        if ($tipo->requiere_dias && $tipo->requiere_fechas) {
            abort(422, 'Configuración inválida del tipo de permiso.');
        }

        // Datos base
        $data = [
            'id_empleado' => $request->id_empleado,
            'id_tipo_permiso' => $tipo->id,
            'motivo' => $request->motivo,
            'estado' => $request->estado,
        ];

        // Distancia
        $data['cantidad_mts'] = ($tipo->requiere_distancia && ! $ubicacionLibre)
            ? $request->cantidad_mts
            : null;

        $data['valor'] = in_array(
            $tipo->codigo,
            ['LLEGADA_TARDE', 'SALIDA_TEMPRANA']
        ) ? $request->valor : null;

        // Fechas
        if ($tipo->requiere_fechas) {
            $data['fecha_inicio'] = $request->fecha_inicio;
            $data['fecha_fin'] = $request->fecha_fin;
            $data['dias_activa'] = null;
        }

        // Días
        if ($tipo->requiere_dias) {
            $data['dias_activa'] = $request->dias_activa;
            $data['fecha_inicio'] = Carbon::today();
            $data['fecha_fin'] = null;
        }

        return $data;
    }
}
