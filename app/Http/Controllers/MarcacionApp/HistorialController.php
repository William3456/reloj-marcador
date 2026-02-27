<?php

namespace App\Http\Controllers\MarcacionApp;

use App\Http\Controllers\Controller;
use App\Models\Empleado\Empleado;
use App\Models\HorarioEmpleado\HorarioEmpleado;
use App\Models\Marcacion\MarcacionEmpleado;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class HistorialController extends Controller
{
    public function __construct()
    {
        // =========================================================================
        // 🧪 ZONA DE PRUEBAS - NIVEL DE CLASE
        // Descomenta una línea para que TODAS las funciones del controlador
        // crean que es esa hora.
        // =========================================================================

        // 🕒 ESCENARIO 1: Salida Correcta (5:15 PM hoy)
        // \Carbon\Carbon::setTestNow(now()->setTime(17, 15, 0));

        // 🕒 ESCENARIO 2: Salida OLVIDADA (8:30 PM hoy)
        // Carbon::setTestNow(now()->addDay(6)->setTime( 23, 59, 0));

        // 🕒 ESCENARIO 3: Salida Temprana (3:00 PM hoy)
        // \Carbon\Carbon::setTestNow(now()->setTime(15, 0, 0));

        // 🕒 ESCENARIO 4: Mañana a las 8:00 AM //martes = 10
        // Carbon::setTestNow(now()->addDay(3)->setTime( 13,0, 0));

    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $empleadoId = $user->empleado->id;
        $hoy = Carbon::now();

        // 1. Obtener Rango de Fechas
        [$desde, $hasta] = $this->determinarRangoFechas($request, $empleadoId, $hoy);

        // 2. Extraer Datos de la Base de Datos
        $empleado = $this->obtenerEmpleadoConPermisos($empleadoId, $desde, $hasta);
        $historialHorarios = $this->obtenerHistorialHorarios($empleadoId);
        $marcacionesReales = $this->obtenerMarcacionesReales($empleadoId, $desde, $hasta);

        // 3. Construir la línea de tiempo cruzando la información
        $historial = $this->construirHistorialEmpleado(
            $empleado,
            $historialHorarios,
            $marcacionesReales,
            $desde,
            $hasta,
            $hoy
        );

        return view('app_marcacion.marcaciones.historial', compact('historial', 'desde', 'hasta'));
    }

    // =========================================================================
    // MÉTODOS AUXILIARES
    // =========================================================================

    /**
     * Define las fechas de inicio y fin para el historial.
     */
    private function determinarRangoFechas(Request $request, $empleadoId, $hoy)
    {
        $desde = $request->input('desde')
            ? Carbon::parse($request->input('desde'))->startOfDay()
            : Carbon::now()->startOfMonth()->startOfDay();

        $hastaPorDefecto = $hoy->copy()->endOfDay();
        $ultimaMarcacion = MarcacionEmpleado::where('id_empleado', $empleadoId)->max('created_at');

        if ($ultimaMarcacion) {
            $fechaUltima = Carbon::parse($ultimaMarcacion)->endOfDay();
            if ($fechaUltima->greaterThan($hastaPorDefecto)) {
                $hastaPorDefecto = $fechaUltima;
            }
        }

        $hasta = $request->input('hasta')
            ? Carbon::parse($request->input('hasta'))->endOfDay()
            : $hastaPorDefecto;

        return [$desde, $hasta];
    }

    /**
     * Obtiene el modelo Empleado junto con sus permisos vigentes en el rango.
     */
    private function obtenerEmpleadoConPermisos($empleadoId, $desde, $hasta)
    {
        return Empleado::with(['sucursal', 'puesto', 'permisos' => function ($q) use ($desde, $hasta) {
            $q->where('estado', 1)
                ->where(function ($q2) use ($desde, $hasta) {
                    $q2->where('fecha_inicio', '<=', $hasta)
                        ->where('fecha_fin', '>=', $desde);
                })
                ->with('tipoPermiso');
        }])->find($empleadoId);
    }

    /**
     * Obtiene el registro de horarios asignados al empleado en el tiempo.
     */
    private function obtenerHistorialHorarios($empleadoId)
    {
        return HorarioEmpleado::with(['horario', 'historico'])
            ->where('id_empleado', $empleadoId)
            ->get();
    }

    /**
     * Obtiene y agrupa por fecha todas las marcaciones reales del empleado.
     */
    private function obtenerMarcacionesReales($empleadoId, $desde, $hasta)
    {
        return MarcacionEmpleado::where('id_empleado', $empleadoId)
            ->with(['sucursal', 'salida', 'permisos.tipoPermiso', 'salida.permisos.tipoPermiso', 'salida.sucursal'])
            ->where('tipo_marcacion', 1)
            ->whereBetween('created_at', [$desde, $hasta])
            ->get()
            ->groupBy(function ($m) {
                return $m->created_at->format('Y-m-d');
            });
    }

    /**
     * El "Motor": Cruza los horarios asignados con las marcaciones reales día a día.
     */
    private function construirHistorialEmpleado($empleado, $historialHorarios, $marcacionesReales, $desde, $hasta, $hoy)
    {
        $historial = collect();
        $periodo = CarbonPeriod::create($desde, $hasta);

        foreach ($periodo as $fechaObj) {
            $fechaStr = $fechaObj->format('Y-m-d');
            $diaSemana = Str::slug($fechaObj->locale('es')->isoFormat('dddd'));

            // NUEVO: Filtramos y Mapeamos con el Histórico
            $turnosEsperados = $historialHorarios->filter(function ($asig) use ($fechaStr, $diaSemana) {
                $inicioValido = empty($asig->fecha_inicio) || $asig->fecha_inicio <= $fechaStr;
                $finValido = empty($asig->fecha_fin) || $asig->fecha_fin >= $fechaStr;
                if (! $inicioValido || ! $finValido || ! $asig->horario) {
                    return false;
                }

                $diasTurno = ($asig->historico && ! empty($asig->historico->dias)) ? $asig->historico->dias : $asig->horario->dias;
                if (empty($diasTurno)) {
                    return false;
                }

                return in_array($diaSemana, array_map(fn ($d) => Str::slug($d), $diasTurno));

            })->map(function ($asig) {
                $newAsig = clone $asig;
                $newHorario = clone $asig->horario;

                if ($newAsig->historico) {
                    $newHorario->hora_ini = $newAsig->historico->hora_entrada;
                    $newHorario->hora_fin = $newAsig->historico->hora_salida;
                    $newHorario->tolerancia = $newAsig->historico->tolerancia;
                }

                $newAsig->horario = $newHorario;

                return $newAsig;
            })->sortBy(fn ($asig) => $asig->horario->hora_ini);

            $marcacionesDelDia = $marcacionesReales->get($fechaStr, collect());
            $marcacionesLibres = collect($marcacionesDelDia->all());
            $turnosProcesados = [];
            $turnosDelDia = collect();

            // RONDA 1: Match Exacto
            foreach ($turnosEsperados as $asig) {
                $marcacion = $marcacionesLibres->first(function ($m) use ($asig) {
                    if ($m->id_horario_historico_empleado && $asig->id_horario_historico) {
                        return $m->id_horario_historico_empleado == $asig->id_horario_historico;
                    }
                    if ($m->id_horario && $asig->id_horario) {
                        return $m->id_horario == $asig->id_horario;
                    }

                    return false;
                });

                if ($marcacion) {
                    $turnosProcesados[$asig->id] = $marcacion;
                    $marcacionesLibres = $marcacionesLibres->reject(fn ($m) => $m->id == $marcacion->id);
                } else {
                    $turnosProcesados[$asig->id] = null;
                }
            }

            // RONDA 2: Match por Proximidad
            foreach ($turnosEsperados as $asig) {
                if (is_null($turnosProcesados[$asig->id]) && $marcacionesLibres->isNotEmpty()) {
                    $horaInicioTurno = Carbon::parse($fechaStr.' '.$asig->horario->hora_ini);
                    $marcacionCercana = $marcacionesLibres->sortBy(function ($m) use ($horaInicioTurno) {
                        return abs($m->created_at->diffInMinutes($horaInicioTurno));
                    })->first();

                    if (abs($marcacionCercana->created_at->diffInMinutes($horaInicioTurno)) <= 300) {
                        $turnosProcesados[$asig->id] = $marcacionCercana;
                        $marcacionesLibres = $marcacionesLibres->reject(fn ($m) => $m->id == $marcacionCercana->id);
                    }
                }
            }

            $completados = 0;

            // Construir tarjetas
            foreach ($turnosEsperados as $asig) {
                $marcacion = $turnosProcesados[$asig->id];
                $estado = $this->determinarEstadoVisualHistorial($marcacion, $fechaObj, $hoy, $asig->horario, $empleado);

                if (str_contains($estado->texto, 'Completado') || $estado->texto === 'Permiso Aprobado') {
                    $completados++;
                }

                $turnosDelDia->push((object) ['horario' => $asig->horario, 'marcacion' => $marcacion, 'estado' => $estado]);
            }

            // Turnos extra
            foreach ($marcacionesLibres as $mExtra) {
                $estadoExtra = (object) [
                    'texto' => $mExtra->salida ? 'Turno Extra' : 'Extra En Curso',
                    'clase' => 'bg-purple-100 text-purple-800 border-purple-200',
                    'borde' => 'bg-purple-500',
                ];
                $turnosDelDia->push((object) ['horario' => null, 'marcacion' => $mExtra, 'estado' => $estadoExtra]);
            }

            $esHoy = $fechaObj->isSameDay($hoy);

            // Agregar al historial si hay datos o si es el día actual
            if ($turnosDelDia->isNotEmpty() || $turnosEsperados->isNotEmpty() || $esHoy) {
                $turnosOrdenados = collect();
                if ($turnosDelDia->isNotEmpty()) {
                    $turnosOrdenados = $turnosDelDia->sortBy(function ($t) {
                        return $t->horario ? $t->horario->hora_ini : '24:00';
                    })->values();
                }

                $historial->push([
                    'fecha_obj' => $fechaObj->copy(),
                    'total_turnos' => $turnosEsperados->count(),
                    'completados' => $completados,
                    'turnos' => $turnosOrdenados,
                ]);
            }
        }

        // Ordenar cronológicamente invertido (más reciente arriba)
        return $historial->sortByDesc(fn ($dia) => $dia['fecha_obj']->format('Y-m-d'))->values();
    }

    /**
     * Determina las clases CSS y el texto según la situación del turno.
     */
    private function determinarEstadoVisualHistorial($marcacion, $fechaObj, $hoy, $horario, $emp)
    {
        $estado = (object) ['texto' => '', 'clase' => '', 'borde' => ''];

        $permisosDelDia = collect();
        if ($emp && $emp->relationLoaded('permisos')) {
            $permisosDelDia = $emp->permisos->filter(function ($p) use ($fechaObj) {
                return $fechaObj->between(\Carbon\Carbon::parse($p->fecha_inicio), \Carbon\Carbon::parse($p->fecha_fin));
            });
        }

        $permisosExoneracion = $permisosDelDia->whereIn('id_tipo_permiso', [5, 6]);
        $tieneExoneracion = $permisosExoneracion->isNotEmpty();

        if ($marcacion) {
            if ($marcacion->salida) {
                $salidaReal = $marcacion->salida->created_at;
                $esDiaDiferente = $marcacion->created_at->format('Y-m-d') !== $salidaReal->format('Y-m-d');
                $esOlvidoSalida = $marcacion->salida->es_olvido || $esDiaDiferente;

                if ($horario && ! $esOlvidoSalida) {
                    $finTurno = \Carbon\Carbon::parse($fechaObj->format('Y-m-d').' '.$horario->hora_fin);
                    if ($horario->hora_fin < $horario->hora_ini) {
                        $finTurno->addDay();
                    }
                    if ($salidaReal->gt($finTurno) && $salidaReal->diffInMinutes($finTurno) > 60) {
                        $esOlvidoSalida = true;
                    }
                }

                $tieneObservacion = $marcacion->fuera_horario || $esOlvidoSalida;
                $tienePermisosMarcacion = $marcacion->permisos->isNotEmpty() || $marcacion->salida->permisos->isNotEmpty();

                if ($tieneObservacion) {
                    $estado->texto = 'Completado c/ Obs.';
                    $estado->clase = 'bg-orange-100 text-orange-800 border-orange-200';
                    $estado->borde = 'bg-orange-400';
                } elseif ($tienePermisosMarcacion || $tieneExoneracion) {
                    $estado->texto = 'Completado c/ Permiso';
                    $estado->clase = 'bg-blue-100 text-blue-800 font-bold border-blue-200';
                    $estado->borde = 'bg-blue-500';
                } else {
                    $estado->texto = 'Jornada Completada';
                    $estado->clase = 'bg-green-100 text-green-800 border-green-200';
                    $estado->borde = 'bg-green-500';
                }
            } else {
                if (! $marcacion->created_at->isToday()) {
                    $estado->texto = 'Sin Salida';
                    $estado->clase = 'bg-red-100 text-red-800 border-red-200';
                    $estado->borde = 'bg-red-500';
                } else {
                    $estado->texto = 'En Turno';
                    $estado->clase = 'bg-yellow-100 text-yellow-800 animate-pulse border-yellow-200';
                    $estado->borde = 'bg-yellow-400';
                }
            }
        } else {
            if ($tieneExoneracion) {
                $nombresPermisos = $permisosExoneracion->map(fn ($p) => $p->tipoPermiso->nombre)->implode(' / ');
                $estado->texto = $nombresPermisos ?: 'Permiso Aprobado';
                $estado->clase = 'bg-blue-100 text-blue-800 font-bold shadow-sm border-blue-200';
                $estado->borde = 'bg-blue-500';
            } else {
                $horaInicioTurno = Carbon::parse($fechaObj->format('Y-m-d').' '.($horario ? $horario->hora_ini : '00:00'));
                if ($fechaObj->isFuture() || ($fechaObj->isToday() && $hoy->lt($horaInicioTurno))) {
                    $estado->texto = 'Próximo';
                    $estado->clase = 'bg-gray-100 text-gray-600 border-gray-200';
                    $estado->borde = 'bg-gray-300';
                } else {
                    $estado->texto = 'Sin Asistencia';
                    $estado->clase = 'bg-red-100 text-red-800 font-bold shadow-sm border-red-200';
                    $estado->borde = 'bg-red-600';
                }
            }
        }

        return $estado;
    }
}
