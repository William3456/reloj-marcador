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
    public function index(Request $request)
    {
        $user = Auth::user();
        $empleado = $user->empleado;
        $hoy = Carbon::now()->endOfDay();

        // 1. Definir rango de fechas solicitado
        $desde = $request->input('desde')
            ? Carbon::parse($request->input('desde'))->startOfDay()
            : Carbon::now()->startOfMonth()->startOfDay();

        $hastaSolicitado = $request->input('hasta')
            ? Carbon::parse($request->input('hasta'))->endOfDay()
            : Carbon::now()->endOfMonth()->endOfDay();

        // Buscamos la fecha de la ULTIMA marcación
        $ultimaMarcacionRegistrada = MarcacionEmpleado::where('id_empleado', $empleado->id)
            ->max('created_at');

        $fechaTopeLogico = $hoy;

        if ($ultimaMarcacionRegistrada) {
            $fechaUltima = Carbon::parse($ultimaMarcacionRegistrada)->endOfDay();
            if ($fechaUltima->isFuture()) {
                $fechaTopeLogico = $fechaUltima;
            }
        }

        $hasta = $hastaSolicitado->greaterThan($fechaTopeLogico) ? $fechaTopeLogico : $hastaSolicitado;

        // =========================================================
        // CORRECCIÓN 1: Eager Loading de la relación en PLURAL
        // =========================================================
        $todasMarcaciones = MarcacionEmpleado::where('id_empleado', $empleado->id)
            ->whereBetween('created_at', [$desde, $hasta])
            ->with(['sucursal', 'entrada', 'permisos.tipoPermiso']) // <-- CORREGIDO AQUÍ
            ->get();

        $historial = collect();
        $idsUsados = []; 

        if ($desde->lessThanOrEqualTo($hasta)) {
            $periodo = CarbonPeriod::create($desde, $hasta);
            
            foreach ($periodo as $dia) {
                $nombreDia = Str::lower($dia->locale('es')->isoFormat('dddd'));

                $horariosDelDia = collect();

                foreach ($empleado->horarios as $horarioBase) {
                    
                    $historico = \App\Models\Horario\HorarioHistorico::where('id_horario', $horarioBase->id)
                        ->where('vigente_desde', '<=', $dia->copy()->endOfDay())
                        ->where(function ($query) use ($dia) {
                            $query->whereNull('vigente_hasta')
                                  ->orWhere('vigente_hasta', '>=', $dia->copy()->startOfDay());
                        })
                        ->orderBy('vigente_desde', 'desc')
                        ->first();

                    if ($historico) {
                        $diasArray = is_array($historico->dias) ? $historico->dias : json_decode($historico->dias, true);

                        if ($diasArray && in_array($nombreDia, $diasArray)) {
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

                $horariosDelDia = $horariosDelDia->sortBy('hora_ini')->values();
                $turnosData = [];
                $completados = 0;
                
                foreach ($horariosDelDia as $horario) {

                    $horaInicioTurno = Carbon::parse($dia->format('Y-m-d').' '.$horario->hora_ini);
                    $ventanaInicio = $horaInicioTurno->copy()->subHours(2);
                    $ventanaFin = $horaInicioTurno->copy()->addHours(4);

                    $marcacionEncontrada = $todasMarcaciones->filter(function ($m) use ($ventanaInicio, $ventanaFin, $idsUsados) {
                        return $m->tipo_marcacion == 1
                            && ! in_array($m->id, $idsUsados)
                            && $m->created_at->between($ventanaInicio, $ventanaFin);
                    })->first();

                    if ($marcacionEncontrada) {
                        
                        $completados++;
                        $idsUsados[] = $marcacionEncontrada->id;

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
                        
                        // =========================================================
                        // CORRECCIÓN 2: Enviar colecciones de permisos a la vista
                        // =========================================================
                        $turnosData[] = [
                            'estado' => 'completado',
                            'horario_info' => $horario,
                            'entrada' => $marcacionEncontrada,
                            'salida' => $salida,
                            'permisos_entrada' => $marcacionEncontrada->permisos ?? collect(), // <-- NUEVO
                            'permisos_salida' => $salida ? $salida->permisos : collect(),       // <-- NUEVO
                        ];
                    } else {
                        if ($horaInicioTurno->isPast()) {
                            $turnosData[] = ['estado' => 'perdido', 'horario_info' => $horario];
                        } else {
                            $turnosData[] = ['estado' => 'pendiente', 'horario_info' => $horario];
                        }
                    }
                }
                
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
}