<?php

namespace App\Http\Controllers\MarcacionApp;

use App\Http\Controllers\Controller;
use App\Models\Marcacion\MarcacionEmpleado;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class HistorialController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $desde = $request->input('desde')
            ? Carbon::parse($request->input('desde'))->startOfDay()
            : Carbon::now()->startOfMonth()->startOfDay();

        $hasta = $request->input('hasta')
            ? Carbon::parse($request->input('hasta'))->endOfDay()
            : Carbon::now()->endOfMonth()->endOfDay();

        $marcaciones = MarcacionEmpleado::where('id_empleado', $user->empleado->id)
            ->where(function ($q) use ($desde, $hasta) {

                // Entradas dentro del rango
                $q->where(function ($q2) use ($desde, $hasta) {
                    $q2->where('tipo_marcacion', 1)
                        ->whereBetween('created_at', [$desde, $hasta]);
                })

                // Salidas cuya ENTRADA esté dentro del rango
                    ->orWhere(function ($q2) use ($desde, $hasta) {
                        $q2->where('tipo_marcacion', 2)
                            ->whereHas('entrada', function ($q3) use ($desde, $hasta) {
                                $q3->whereBetween('created_at', [$desde, $hasta]);
                            });
                    });
            })
            ->with(['sucursal', 'entrada'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(function ($item) {
                return $item->entrada
                    ? $item->entrada->created_at->format('Y-m-d')
                    : $item->created_at->format('Y-m-d');
            });

        return view(
            'app_marcacion.marcaciones.historial',
            compact('marcaciones', 'desde', 'hasta')
        );
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
        //
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
