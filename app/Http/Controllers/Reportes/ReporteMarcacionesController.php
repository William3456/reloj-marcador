<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use App\Models\Empleado\Empleado;
use App\Models\Empresa\Empresa;
use App\Models\HorarioEmpleado\HorarioEmpleado;
use App\Models\Marcacion\MarcacionEmpleado;
use App\Models\Sucursales\Sucursal;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ReporteMarcacionesController extends Controller
{
    public function index(Request $request)
    {
        $sucursales = Sucursal::visiblePara(Auth::user())->where('estado', 1)->get();
        $empleadosList = Empleado::visiblePara(Auth::user())->where('estado', 1)
            ->whereHas('user', function ($q) {
                $q->whereNotIn('id_rol', [1, 2]);
            })->orderBy('nombres')->get();

        $marcaciones = collect();
        if ($request->has('desde')) {
            $marcaciones = $this->generarDataReporte($request);
        }

        return view('reportes.marcaciones.marcaciones_rep', compact('marcaciones', 'sucursales', 'empleadosList'));
    }

    public function generarPdf(Request $request)
    {
        $registros = $this->generarDataReporte($request);
        $empresa = Empresa::first();

        $nombresFiltros = [
            'presente' => 'Asistencia Perfecta (Puntuales)',
            'extra' => 'Turnos Extras',
            'tarde_total' => 'Todas las Llegadas Tarde',
            'tarde_sin_permiso' => 'Llegadas Tarde Injustificadas',
            'ausente' => 'Ausencias Injustificadas',
            'con_permiso' => 'Registros Justificados (Con Permiso)',
            'sin_cierre' => 'Olvidos de Salida / Sin Cierre',
        ];

        // 🌟 AGREGADO: Cargamos los horarios de la sucursal para mostrarlos en el PDF
        $sucursalObj = $request->filled('sucursal') ? Sucursal::with('horarios')->find($request->sucursal) : null;

        $filtros = [
            'desde' => $request->input('desde') ?? date('Y-m-01'),
            'hasta' => $request->input('hasta') ?? date('Y-m-d'),
            'sucursal_nombre' => $sucursalObj ? $sucursalObj->nombre : 'Consolidado General (Todas)',
            'sucursal_obj' => $sucursalObj,
            'incidencia' => $request->filled('incidencia') ? ($nombresFiltros[$request->incidencia] ?? 'Todos') : 'Todos los registros',
        ];

        $pdf = Pdf::loadView('reportes.marcaciones.pdf', compact('registros', 'empresa', 'filtros'));
        $pdf->setPaper('letter', 'landscape');

        return $pdf->stream('reporte_asistencia.pdf');
    }

    private function generarDataReporte(Request $request)
    {
        $hoy = Carbon::now();
        $desde = $request->input('desde') ? Carbon::parse($request->input('desde'))->startOfDay() : Carbon::now()->startOfMonth()->startOfDay();

        $hastaPorDefecto = $hoy->copy()->endOfDay();
        $ultimaMarcacion = MarcacionEmpleado::max('created_at');
        if ($ultimaMarcacion && Carbon::parse($ultimaMarcacion)->endOfDay()->greaterThan($hastaPorDefecto)) {
            $hastaPorDefecto = Carbon::parse($ultimaMarcacion)->endOfDay();
        }
        $hasta = $request->input('hasta') ? Carbon::parse($request->input('hasta'))->endOfDay() : $hastaPorDefecto;

        $user = Auth::user();
        $queryEmpleados = Empleado::with(['sucursal', 'puesto', 'trabajo_remoto' => function ($q) {
            $q->where('es_actual', 1);
        }, 'permisos' => function ($q) use ($desde, $hasta) {
            $q->where('estado', 1)
                ->where(function ($q2) use ($desde, $hasta) {
                    $q2->where('fecha_inicio', '<=', $hasta)->where('fecha_fin', '>=', $desde);
                })->with('tipoPermiso');
        }])->where('estado', 1)->whereHas('user', function ($q) {
            $q->whereNotIn('id_rol', [1, 2]);
        })->when($user->id_rol == 2, function ($q) use ($user) {
            return $q->where('id_sucursal', $user->empleado->id_sucursal);
        });

        if ($request->filled('sucursal')) {
            $queryEmpleados->where('id_sucursal', $request->sucursal);
        }
        if ($request->filled('empleado')) {
            $queryEmpleados->where('id', $request->empleado);
        }

        $empleados = $queryEmpleados->orderBy('apellidos')->get();
        $empleadosIds = $empleados->pluck('id');

        $historialHorariosTodos = HorarioEmpleado::with(['horario', 'historico'])->whereIn('id_empleado', $empleadosIds)->get()->groupBy('id_empleado');

        $marcacionesReales = MarcacionEmpleado::whereIn('id_empleado', $empleadosIds)
            ->with(['sucursal', 'salida', 'permisos.tipoPermiso', 'salida.permisos.tipoPermiso'])
            ->where('tipo_marcacion', 1)
            ->whereBetween('created_at', [$desde, $hasta])
            ->get()
            ->groupBy('id_empleado');

        $reporte = collect();
        $periodo = CarbonPeriod::create($desde, $hasta);

        foreach ($empleados as $emp) {
            $horariosDelEmpleado = $historialHorariosTodos->get($emp->id, collect());
            $marcacionesDelEmpleado = $marcacionesReales->get($emp->id, collect())->groupBy(fn ($m) => $m->created_at->format('Y-m-d'));

            foreach ($periodo as $fechaObj) {
                $fechaStr = $fechaObj->format('Y-m-d');
                $diaSemana = Str::slug($fechaObj->locale('es')->isoFormat('dddd'));

                $marcacionesDelDia = $marcacionesDelEmpleado->get($fechaStr, collect());

                $esDiaRemoto = false;

                // 1. Prioridad: ¿Alguna marcación de este día fue remota?
                foreach ($marcacionesDelDia as $mDia) {
                    if ($mDia->es_remoto) {
                        $esDiaRemoto = true;
                    }
                    if ($mDia->salida && $mDia->salida->es_remoto) {
                        $esDiaRemoto = true;
                    }
                }

                // 2. Si no marcó remoto, verificamos si le correspondía por configuración
                if (! $esDiaRemoto && $emp->trabajo_remoto) {
                    $config = $emp->trabajo_remoto;
                    $inicio = \Carbon\Carbon::parse($config->fecha_inicio)->startOfDay();
                    $fin = $config->fecha_fin ? \Carbon\Carbon::parse($config->fecha_fin)->startOfDay() : null;
                    $fechaFila = $fechaObj->copy()->startOfDay();

                    // Comparamos si la fecha actual del ciclo entra en la vigencia
                    if ($fechaFila->greaterThanOrEqualTo($inicio) && ($fin === null || $fechaFila->lessThanOrEqualTo($fin))) {
                        $diasConfig = is_array($config->dias) ? $config->dias : json_decode($config->dias, true);
                        if (is_array($diasConfig)) {
                            $diasConfig = array_map('mb_strtolower', $diasConfig);
                            if (in_array(mb_strtolower($fechaObj->locale('es')->isoFormat('dddd')), $diasConfig)) {
                                $esDiaRemoto = true;
                            }
                        }
                    }
                }

                $turnosEsperados = $horariosDelEmpleado->filter(function ($asig) use ($fechaStr, $diaSemana) {
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

                $marcacionesLibres = collect($marcacionesDelDia->all());
                $turnosProcesados = [];

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

                foreach ($turnosEsperados as $asig) {
                    if (is_null($turnosProcesados[$asig->id]) && $marcacionesLibres->isNotEmpty()) {
                        $horaInicioTurno = Carbon::parse($fechaStr.' '.$asig->horario->hora_ini);
                        $marcacionCercana = $marcacionesLibres->sortBy(fn ($m) => abs($m->created_at->diffInMinutes($horaInicioTurno)))->first();
                        if (abs($marcacionCercana->created_at->diffInMinutes($horaInicioTurno)) <= 300) {
                            $turnosProcesados[$asig->id] = $marcacionCercana;
                            $marcacionesLibres = $marcacionesLibres->reject(fn ($m) => $m->id == $marcacionCercana->id);
                        }
                    }
                }

                foreach ($turnosEsperados as $asig) {
                    $marcacion = $turnosProcesados[$asig->id];
                    $datosEstado = $this->determinarEstadoReporte($marcacion, $fechaObj, $hoy, $asig->horario, $emp);

                    if ($datosEstado['key'] == 'programado') {
                        continue;
                    }

                    $reporte->push([
                        'fecha' => $fechaObj->copy(),
                        'empleado' => $emp,
                        'sucursal' => $emp->sucursal,
                        'horario_programado' => Carbon::parse($asig->horario->hora_ini)->format('H:i').' - '.Carbon::parse($asig->horario->hora_fin)->format('H:i'),
                        'tolerancia' => $datosEstado['tolerancia_calculada'], // <-- AHORA USAMOS LA CALCULADA
                        'entrada_real' => $marcacion ? $marcacion->created_at : null,
                        'salida_real' => ($marcacion && $marcacion->salida) ? $marcacion->salida->created_at : null,
                        'es_dia_remoto' => $esDiaRemoto,
                        'es_remoto_entrada' => $marcacion ? (bool)$marcacion->es_remoto : false,
                        'es_remoto_salida' => ($marcacion && $marcacion->salida) ? (bool)$marcacion->salida->es_remoto : false,
                        'estado_key' => $datosEstado['key'],
                        'minutos_tarde' => $datosEstado['minutos_tarde'],
                        'permiso_info' => $datosEstado['permiso_info'],
                        'es_olvido_salida' => $datosEstado['es_olvido_salida'],
                    ]);
                }

                foreach ($marcacionesLibres as $mExtra) {
                    $datosEstado = $this->determinarEstadoReporte($mExtra, $fechaObj, $hoy, null, $emp);
                    $estadoKeyExtra = 'extra';

                    if (! $mExtra->salida && ! $mExtra->created_at->isToday()) {
                        $estadoKeyExtra = 'sin_cierre';
                    }

                    $reporte->push([
                        'fecha' => $fechaObj->copy(),
                        'empleado' => $emp,
                        'sucursal' => $emp->sucursal,
                        'horario_programado' => 'Turno Extra',
                        'tolerancia' => 0,
                        'entrada_real' => $mExtra->created_at,
                        'salida_real' => $mExtra->salida ? $mExtra->salida->created_at : null,
                        'es_dia_remoto' => $esDiaRemoto,
                        'es_remoto_entrada' => (bool)$mExtra->es_remoto,
                        'es_remoto_salida' => $mExtra->salida ? (bool)$mExtra->salida->es_remoto : false,
                        'estado_key' => $estadoKeyExtra,
                        'minutos_tarde' => 0,
                        'permiso_info' => $datosEstado['permiso_info'],
                        'es_olvido_salida' => false,
                    ]);
                }
            }
        }

        if ($request->filled('incidencia')) {
            $filtro = $request->incidencia;

            $reporte = $reporte->filter(function ($row) use ($filtro) {
                switch ($filtro) {
                    case 'presente': return $row['estado_key'] === 'presente';
                    case 'tarde_total': return in_array($row['estado_key'], ['tarde', 'tarde_con_permiso']);
                    case 'tarde_sin_permiso': return $row['estado_key'] === 'tarde';
                    case 'ausente': return $row['estado_key'] === 'ausente';
                    case 'con_permiso': return ! empty($row['permiso_info']);
                    case 'sin_cierre': return $row['estado_key'] === 'sin_cierre' || $row['es_olvido_salida'] === true;
                    case 'extra': return $row['estado_key'] === 'extra';
                    default: return true;
                }
            });
        }

        return $reporte->sortBy([
            fn ($a, $b) => strcmp($a['empleado']->apellidos, $b['empleado']->apellidos),
            fn ($a, $b) => $a['fecha']->timestamp <=> $b['fecha']->timestamp,
        ])->values();
    }

    private function determinarEstadoReporte($marcacion, $fechaObj, $hoy, $horario, $emp)
    {
        $key = 'ausente';
        $minutosTarde = 0;
        $permisoInfo = null;
        $esOlvidoSalida = false;
        $toleranciaFinal = 0;

        // 1. Obtener Tolerancia Base (Incluso si no vino a trabajar, debemos saber cuál era su tolerancia)
        if ($horario) {
            $toleranciaFinal = $horario->tolerancia;
            if ($emp->sucursal && $emp->sucursal->horarios && $emp->sucursal->horarios->isNotEmpty()) {
                $horaTeorica = Carbon::parse($fechaObj->format('Y-m-d').' '.$horario->hora_ini);
                $horarioSucursal = $emp->sucursal->horarios->sortBy(function ($hs) use ($horaTeorica) {
                    $horaInicioSucursal = Carbon::parse($horaTeorica->format('Y-m-d').' '.$hs->hora_ini);

                    return abs($horaTeorica->diffInMinutes($horaInicioSucursal));
                })->first();

                if ($horarioSucursal) {
                    $toleranciaFinal = $horarioSucursal->tolerancia;
                }
            }
        }

        // 2. Permisos del Día
        $permisosDelDia = $emp && $emp->relationLoaded('permisos') ? $emp->permisos->filter(fn ($p) => $fechaObj->between(Carbon::parse($p->fecha_inicio), Carbon::parse($p->fecha_fin))) : collect();
        $permisosExoneracion = $permisosDelDia->whereIn('id_tipo_permiso', [5, 6]);
        $tieneExoneracion = $permisosExoneracion->isNotEmpty();

        if ($tieneExoneracion) {
            $p = $permisosExoneracion->first();
            $permisoInfo = ['tipo' => $p->tipoPermiso->nombre, 'motivo' => $p->motivo, 'desde' => $p->fecha_inicio, 'hasta' => $p->fecha_fin];
        }

        // 3. Evaluaciones si hay marcación
        if ($marcacion) {

            if ($marcacion->permisos->isNotEmpty()) {
                $p = $marcacion->permisos->first();
                $permisoInfo = ['tipo' => $p->tipoPermiso->nombre, 'motivo' => $p->motivo, 'desde' => $p->fecha_inicio ?? $fechaObj->format('Y-m-d'), 'hasta' => $p->fecha_fin ?? $fechaObj->format('Y-m-d')];

                // Si la marcación tiene un permiso de Llegada Tarde, sumar los minutos a la tolerancia que mostraremos
                if ($p->tipoPermiso && $p->tipoPermiso->codigo === 'LLEGADA_TARDE') {
                    $toleranciaFinal += (int) $p->valor;
                }
            } elseif ($marcacion->salida && $marcacion->salida->permisos->isNotEmpty()) {
                $p = $marcacion->salida->permisos->first();
                $permisoInfo = ['tipo' => $p->tipoPermiso->nombre, 'motivo' => $p->motivo, 'desde' => $p->fecha_inicio ?? $fechaObj->format('Y-m-d'), 'hasta' => $p->fecha_fin ?? $fechaObj->format('Y-m-d')];
            }

            if ($marcacion->salida) {
                $salidaReal = $marcacion->salida->created_at;
                $esDiaDiferente = $marcacion->created_at->format('Y-m-d') !== $salidaReal->format('Y-m-d');
                $esOlvidoSalida = $marcacion->salida->es_olvido || $esDiaDiferente;

                if ($horario && ! $esOlvidoSalida) {
                    $finTurno = Carbon::parse($fechaObj->format('Y-m-d').' '.$horario->hora_fin);
                    if ($horario->hora_fin < $horario->hora_ini) {
                        $finTurno->addDay();
                    }
                    if ($salidaReal->gt($finTurno) && $salidaReal->diffInMinutes($finTurno) > 60) {
                        $esOlvidoSalida = true;
                    }
                }
            } else {
                if (! $marcacion->created_at->isToday()) {
                    $esOlvidoSalida = true;
                }
            }

            // Calculo Dinámico de la impuntualidad
            $esTarde = false;
            if ($horario) {
                $horaTeorica = Carbon::parse($fechaObj->format('Y-m-d').' '.$horario->hora_ini);
                $entradaReal = $marcacion->created_at->copy()->startOfMinute();
                $horaLimite = $horaTeorica->copy()->addMinutes($toleranciaFinal)->startOfMinute();

                if ($entradaReal->greaterThan($horaLimite)) {
                    $esTarde = true;
                    $diferencia = $horaTeorica->diffInMinutes($entradaReal, false);
                    $minutosTarde = $diferencia > 0 ? $diferencia : 0;
                }
            }

            if ($esOlvidoSalida) {
                $key = 'sin_cierre';
            } elseif ($esTarde) {
                $key = $permisoInfo ? 'tarde_con_permiso' : 'tarde';
            } elseif ($tieneExoneracion) {
                $key = 'permiso';
            } else {
                $key = 'presente';
            }

            if (! $horario && ! $esOlvidoSalida) {
                $key = 'extra';
            }

        } else {
            if ($tieneExoneracion) {
                $key = 'permiso';
            } else {
                $horaInicioTurno = Carbon::parse($fechaObj->format('Y-m-d').' '.($horario ? $horario->hora_ini : '00:00'));
                if ($fechaObj->isFuture() || ($fechaObj->isToday() && $hoy->lt($horaInicioTurno))) {
                    $key = 'programado';
                } else {
                    $key = 'ausente';
                }
            }
        }

        // DEVOLVEMOS LA TOLERANCIA FINAL CALCULADA PARA QUE LA VISTA LA MUESTRE CORRECTAMENTE
        return ['key' => $key, 'minutos_tarde' => $minutosTarde, 'permiso_info' => $permisoInfo, 'es_olvido_salida' => $esOlvidoSalida, 'tolerancia_calculada' => $toleranciaFinal];
    }
}
