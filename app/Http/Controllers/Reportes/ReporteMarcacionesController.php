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
    // =========================================================================
    // 1. VISTA WEB
    // =========================================================================
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

    // =========================================================================
    // 2. GENERACIÓN DE PDF
    // =========================================================================
    public function generarPdf(Request $request)
    {
        $registros = $this->generarDataReporte($request); 
        $empresa = Empresa::first(); 

        // Diccionario para traducir el filtro seleccionado a texto legible en el PDF
        $nombresFiltros = [
            'presente' => 'Asistencia Perfecta (Puntuales)',
            'extra' => 'Turnos Extras',
            'tarde_total' => 'Todas las Llegadas Tarde',
            'tarde_sin_permiso' => 'Llegadas Tarde Injustificadas',
            'ausente' => 'Ausencias Injustificadas',
            'con_permiso' => 'Registros Justificados (Con Permiso)',
            'sin_cierre' => 'Olvidos de Salida / Sin Cierre'
        ];

        $filtros = [
            'desde' => $request->input('desde') ?? date('Y-m-01'),
            'hasta' => $request->input('hasta') ?? date('Y-m-d'),
            'sucursal' => $request->filled('sucursal') ? Sucursal::find($request->sucursal)->nombre : 'Todas',
            'incidencia' => $request->filled('incidencia') ? ($nombresFiltros[$request->incidencia] ?? 'Todos') : 'Todos los registros',
        ];

        $pdf = Pdf::loadView('reportes.marcaciones.pdf', compact('registros', 'empresa', 'filtros'));
        $pdf->setPaper('letter', 'landscape');

        return $pdf->stream('reporte_asistencia.pdf');
    }

    // =========================================================================
    // 3. EL MOTOR CORE ADAPTADO A REPORTES
    // =========================================================================
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
        // 1. Obtener Empleados y sus permisos
        $queryEmpleados = Empleado::with(['sucursal', 'puesto', 'permisos' => function($q) use ($desde, $hasta) {
            $q->where('estado', 1)
            ->where(function($q2) use ($desde, $hasta) {
                  $q2->where('fecha_inicio', '<=', $hasta)->where('fecha_fin', '>=', $desde);
              })->with('tipoPermiso');
            }])->where('estado', 1)->whereHas('user', function ($q) {
                $q->whereNotIn('id_rol', [1, 2]);
            })->when($user->id_rol == 2, function ($q) use ($user) {
                return $q->where('id_sucursal', $user->empleado->id_sucursal);
            });

        if ($request->filled('sucursal')) $queryEmpleados->where('id_sucursal', $request->sucursal);
        if ($request->filled('empleado')) $queryEmpleados->where('id', $request->empleado);
        
        $empleados = $queryEmpleados->orderBy('apellidos')->get();
        $empleadosIds = $empleados->pluck('id');

        // 2. Pre-cargar Horarios y Marcaciones
        $historialHorariosTodos = HorarioEmpleado::with('horario')->whereIn('id_empleado', $empleadosIds)->get()->groupBy('id_empleado');
        
        $marcacionesReales = MarcacionEmpleado::whereIn('id_empleado', $empleadosIds)
            ->with(['sucursal', 'salida', 'permisos.tipoPermiso', 'salida.permisos.tipoPermiso'])
            ->where('tipo_marcacion', 1)
            ->whereBetween('created_at', [$desde, $hasta])
            ->get()
            ->groupBy('id_empleado');

        $reporte = collect();
        $periodo = CarbonPeriod::create($desde, $hasta);

        // 3. Procesamiento Día a Día
        foreach ($empleados as $emp) {
            $horariosDelEmpleado = $historialHorariosTodos->get($emp->id, collect());
            $marcacionesDelEmpleado = $marcacionesReales->get($emp->id, collect())->groupBy(fn($m) => $m->created_at->format('Y-m-d'));

            foreach ($periodo as $fechaObj) {
                //if ($fechaObj->isFuture() && !$fechaObj->isSameDay($hoy)) continue;

                $fechaStr = $fechaObj->format('Y-m-d');
                $diaSemana = Str::slug($fechaObj->locale('es')->isoFormat('dddd'));

                $turnosEsperados = $horariosDelEmpleado->filter(function($asig) use ($fechaStr, $diaSemana) {
                    $inicioValido = empty($asig->fecha_inicio) || $asig->fecha_inicio <= $fechaStr;
                    $finValido = empty($asig->fecha_fin) || $asig->fecha_fin >= $fechaStr;
                    if (!$inicioValido || !$finValido || !$asig->horario || empty($asig->horario->dias)) return false;
                    return in_array($diaSemana, array_map(fn($d) => Str::slug($d), $asig->horario->dias));
                })->sortBy(fn($asig) => $asig->horario->hora_ini);

                $marcacionesDelDia = $marcacionesDelEmpleado->get($fechaStr, collect());
                $marcacionesLibres = collect($marcacionesDelDia->all());
                $turnosProcesados = [];

                // RONDA 1: Match Exacto
                foreach ($turnosEsperados as $asig) {
                    $marcacion = $marcacionesLibres->first(function($m) use ($asig) {
                        if ($m->id_horario_historico_empleado && $asig->id_horario_historico) return $m->id_horario_historico_empleado == $asig->id_horario_historico;
                        if ($m->id_horario && $asig->id_horario) return $m->id_horario == $asig->id_horario;
                        return false;
                    });
                    if ($marcacion) {
                        $turnosProcesados[$asig->id] = $marcacion;
                        $marcacionesLibres = $marcacionesLibres->reject(fn($m) => $m->id == $marcacion->id);
                    } else {
                        $turnosProcesados[$asig->id] = null;
                    }
                }

                // RONDA 2: Match Proximidad
                foreach ($turnosEsperados as $asig) {
                    if (is_null($turnosProcesados[$asig->id]) && $marcacionesLibres->isNotEmpty()) {
                        $horaInicioTurno = Carbon::parse($fechaStr . ' ' . $asig->horario->hora_ini);
                        $marcacionCercana = $marcacionesLibres->sortBy(fn($m) => abs($m->created_at->diffInMinutes($horaInicioTurno)))->first();
                        if (abs($marcacionCercana->created_at->diffInMinutes($horaInicioTurno)) <= 300) {
                            $turnosProcesados[$asig->id] = $marcacionCercana;
                            $marcacionesLibres = $marcacionesLibres->reject(fn($m) => $m->id == $marcacionCercana->id);
                        }
                    }
                }

                // Generar Filas del Reporte
                foreach ($turnosEsperados as $asig) {
                    $marcacion = $turnosProcesados[$asig->id];
                    $datosEstado = $this->determinarEstadoReporte($marcacion, $fechaObj, $hoy, $asig->horario, $emp);

                    if ($datosEstado['key'] == 'programado') continue; 

                    $reporte->push([
                        'fecha' => $fechaObj->copy(),
                        'empleado' => $emp,
                        'sucursal' => $emp->sucursal,
                        'horario_programado' => Carbon::parse($asig->horario->hora_ini)->format('H:i').' - '.Carbon::parse($asig->horario->hora_fin)->format('H:i'),
                        'entrada_real' => $marcacion ? $marcacion->created_at : null,
                        'salida_real' => ($marcacion && $marcacion->salida) ? $marcacion->salida->created_at : null,
                        'estado_key' => $datosEstado['key'],
                        'minutos_tarde' => $datosEstado['minutos_tarde'],
                        'permiso_info' => $datosEstado['permiso_info'],
                        'es_olvido_salida' => $datosEstado['es_olvido_salida']
                    ]);
                }

                // Generar Filas para Turnos Extras
                foreach ($marcacionesLibres as $mExtra) {
                    $datosEstado = $this->determinarEstadoReporte($mExtra, $fechaObj, $hoy, null, $emp);
                    $estadoKeyExtra = 'extra';
                    
                    if (!$mExtra->salida && !$mExtra->created_at->isToday()) $estadoKeyExtra = 'sin_cierre';

                    $reporte->push([
                        'fecha' => $fechaObj->copy(),
                        'empleado' => $emp,
                        'sucursal' => $emp->sucursal,
                        'horario_programado' => 'Turno Extra',
                        'entrada_real' => $mExtra->created_at,
                        'salida_real' => $mExtra->salida ? $mExtra->salida->created_at : null,
                        'estado_key' => $estadoKeyExtra,
                        'minutos_tarde' => 0,
                        'permiso_info' => $datosEstado['permiso_info'],
                        'es_olvido_salida' => false
                    ]);
                }
            }
        }

        // =========================================================================
        // 4. APLICAR FILTRO DE INCIDENCIA AVANZADO
        // =========================================================================
        if ($request->filled('incidencia')) {
            $filtro = $request->incidencia;
            
            $reporte = $reporte->filter(function ($row) use ($filtro) {
                switch ($filtro) {
                    case 'presente':
                        // Solo los que llegaron puntuales y sin problemas
                        return $row['estado_key'] === 'presente';
                    case 'tarde_total':
                        // Todas las llegadas tarde (con o sin permiso)
                        return in_array($row['estado_key'], ['tarde', 'tarde_con_permiso']);
                    case 'tarde_sin_permiso':
                        // Llegadas tarde que NO tienen justificación
                        return $row['estado_key'] === 'tarde';
                    case 'ausente':
                        // Faltas completas injustificadas
                        return $row['estado_key'] === 'ausente';
                    case 'con_permiso':
                        // Cualquier registro que tenga un permiso aplicado (ausencias eximidas, llegadas tarde justificadas, etc.)
                        return !empty($row['permiso_info']);
                    case 'sin_cierre':
                        // Olvidos de salida (ya sea porque no marcó nunca o porque marcó otro día)
                        return $row['estado_key'] === 'sin_cierre' || $row['es_olvido_salida'] === true;
                    case 'extra':
                        // Turnos no programados
                        return $row['estado_key'] === 'extra';
                    default:
                        return true;
                }
            });
        }

        return $reporte->sortBy([
            fn($a, $b) => strcmp($a['empleado']->apellidos, $b['empleado']->apellidos),
            fn($a, $b) => $a['fecha']->timestamp <=> $b['fecha']->timestamp,
        ])->values();
    }

    private function determinarEstadoReporte($marcacion, $fechaObj, $hoy, $horario, $emp)
    {
        $key = 'ausente';
        $minutosTarde = 0;
        $permisoInfo = null;
        $esOlvidoSalida = false;

        $permisosDelDia = $emp && $emp->relationLoaded('permisos') ? $emp->permisos->filter(fn($p) => $fechaObj->between(Carbon::parse($p->fecha_inicio), Carbon::parse($p->fecha_fin))) : collect();
        $permisosExoneracion = $permisosDelDia->whereIn('id_tipo_permiso', [5, 6]);
        $tieneExoneracion = $permisosExoneracion->isNotEmpty();

        if ($tieneExoneracion) {
            $p = $permisosExoneracion->first();
            $permisoInfo = ['tipo' => $p->tipoPermiso->nombre, 'motivo' => $p->motivo, 'desde' => $p->fecha_inicio, 'hasta' => $p->fecha_fin];
            $key = 'permiso';
        }

        if ($marcacion) {
            $key = 'presente';

            if ($marcacion->permisos->isNotEmpty()) {
                $p = $marcacion->permisos->first();
                $permisoInfo = ['tipo' => $p->tipoPermiso->nombre, 'motivo' => $p->motivo, 'desde' => $p->fecha_inicio ?? $fechaObj->format('Y-m-d'), 'hasta' => $p->fecha_fin ?? $fechaObj->format('Y-m-d')];
            } elseif ($marcacion->salida && $marcacion->salida->permisos->isNotEmpty()) {
                $p = $marcacion->salida->permisos->first();
                $permisoInfo = ['tipo' => $p->tipoPermiso->nombre, 'motivo' => $p->motivo, 'desde' => $p->fecha_inicio ?? $fechaObj->format('Y-m-d'), 'hasta' => $p->fecha_fin ?? $fechaObj->format('Y-m-d')];
            }

            if ($marcacion->salida) {
                $salidaReal = $marcacion->salida->created_at;
                $esDiaDiferente = $marcacion->created_at->format('Y-m-d') !== $salidaReal->format('Y-m-d');
                $esOlvidoSalida = $marcacion->salida->es_olvido || $esDiaDiferente;
                
                if ($horario && !$esOlvidoSalida) {
                    $finTurno = Carbon::parse($fechaObj->format('Y-m-d') . ' ' . $horario->hora_fin);
                    if ($horario->hora_fin < $horario->hora_ini) $finTurno->addDay();
                    if ($salidaReal->gt($finTurno) && $salidaReal->diffInMinutes($finTurno) > 60) $esOlvidoSalida = true;
                }
            } else {
                if (!$marcacion->created_at->isToday()) $esOlvidoSalida = true;
            }

            $esTarde = $horario && $marcacion->fuera_horario;
            if ($esTarde) {
                $horaTeorica = Carbon::parse($fechaObj->format('Y-m-d') . ' ' . $horario->hora_ini);
                $minutosTarde = $marcacion->created_at->diffInMinutes($horaTeorica->copy()->addMinutes($horario->tolerancia));
            }

            if ($esOlvidoSalida) {
                $key = 'sin_cierre';
            } elseif ($esTarde) {
                $key = $permisoInfo ? 'tarde_con_permiso' : 'tarde';
            } elseif ($permisoInfo || $tieneExoneracion) {
                $key = 'permiso';
            }

            if (!$horario && !$esOlvidoSalida) $key = 'extra';

        } elseif (!$tieneExoneracion) {
            $horaInicioTurno = Carbon::parse($fechaObj->format('Y-m-d') . ' ' . ($horario ? $horario->hora_ini : '00:00'));
            if ($fechaObj->isFuture() || ($fechaObj->isToday() && $hoy->lt($horaInicioTurno))) {
                $key = 'programado'; 
            } else {
                $key = 'ausente';
            }
        }

        return ['key' => $key, 'minutos_tarde' => $minutosTarde, 'permiso_info' => $permisoInfo, 'es_olvido_salida' => $esOlvidoSalida];
    }
}