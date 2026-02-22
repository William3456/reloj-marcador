<?php

namespace App\Http\Controllers\MarcacionApp;

use App\Http\Controllers\Controller;
use App\Models\Marcacion\MarcacionEmpleado;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class HistorialController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $empleado = $user->empleado;
        $hoy = Carbon::now()->endOfDay();

        // 1. Definir rango de fechas solicitado (Input del usuario)
        $desde = $request->input('desde')
            ? Carbon::parse($request->input('desde'))->startOfDay()
            : Carbon::now()->startOfMonth()->startOfDay();

        $hastaSolicitado = $request->input('hasta')
            ? Carbon::parse($request->input('hasta'))->endOfDay()
            : Carbon::now()->endOfMonth()->endOfDay();

        // ---------------------------------------------------------
        // CORRECCIÓN 1: LÓGICA INTELIGENTE PARA EL LIMITE DE FECHA
        // ---------------------------------------------------------
        // Buscamos la fecha de la ULTIMA marcación real que tenga este empleado en BD
        $ultimaMarcacionRegistrada = MarcacionEmpleado::where('id_empleado', $empleado->id)
            ->max('created_at');

        $fechaTopeLogico = $hoy; // Por defecto, el tope es Hoy

        if ($ultimaMarcacionRegistrada) {
            $fechaUltima = Carbon::parse($ultimaMarcacionRegistrada)->endOfDay();
            // Si tienes pruebas en el futuro (ej: mañana), el tope se extiende hasta esa fecha
            if ($fechaUltima->isFuture()) {
                $fechaTopeLogico = $fechaUltima;
            }
        }

        // El $hasta final será lo que pidió el usuario, PERO recortado por el tope lógico
        // (Para que si filtras hasta el 31 de mes, no te salgan días vacíos futuros,
        // a menos que tengas marcaciones en ellos).
        $hasta = $hastaSolicitado->greaterThan($fechaTopeLogico) ? $fechaTopeLogico : $hastaSolicitado;

        // 2. Traer marcaciones
        $todasMarcaciones = MarcacionEmpleado::where('id_empleado', $empleado->id)
            ->whereBetween('created_at', [$desde, $hasta])
            ->with(['sucursal', 'entrada', 'permiso.tipoPermiso']) // Cargar relación de permiso y su tipo
            ->get();

        // 3. Generar historial
        $historial = collect();
        $idsUsados = []; // <--- NUEVO: Para no repetir la misma entrada en dos turnos

        if ($desde->lessThanOrEqualTo($hasta)) {
            $periodo = CarbonPeriod::create($desde, $hasta);
            
            
            foreach ($periodo as $dia) {
                $nombreDia = Str::lower($dia->locale('es')->isoFormat('dddd'));

                // A. Buscar horarios (RESPETANDO LA LÍNEA DE TIEMPO HISTÓRICA)
                $horariosDelDia = collect();

                foreach ($empleado->horarios as $horarioBase) {
                    
                    // 1. Buscamos la versión exacta de este horario que estaba vigente en ESTA fecha ($dia)
                    $historico = \App\Models\Horario\HorarioHistorico::where('id_horario', $horarioBase->id)
                        ->where('vigente_desde', '<=', $dia->copy()->endOfDay())
                        ->where(function ($query) use ($dia) {
                            $query->whereNull('vigente_hasta')
                                  ->orWhere('vigente_hasta', '>=', $dia->copy()->startOfDay());
                        })
                        ->orderBy('vigente_desde', 'desc')
                        ->first();

                    if ($historico) {
                        // 2. Revisamos si en ESA época, el horario incluía el día de la semana actual
                        $diasArray = is_array($historico->dias) ? $historico->dias : json_decode($historico->dias, true);

                        if ($diasArray && in_array($nombreDia, $diasArray)) {
                            
                            // 3. "Disfrazamos" el histórico como un horario normal para no romper tu vista Blade ni tu lógica de abajo
                            $horariosDelDia->push((object)[
                                'id' => $horarioBase->id, 
                                'hora_ini' => $historico->hora_entrada,
                                'hora_fin' => $historico->hora_salida,
                                'tolerancia' => $historico->tolerancia,
                                'dias' => $diasArray,
                                'permitido_marcacion' => $historico->tipo_horario
                            ]);
                        }
                    }
                }

                // Ordenamos cronológicamente los turnos del día
                $horariosDelDia = $horariosDelDia->sortBy('hora_ini')->values();

                $turnosData = [];
                $completados = 0;
                
                foreach ($horariosDelDia as $horario) {

                    $horaInicioTurno = Carbon::parse($dia->format('Y-m-d').' '.$horario->hora_ini);

                    // DEFINIR VENTANA DE TIEMPO ESTRICTA
                    // La marcación es válida si ocurre entre 2 horas antes y 4 horas después del inicio del turno.
                    // Esto evita que el Turno 1 (08:00) capture una marcación de las 16:00 (fuera de rango).
                    $ventanaInicio = $horaInicioTurno->copy()->subHours(2);
                    $ventanaFin = $horaInicioTurno->copy()->addHours(4);

                    // 1. Buscar Entrada
                    $marcacionEncontrada = $todasMarcaciones->filter(function ($m) use ($ventanaInicio, $ventanaFin, $idsUsados) {
                        return $m->tipo_marcacion == 1
                            && ! in_array($m->id, $idsUsados) // <--- VALIDACIÓN 1: Que no haya sido usada
                            && $m->created_at->between($ventanaInicio, $ventanaFin); // <--- VALIDACIÓN 2: Rango estricto
                    })->first();

                    if ($marcacionEncontrada) {
                        
                        $completados++;
                        $idsUsados[] = $marcacionEncontrada->id; // <--- MARCAR COMO USADA

                        // BUSCAR SALIDA (Tu lógica existente, mantenida intacta)
                        // ... (aquí va tu lógica de búsqueda de salida igual que antes)
                        $salida = $todasMarcaciones->first(function ($item) use ($marcacionEncontrada) {
                            return $item->tipo_marcacion == 2
                                && $item->id_marcacion_entrada == $marcacionEncontrada->id;
                        });

                        if (! $salida) {
                            $salida = $todasMarcaciones->first(function ($item) use ($marcacionEncontrada, $dia) {
                                return $item->tipo_marcacion == 2
                                    && $item->id_marcacion_entrada == null
                                    && $item->created_at->gt($marcacionEncontrada->created_at)
                                    && $item->created_at->lt($dia->copy()->endOfDay());
                            });
                        }
                        
                        //dd($marcacionEncontrada);
                        $turnosData[] = [
                            'estado' => 'completado',
                            'horario_info' => $horario,
                            'entrada' => $marcacionEncontrada,
                            'salida' => $salida,
                            'id_permiso_aplicado' => $marcacionEncontrada->id_permiso_aplicado 
                                 ?? ($salida->id_permiso_aplicado ?? null),
                            'permiso_info' => $marcacionEncontrada->permiso ?? ($salida->permiso ?? null),
                        ];
                    } else {
                        
                        if ($horaInicioTurno->isPast()) {
                            $turnosData[] = ['estado' => 'perdido', 'horario_info' => $horario];
                        } else {
                            $turnosData[] = ['estado' => 'pendiente', 'horario_info' => $horario];
                        }
                    }
                   
                }
                
                // Resto de tu lógica para agregar al historial...
                $tieneMarcacionesSueltas = $todasMarcaciones->filter(function ($m) use ($dia) {
                    return $m->created_at->isSameDay($dia);
                })->count() > 0;

                $esHoy = $dia->isSameDay(Carbon::now()->startOfDay());

                if ($horariosDelDia->count() > 0 || $esHoy || $tieneMarcacionesSueltas) {
                    $historial->push([
                        'fecha' => $dia,
                        'total_turnos' => $horariosDelDia->count(),
                        'completados' => $completados,
                        'detalles' => $turnosData,
                    ]);
                }
            }
        }

        $historial = $historial->sortByDesc('fecha');
        


        return view('app_marcacion.marcaciones.historial', [
            'historial' => $historial,
            'desde' => $desde,
            'hasta' => $hastaSolicitado,
        ]);
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
