<?php

namespace App\Http\Controllers\MarcacionApp;

use App\Http\Controllers\Controller;
use App\Models\Empleado\Empleado;
use App\Models\Empleado\HomeOffice;
use App\Models\Horario\horario;
use App\Models\Horario\HorarioHistorico;
use App\Models\HorarioEmpleado\HorarioEmpleado;
use App\Models\Marcacion\MarcacionEmpleado;
use App\Models\Permiso\Permiso;
use App\Models\Sucursales\Sucursal;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class MarcacionController extends Controller
{
    public function __construct()
    {
        // =========================================================================
        // 🧪 ZONA DE PRUEBAS - NIVEL DE CLASE
        // =========================================================================
        // \Carbon\Carbon::setTestNow(now()->setTime(17, 15, 0));
         //Carbon::setTestNow(now()->addDay(6)->setTime(13, 01, 12));
        // dd(Auth::user()->empleado->sucursal->horarios);
    }

    // =========================================================================
    // 1. PANEL ADMINISTRATIVO (Recursos Humanos)
    // =========================================================================

    public function indexPanel(Request $request)
    {
        $hoy = Carbon::now();
        $desde = $request->input('desde') ? Carbon::parse($request->input('desde'))->startOfDay() : $hoy->copy()->startOfDay();
        $hasta = $request->input('hasta') ? Carbon::parse($request->input('hasta'))->endOfDay() : $hoy->copy()->endOfDay();
        $estadoFiltro = $request->get('estado');

        $sucursales = Sucursal::visiblePara(Auth::user())->where('estado', 1)->get();
        $empleadosList = Empleado::visiblePara(Auth::user())->where('estado', 1)
            ->whereHas('user', function ($q) {
                $q->whereNotIn('id_rol', [1, 2]);
            })->orderBy('nombres')->get();

        $empleadosEvaluar = $this->obtenerEmpleadosFiltrados($request, $desde, $hasta);
        $empleadosIds = $empleadosEvaluar->pluck('id');

        // Pre-cargas para optimizar consultas a la base de datos
        $historialHorariosTodos = HorarioEmpleado::with(['horario', 'historico'])->
        whereIn('id_empleado', $empleadosIds)->get()->
        groupBy('id_empleado');

        $marcacionesReales = $this->obtenerMarcacionesEnRango($empleadosIds, $desde, $hasta);
        $periodo = CarbonPeriod::create($desde, $hasta);

        $datosAgrupados = [];

        foreach ($empleadosEvaluar as $emp) {
            $horariosDelEmpleado = $historialHorariosTodos->get($emp->id, collect());
            $marcacionesDelEmpleado = $marcacionesReales->get($emp->id, collect())->groupBy(fn ($m) => $m->created_at->format('Y-m-d'));

            // Llamada al MOTOR CORE UNIFICADO
            $fechasProcesadas = $this->procesarDiasHistorial($emp, $horariosDelEmpleado, $marcacionesDelEmpleado, $periodo, $hoy, $estadoFiltro, false);

            if (! empty($fechasProcesadas)) {
                $datosAgrupados[] = [
                    'empleado' => $emp,
                    'fechas' => $fechasProcesadas,
                ];
            }
        }

        return view('marcaciones.index', compact('datosAgrupados', 'sucursales', 'empleadosList'));
    }

    // =========================================================================
    // 3. APP EMPLEADO - INICIO (MARCAR)
    // =========================================================================

    public function index()
    {
        $hoy = Carbon::today();
        $ahora = now();
        $empleado = Auth::user()->empleado;
        $sucursal = $empleado->sucursal;
        $diaSemanaHoy = $ahora->locale('es')->isoFormat('dddd');

        // 1. Validar Permisos que Eximen
        $permisos = $this->validaPermisos();
        $permisoActivo = collect([$permisos['sin_marcacion'], $permisos['incapacidad']])->filter()->first();
        $historialHoy = collect();
        $horariosActivos = $empleado->horarios()->wherePivot('es_actual', 1)->get();

        if ($permisoActivo) {
            return view('app_marcacion.inicio', compact('permisoActivo', 'historialHoy', 'horariosActivos'));
        }

        // 2. Obtener Actividad de Hoy
        $historialHoy = MarcacionEmpleado::where('id_empleado', $empleado->id)
            ->whereDate('created_at', $hoy)
            ->with(['sucursal', 'permisos.tipoPermiso'])
            ->orderByDesc('created_at')->orderByDesc('id')
            ->get();

        $entradasHoyIds = $historialHoy->where('tipo_marcacion', 1)->pluck('id')->toArray();

        // 3. Determinar Estado Actual (Entrada Abierta)
        $entradaActiva = MarcacionEmpleado::where('id_empleado', $empleado->id)
            ->where('tipo_marcacion', 1)->whereDoesntHave('salida')->orderByDesc('id')->first();

        // 4. Buscar Horarios Candidatos
        $candidatos = $this->obtenerCandidatosVigentesHoy($empleado, $sucursal, $diaSemanaHoy);

        // 5. Bloqueos y Decisiones Visuales
        $estadoJornada = $this->calcularEstadoJornadaApp($candidatos, $historialHoy, $entradasHoyIds, $entradaActiva, $hoy, $ahora);

        $horarioActivo = ($entradaActiva && $entradaActiva->id_horario) ? $entradaActiva->horario : null;
        $horarioRequiereSalida = $sucursal->horarios()->where('requiere_salida', 1)->exists() ? 1 : 0;

        [$mostrarModalBloqueo, $marcacionPendiente] = $this->validarBloqueoSalida($horarioRequiereSalida, $entradaActiva, $horarioActivo);


        return view('app_marcacion.inicio', array_merge([
            'horariosActivos' => $horariosActivos,
            'entradaActiva' => $entradaActiva,
            'horarioRequiereSalida' => $horarioRequiereSalida,
            'mostrarModalBloqueo' => $mostrarModalBloqueo,
            'marcacionPendiente' => $marcacionPendiente,
            'historialHoy' => $historialHoy,
            'candidatos' => $candidatos,
            'permisoActivo' => $permisoActivo,
        ], $estadoJornada));
    }

    // =========================================================================
    // 4. MOTOR CORE DE HISTORIAL (Compartido por Admin y Empleado)
    // =========================================================================

    private function procesarDiasHistorial($empleado, $turnosAsignados, $marcacionesDelEmpleado, $periodo, $hoy, $estadoFiltro = null, $incluirHoyVacio = false)
    {
        $diasProcesados = [];

        foreach ($periodo as $fechaObj) {
            $fechaStr = $fechaObj->format('Y-m-d');
            $diaSemana = Str::slug($fechaObj->locale('es')->isoFormat('dddd'));

            // A) Turnos programados
            $turnosEsperados = $turnosAsignados->filter(function ($asig) use ($fechaStr, $diaSemana) {
                $inicioValido = empty($asig->fecha_inicio) || $asig->fecha_inicio <= $fechaStr;
                $finValido = empty($asig->fecha_fin) || $asig->fecha_fin >= $fechaStr;

                if (! $inicioValido || ! $finValido || ! $asig->horario) {
                    return false;
                }

                // Leemos los días del histórico (si hay) o del maestro
                $diasTurno = ($asig->historico && ! empty($asig->historico->dias)) ? $asig->historico->dias : $asig->horario->dias;

                if (empty($diasTurno)) {
                    return false;
                }

                return in_array($diaSemana, array_map(fn ($d) => Str::slug($d), $diasTurno));

            })->map(function ($asig) {
                // Clonamos para no alterar los datos de otros días en el bucle
                $newAsig = clone $asig;
                $newHorario = clone $asig->horario;

                // MAGIA: Si la asignación tiene un histórico, usamos esas horas
                if ($newAsig->historico) {
                    $newHorario->hora_ini = $newAsig->historico->hora_entrada; // Ojo: verifica si es hora_entrada o hora_ini
                    $newHorario->hora_fin = $newAsig->historico->hora_salida;
                    $newHorario->tolerancia = $newAsig->historico->tolerancia;
                }

                $newAsig->horario = $newHorario;

                return $newAsig;

            })->sortBy(fn ($asig) => $asig->horario->hora_ini);

            $marcacionesDelDia = $marcacionesDelEmpleado->get($fechaStr, collect());
            $marcacionesLibres = collect($marcacionesDelDia->all());
            $turnosProcesados = [];
            $turnosDelDia = collect();

            // RONDA 1: Match Exacto (Histórico o Normal)
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

            // RONDA 2: Match Proximidad (Fallback 5 horas)
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

            $completados = 0;

            // Construir Tarjetas Programadas
            foreach ($turnosEsperados as $asig) {
                $marcacion = $turnosProcesados[$asig->id];
                $estado = $this->determinarEstadoVisual($marcacion, $fechaObj, $hoy, $asig->horario, $empleado);

                if ($estadoFiltro == 'sin_cierre' && ! in_array($estado->texto, ['Sin Salida', 'En Turno'])) {
                    continue;
                }
                if (str_contains($estado->texto, 'Completado') || $estado->texto === 'Permiso Aprobado') {
                    $completados++;
                }

                $turnosDelDia->push((object) ['horario' => $asig->horario, 'marcacion' => $marcacion, 'estado' => $estado]);
            }

            // Construir Tarjetas Extra
            foreach ($marcacionesLibres as $mExtra) {
                $estadoExtra = (object) [
                    'texto' => $mExtra->salida ? 'Turno Extra' : 'Extra En Curso',
                    'clase' => 'bg-purple-100 text-purple-800 border-purple-200',
                    'borde' => 'bg-purple-500',
                ];
                if ($estadoFiltro == 'sin_cierre' && $estadoExtra->texto != 'Extra En Curso') {
                    continue;
                }
                $turnosDelDia->push((object) ['horario' => null, 'marcacion' => $mExtra, 'estado' => $estadoExtra]);
            }

            // Agregar al arreglo si tiene datos o si es el día actual (si se requiere)
            $esHoy = $fechaObj->isSameDay($hoy);
            if ($turnosDelDia->isNotEmpty() || $turnosEsperados->isNotEmpty() || ($incluirHoyVacio && $esHoy)) {
                $diasProcesados[$fechaStr] = [
                    'fecha_obj' => $fechaObj->copy(),
                    'total_turnos' => $turnosEsperados->count(),
                    'completados' => $completados,
                    'turnos' => $turnosDelDia->sortBy(fn ($t) => $t->horario ? $t->horario->hora_ini : '24:00')->values(),
                ];
            }
        }

        return $diasProcesados;
    }

    private function determinarEstadoVisual($marcacion, $fechaObj, $hoy, $horario, $emp)
    {
        $estado = (object) ['texto' => '', 'clase' => '', 'borde' => ''];

        $permisosDelDia = $emp && $emp->relationLoaded('permisos') ? $emp->permisos->filter(fn ($p) => $fechaObj->between(Carbon::parse($p->fecha_inicio), Carbon::parse($p->fecha_fin))) : collect();
        $permisosExoneracion = $permisosDelDia->whereIn('id_tipo_permiso', [5, 6]);
        $tieneExoneracion = $permisosExoneracion->isNotEmpty();

        if ($marcacion) {

            $esTardeReal = ($marcacion->fuera_horario == 1);

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

                $tieneObservacion = $esTardeReal || $esOlvidoSalida;
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

    // =========================================================================
    // 5. PROCESO DE MARCACIÓN (STORE)
    // =========================================================================

    public function store(Request $request)
    {
        $validated = $this->validateRequest($request);
        $empleado = Auth::user()->empleado;
        $sucursal = $empleado->sucursal;
        $fechaReferencia = now();
        $entradaAbierta = null;

        // Validar sucursal abierta si es Entrada
        if ($request->tipo_marcacion == 1 && ! $this->isSucursalAbierta($sucursal, now())) {
            return back()->withErrors(['error' => 'La sucursal se encuentra cerrada en este horario.']);
        }

        // Buscar entrada si es Salida
        if ($validated['tipo_marcacion'] == 2) {
            $entradaAbierta = MarcacionEmpleado::where('id_empleado', $empleado->id)
                ->where('tipo_marcacion', 1)->whereDoesntHave('salida')->latest()->first();
            if ($entradaAbierta) {
                $fechaReferencia = $entradaAbierta->created_at;
            }
        }

        $diaSemana = $this->getDiaSemanaEspanol($fechaReferencia);

        // Validaciones de Negocio
        if (! $this->isDiaLaboralSucursal($sucursal, $diaSemana)) {
            return back()->withErrors(['error' => "La sucursal no labora los días $diaSemana."]);
        }

        $horarioHoy = $this->determinarHorario($empleado, $sucursal, $diaSemana, $validated['tipo_marcacion'], $entradaAbierta);
        if (! $horarioHoy) {
            return back()->withErrors(['error' => "No se encontró un horario asignado para el $diaSemana."]);
        }

        $validacionTiempo = $this->validarTiemposTurno($horarioHoy, $fechaReferencia, $validated['tipo_marcacion'], $entradaAbierta);
        if (isset($validacionTiempo['error'])) {
            return back()->withErrors(['error' => $validacionTiempo['error']]);
        }
        // =========================================================
        //  VERIFICACIÓN DE TRABAJO REMOTO PARA EL DÍA DE HOY
        // =========================================================
        $esRemoto = false;
        
        // 1. Buscamos explícitamente el registro ACTIVO para este empleado
        $configRemoto = HomeOffice::where('id_empleado', $empleado->id)
                            ->where('es_actual', true)
                            ->first();

        if ($configRemoto) {
            // 2. Nos aseguramos de tener un array válido (por si acaso viene como string JSON)
            $diasAsignados = is_string($configRemoto->dias) ? json_decode($configRemoto->dias, true) : $configRemoto->dias;
            
            if (is_array($diasAsignados)) {
                // Convertimos todo a minúsculas por seguridad
                $diasRemoto = array_map('mb_strtolower', $diasAsignados);
                
                // Comparamos si el día de hoy está en su lista
                if (in_array(mb_strtolower($diaSemana), $diasRemoto)) {
                    $esRemoto = true;
                }
            }
        }
        
        $validacionGPS = $this->validarGPS($validated, $sucursal, $validacionTiempo['es_olvido'], $esRemoto);
        
        if (isset($validacionGPS['error'])) {
            return back()->withErrors(['error' => $validacionGPS['error']]);
        }

        // =========================================================
        // 🌟 MAGIA DINÁMICA DE PERMISOS PARA GUARDAR EN BD
        // =========================================================
        $permisosActivos = $this->validaPermisos();
        $permisosTotales = [];

        foreach ($permisosActivos['permisos'] as $codigo => $permiso) {
            // Regla dinámica 1: LLEGADA_TARDE solo se guarda en Entrada (1)
            if ($codigo === 'LLEGADA_TARDE' && $validated['tipo_marcacion'] == 2) {
                continue;
            }

            // Regla dinámica 2: SALIDA_TEMPRANA solo se guarda en Salida (2)
            if ($codigo === 'SALIDA_TEMPRANA' && $validated['tipo_marcacion'] == 1) {
                continue;
            }

            // El resto (FUERA_RANGO, TELETRABAJO, etc.) aplican a ambos
            $permisosTotales[] = $permiso->id;
        }
        
        // Transacción Base de Datos
        $marcacion = $this->guardarRegistro($validated, $empleado, $sucursal, $horarioHoy, $validacionTiempo, $validacionGPS['distancia'], $entradaAbierta, $diaSemana, $permisosTotales, $esRemoto);

        // Foto
        $this->procesarImagen($request->file('ubi_foto'), $marcacion, $empleado, $validated['tipo_marcacion']);

        $msj = $validacionTiempo['es_olvido'] ? 'Salida registrada (Regularización).' : ($validated['tipo_marcacion'] == 1 ? 'Entrada registrada.' : 'Salida registrada.');

        return back()->with('success', $msj);
    }

    private function validateRequest($request)
    {
        return $request->validate([
            'latitud' => 'required|numeric|between:-90,90',
            'longitud' => 'required|numeric|between:-180,180',
            'ubicacion' => 'nullable|string|max:255',
            'tipo_marcacion' => 'required|in:1,2',
            'ubi_foto' => 'required|image|max:5120',
        ], [
            'latitud.required' => 'Ubicación no detectada.',
            'ubi_foto.required' => 'Debes tomar la foto de evidencia.',
        ]);
    }

    private function isSucursalAbierta($sucursal, Carbon $fechaHora)
    {
        $diaNormalizado = Str::slug($this->getDiaSemanaEspanol($fechaHora));
        $horariosSucursal = $sucursal->horarios->filter(fn ($h) => in_array($diaNormalizado, array_map(fn ($d) => Str::slug($d), $h->dias ?? [])));

        if ($horariosSucursal->isEmpty()) {
            return false;
        }

        foreach ($horariosSucursal as $hs) {
            $inicio = Carbon::parse($fechaHora->format('Y-m-d').' '.$hs->hora_ini);
            $fin = Carbon::parse($fechaHora->format('Y-m-d').' '.$hs->hora_fin);
            if ($fin->lessThan($inicio)) {
                $fin->addDay();
            }
            if ($fechaHora->between($inicio->copy()->subMinutes(60), $fin->copy()->addHour())) {
                return true;
            }
        }

        return false;
    }

    private function determinarHorario($empleado, $sucursal, $diaSemana, $tipoMarcacion, $entradaAbierta)
    {
        $ahora = now();

        // ---------------------------------------------------------
        // ESCENARIO 1: SALIDA (USAR ID GUARDADO) - ¡INFALIBLE!
        // ---------------------------------------------------------
        if ($tipoMarcacion == 2 && $entradaAbierta) {
            if ($entradaAbierta->id_horario) {
                return horario::find($entradaAbierta->id_horario);
            }

            $candidatos = $empleado->horarios()->whereJsonContains('dias', $diaSemana)->get();
            if ($candidatos->isEmpty()) {
                $candidatos = $sucursal->horarios()->whereJsonContains('dias', $diaSemana)->get();
            }

            $horaEntrada = Carbon::parse($entradaAbierta->created_at->format('H:i:s'));
            foreach ($candidatos as $h) {
                if ($horaEntrada->diffInMinutes(Carbon::parse($h->hora_ini)) < 240) {
                    return $h;
                }
            }

            return $candidatos->first();
        }

        // ---------------------------------------------------------
        // ESCENARIO 2: ENTRADA (SELECCIÓN INTELIGENTE + NOCTURNOS)
        // ---------------------------------------------------------

        // PASO 1: Obtener horarios del día actual
        $candidatosHoy = $empleado->horarios()->whereJsonContains('dias', $diaSemana)->get();
        if ($candidatosHoy->isEmpty()) {
            $candidatosHoy = $sucursal->horarios()->whereJsonContains('dias', $diaSemana)->get();
        }

        // PASO 2: Obtener horarios del DÍA ANTERIOR (Magia Nocturna)
        // Si son las 2 AM del Martes, debemos buscar si el Lunes había un turno nocturno
        $ayer = $ahora->copy()->subDay();
        $diaAyerEspanol = $this->getDiaSemanaEspanol($ayer);
        $diaAyerSlug = Str::slug(substr($diaAyerEspanol, 0, 3)); // 'lun', 'mar', etc.

        $candidatosAyer = $empleado->horarios()->whereJsonContains('dias', $diaAyerEspanol)->get(); // Ajusta según cómo guardes en BD (ej: si guardas "Lunes" o "lun")
        if ($candidatosAyer->isEmpty()) {
            $candidatosAyer = $sucursal->horarios()->whereJsonContains('dias', $diaAyerEspanol)->get();
        }

        // Filtramos SOLO los turnos de ayer que cruzan la medianoche
        $turnosNocturnosDeAyer = collect();
        foreach ($candidatosAyer as $hAyer) {
            if ($hAyer->hora_fin < $hAyer->hora_ini) {
                $turnosNocturnosDeAyer->push($hAyer);
            }
        }

        $mejorCandidato = null;
        $minutosDiferencia = PHP_INT_MAX;

        // EVALUACIÓN A: Buscar si la hora actual "CAE DENTRO" de un turno nocturno de AYER
        // (Ej: Son las 01:00 AM del martes, y el lunes tenía turno de 20:00 a 08:00)
        foreach ($turnosNocturnosDeAyer as $hNoche) {
            $inicioAyer = Carbon::parse($ayer->format('Y-m-d').' '.$hNoche->hora_ini);
            $finHoy = Carbon::parse($ahora->format('Y-m-d').' '.$hNoche->hora_fin); // Como es la hora fin, ya es "hoy"

            if ($ahora->between($inicioAyer->copy()->subMinutes(60), $finHoy)) {
                return $hNoche; // ¡Cayó exactamente dentro del turno de la madrugada!
            }
        }

        // EVALUACIÓN B: Buscar si "CAIGO DENTRO" de algún turno de HOY
        foreach ($candidatosHoy as $h) {
            $inicio = Carbon::parse($ahora->format('Y-m-d').' '.$h->hora_ini);
            $fin = Carbon::parse($ahora->format('Y-m-d').' '.$h->hora_fin);
            if ($fin->lessThan($inicio)) {
                $fin->addDay();
            } // Si este mismo turno hoy es nocturno

            if ($ahora->between($inicio->copy()->subMinutes(60), $fin)) {
                return $h;
            }
        }

        // EVALUACIÓN C: Si no caigo en ninguno, buscar el MÁS CERCANO en el futuro HOY
        if ($candidatosHoy->isNotEmpty()) {
            foreach ($candidatosHoy as $h) {
                $inicio = Carbon::parse($ahora->format('Y-m-d').' '.$h->hora_ini);
                $diff = abs($ahora->diffInMinutes($inicio, false));
                if ($ahora->diffInHours($inicio, false) >= -8 && $diff < $minutosDiferencia) {
                    $minutosDiferencia = $diff;
                    $mejorCandidato = $h;
                }
            }
        }

        return $mejorCandidato ?? $candidatosHoy->last();
    }

    private function validarTiemposTurno($horario, $fechaReferencia, $tipoMarcacion, $entradaAbierta)
    {
        $permisos = $this->validaPermisos();

        if (collect([$permisos['sin_marcacion'], $permisos['incapacidad']])->filter()->first()) {
            return ['error' => 'Permiso activo exime marcación.'];
        }

        $fechaBase = $fechaReferencia->format('Y-m-d');
        $inicioTurno = Carbon::parse($fechaBase.' '.$horario->hora_ini);
        $finTurno = Carbon::parse($fechaBase.' '.$horario->hora_fin);

        if ($finTurno->lessThanOrEqualTo($inicioTurno)) {
            $finTurno->addDay();
        }

        // Ya no declaramos 'permisos_aplicados' aquí
        $resultado = ['fuera_horario' => null, 'es_olvido' => false];

        if ($tipoMarcacion == 1) {
            if (now()->lessThanOrEqualTo($finTurno)) {

                $empleado = Auth::user()->empleado;
                $sucursal = $empleado->sucursal;

                // 1. Fallback base: Tolerancia del turno del empleado
                $toleranciaAplicar = $horario->tolerancia;

                // 2. REGLA PRIORITARIA: Buscar tolerancia de la sucursal (Turno más cercano)
                if ($sucursal && $sucursal->horarios && $sucursal->horarios->isNotEmpty()) {

                    $horarioSucursal = $sucursal->horarios->sortBy(function ($hs) use ($inicioTurno) {
                        // Armamos la hora de la sucursal para compararla con el inicioTurno actual
                        $horaInicioSucursal = Carbon::parse(now()->format('Y-m-d').' '.$hs->hora_ini);

                        return abs($inicioTurno->diffInMinutes($horaInicioSucursal));
                    })->first();

                    if ($horarioSucursal) {
                        $toleranciaAplicar = $horarioSucursal->tolerancia;
                    }
                }

                // 3. EXCEPCIÓN SUPREMA: Permiso de Llegada Tarde
                // (Si existe, suma los minutos a la tolerancia, pero ya no guarda el ID)
                if (isset($permisos['llegada_tarde']) && $permisos['llegada_tarde']) {
                    $toleranciaAplicar += (int) $permisos['llegada_tarde']->valor;
                }

                // 4. LA TRAMPA DE LOS SEGUNDOS: Ignorar segundos para ser justos
                $horaMarcaje = now()->startOfMinute();
                $horaLimite = $inicioTurno->copy()->addMinutes($toleranciaAplicar)->startOfMinute();

                // 5. Evaluar la verdad absoluta
                if ($horaMarcaje->greaterThan($horaLimite)) {
                    $resultado['fuera_horario'] = 1;
                } else {
                    $resultado['fuera_horario'] = 0; // Guardamos 0 explícito para evitar NULLs
                }

            } else {
                return ['error' => 'Tu jornada para este turno ya finalizó.'];
            }
        } else {
            if ($entradaAbierta) {
                $limiteOlvido = $finTurno->copy()->addHour();
                $momentoMinimo = $finTurno->copy();

                // Si tiene salida temprana, restamos los minutos al límite inferior
                if ($permisos['salida_temprana']) {
                    $momentoMinimo->subMinutes($permisos['salida_temprana']->valor);
                }

                if (now()->greaterThan($limiteOlvido)) {
                    $resultado['es_olvido'] = true;
                    $resultado['fuera_horario'] = 1;
                } elseif (now()->lessThan($momentoMinimo)) {
                    return ['error' => 'Salida no permitida antes de las '.$momentoMinimo->format('d/m H:i')];
                }
            }
        }

        return $resultado;
    }

    private function validarGPS($validated, $sucursal, $esOlvido, $esRemoto = false)
    {
        // 1. Si es una regularización de olvido de un día pasado, la distancia no importa
        if ($validated['tipo_marcacion'] == 2 && $esOlvido) {
            return ['distancia' => 0];
        }

        // 2. Calculamos la distancia REAL sin importar qué pase después
        $distancia = $this->distanciaEnMetros($validated['latitud'], $validated['longitud'], $sucursal->latitud, $sucursal->longitud);

        // 3. LÓGICA HOME OFFICE: Si hoy le toca remoto, lo dejamos pasar con su distancia real
        if ($esRemoto) {
            return ['distancia' => $distancia];
        }

        // 4. Si es presencial, aplicamos las validaciones estrictas normales
        $permisos = $this->validaPermisos();
        $rango = $sucursal->rango_marcacion_mts;

        // Si tiene permiso fuera de rango, hacemos la matemática
        if ($permisos['fuera_rango']) {
            $rango = $permisos['fuera_rango']->cantidad_mts === null ? PHP_INT_MAX : $rango + $permisos['fuera_rango']->cantidad_mts;
        }

        if ($distancia > ($rango + $sucursal->margen_error_gps_mts)) {
            return ['error' => "Estás fuera del rango permitido ($distancia mts)."];
        }

        return ['distancia' => $distancia];
    }

    private function guardarRegistro($validated, $empleado, $sucursal, $horario, $validacionTiempo, $distancia, $entradaAbierta, $diaSemana, $permisosTotales, $esRemoto)
    {
        return DB::transaction(function () use ($diaSemana, $validated, $empleado, $sucursal, $horario, $validacionTiempo, $distancia, $entradaAbierta, $permisosTotales,$esRemoto) {

            $horarioSucursal = $sucursal->horarios->first(fn ($h) => $h->permitido_marcacion == 1 && in_array($diaSemana, $h->dias));

            $historicoEmpleado = HorarioHistorico::mismoHorario($horario)->vigente()->first();
            if (! $historicoEmpleado) {
                HorarioHistorico::where('id_horario', $horario->id)->where('tipo_horario', $horario->permitido_marcacion)->vigente()->update(['vigente_hasta' => now()]);
                $historicoEmpleado = HorarioHistorico::create([
                    'id_horario' => $horario->id, 'tipo_horario' => $horario->permitido_marcacion, 'hora_entrada' => $horario->hora_ini,
                    'hora_salida' => $horario->hora_fin, 'tolerancia' => $horario->tolerancia, 'dias' => $horario->dias, 'vigente_desde' => now(),
                ]);
            }

            $historicoSucursal = null;
            if ($horarioSucursal) {
                $historicoSucursal = HorarioHistorico::mismoHorario($horarioSucursal)->vigente()->first();
                if (! $historicoSucursal) {
                    HorarioHistorico::where('id_horario', $horarioSucursal->id)->where('tipo_horario', $horarioSucursal->permitido_marcacion)->vigente()->update(['vigente_hasta' => now()]);
                    $historicoSucursal = HorarioHistorico::create([
                        'id_horario' => $horarioSucursal->id, 'tipo_horario' => $horarioSucursal->permitido_marcacion, 'hora_entrada' => $horarioSucursal->hora_ini,
                        'hora_salida' => $horarioSucursal->hora_fin, 'tolerancia' => $horarioSucursal->tolerancia, 'dias' => $horarioSucursal->dias, 'vigente_desde' => now(),
                    ]);
                }
            }

            $marcacion = MarcacionEmpleado::create([
                'id_empleado' => $empleado->id, 'id_sucursal' => $sucursal->id, 'id_horario' => $horario->id,
                'id_horario_historico_empleado' => $historicoEmpleado->id ?? null, 'id_horario_historico_sucursal' => $historicoSucursal->id ?? null,
                'latitud' => $validated['latitud'], 'longitud' => $validated['longitud'], 'distancia_real_mts' => $distancia,
                'tipo_marcacion' => $validated['tipo_marcacion'], 'fuera_horario' => $validacionTiempo['fuera_horario'],
                'id_marcacion_entrada' => $validated['tipo_marcacion'] == 2 ? $entradaAbierta?->id : null,
                'es_remoto' => $esRemoto,
            ]);

            if (! empty($permisosTotales)) {
                $insertData = array_map(fn ($idP) => ['id_marcacion' => $marcacion->id, 'id_permiso' => $idP], $permisosTotales);
                DB::table('permisos_marcaciones')->insert($insertData);
            }

            return $marcacion;
        });
    }

    private function procesarImagen($file, $marcacion, $empleado, $tipo)
    {
        try {
            $tipoTexto = $tipo == 1 ? 'entrada' : 'salida';
            $nombre = "{$empleado->cod_trabajador}_{$marcacion->id}_{$tipoTexto}_".now()->format('YmdHis').'.jpg';

            $rutaMini = "marcaciones_empleados/$nombre";
            $rutaFull = "marcaciones_empleados/full/$nombre";

            // 1. GUARDADO ULTRA RÁPIDO: Guardamos el archivo que ya viene comprimido por JS directamente.
            // Esto evita la "doble compresión" y no gasta memoria RAM del servidor.
            Storage::disk('public')->put($rutaFull, file_get_contents($file));

            // 2. CREAR MINIATURA: Usamos Intervention Image SOLO para la fotito pequeña de las tablas.
            $manager = new ImageManager(new Driver);
            $encodedMini = $manager->read($file)->scaleDown(width: 400)->toJpeg(70);
            Storage::disk('public')->put($rutaMini, (string) $encodedMini);

            // 3. Actualizamos BD
            $marcacion->update([
                'ubi_foto' => $rutaMini,
                'ubi_foto_full' => $rutaFull,
            ]);

        } catch (\Exception $e) {
            Log::error("Error procesando foto marcación ID {$marcacion->id}: ".$e->getMessage());
        }
    }

    // =========================================================================
    // 6. UTILIDADES COMPARTIDAS
    // =========================================================================

    private function obtenerEmpleadosFiltrados(Request $request, $desde, $hasta)
    {
        $user = Auth::user();

        $query = Empleado::with(['sucursal', 'puesto', 'permisos' => function ($q) use ($desde, $hasta) {
            $q->where('estado', 1)->where(function ($q2) use ($desde, $hasta) {
                $q2->where('fecha_inicio', '<=', $hasta)->where('fecha_fin', '>=', $desde);
            })->with('tipoPermiso');
        }])
            ->where('estado', 1)
            ->whereHas('user', function ($q) {
                $q->whereNotIn('id_rol', [1, 2]);
            })
            ->when($user->id_rol == 2, function ($q) use ($user) {
                return $q->where('id_sucursal', $user->empleado->id_sucursal);
            });

        if ($request->filled('empleado')) {
            $query->where('id', $request->empleado);
        }
        if ($request->filled('sucursal')) {
            $query->where('id_sucursal', $request->sucursal);
        }

        return $query->orderBy('nombres')->get();
    }

    private function obtenerMarcacionesEnRango($empleadosIds, $desde, $hasta)
    {
        return MarcacionEmpleado::visiblePara(Auth::user())
            ->with(['sucursal', 'salida', 'permisos.tipoPermiso', 'salida.permisos.tipoPermiso'])
            ->whereIn('id_empleado', $empleadosIds)
            ->where('tipo_marcacion', 1)
            ->whereBetween('created_at', [$desde, $hasta])
            ->get()
            ->groupBy('id_empleado');
    }

    private function determinarRangoFechasHistorialApp(Request $request, $empleadoId, $hoy)
    {
        $desde = $request->input('desde') ? Carbon::parse($request->input('desde'))->startOfDay() : Carbon::now()->startOfMonth()->startOfDay();
        $hastaPorDefecto = $hoy->copy()->endOfDay();

        $ultimaMarcacion = MarcacionEmpleado::where('id_empleado', $empleadoId)->max('created_at');
        if ($ultimaMarcacion && Carbon::parse($ultimaMarcacion)->endOfDay()->greaterThan($hastaPorDefecto)) {
            $hastaPorDefecto = Carbon::parse($ultimaMarcacion)->endOfDay();
        }

        $hasta = $request->input('hasta') ? Carbon::parse($request->input('hasta'))->endOfDay() : $hastaPorDefecto;

        return [$desde, $hasta];
    }

    private function obtenerEmpleadoConPermisos($empleadoId, $desde, $hasta)
    {
        return Empleado::with(['sucursal', 'puesto', 'permisos' => function ($q) use ($desde, $hasta) {
            $q->where('estado', 1)->where(function ($q2) use ($desde, $hasta) {
                $q2->where('fecha_inicio', '<=', $hasta)->where('fecha_fin', '>=', $desde);
            })->with('tipoPermiso');
        }])->find($empleadoId);
    }

    private function obtenerCandidatosVigentesHoy($empleado, $sucursal, $diaSemana)
    {
        $normalizarDia = fn ($dia) => substr(Str::slug($dia), 0, 3);
        $hoyNorm = $normalizarDia($diaSemana);

        // 1. Obtener los horarios específicos asignados al empleado para el día de hoy
        $candidatosRaw = $empleado->horarios()
            ->wherePivot('es_actual', 1)
            ->get()
            ->filter(fn ($h) => in_array($hoyNorm, array_map($normalizarDia, $h->dias ?? [])));

        // 2. Si el empleado no tiene turnos personalizados, usamos los de la sucursal como respaldo
        if ($candidatosRaw->isEmpty()) {
            $candidatosRaw = $sucursal->horarios->filter(fn ($h) => in_array($hoyNorm, array_map($normalizarDia, $h->dias ?? [])));
        }

        // 3. Retornamos los turnos ordenados cronológicamente.
        // (Se eliminó el cruce restrictivo con la sucursal para que respete horarios como el de 14:00 a 17:30)
        return $candidatosRaw->sortBy('hora_ini')->values();
    }

    private function calcularEstadoJornadaApp($candidatos, $historialHoy, $entradasHoyIds, $entradaActiva, $hoy, $ahora)
    {
        if ($entradaActiva) {
            return ['habilitarEntrada' => false, 'proximoHorario' => null, 'tiempoRestante' => null, 'jornadaTerminada' => false, 'ausenteTotal' => false];
        }

        $turnoVigente = null;
        $siguienteTurno = null;
        $minutosParaSiguiente = PHP_INT_MAX;

        foreach ($candidatos as $h) {
            $inicio = Carbon::parse($hoy->format('Y-m-d').' '.$h->hora_ini);
            $fin = Carbon::parse($hoy->format('Y-m-d').' '.$h->hora_fin);
            if ($fin->lessThan($inicio)) {
                $fin->addDay();
            }

            $inicioHabilitado = $inicio->copy()->subMinutes(60);

            $yaCompletado = $historialHoy->contains(fn ($m) => $m->tipo_marcacion == 1 && $m->id_horario == $h->id) &&
                            $historialHoy->contains(fn ($m) => $m->tipo_marcacion == 2 && $m->id_horario == $h->id && in_array($m->id_marcacion_entrada, $entradasHoyIds));

            if ($ahora->between($inicioHabilitado, $fin)) {
                if (! $yaCompletado) {
                    $turnoVigente = $h;
                    break;
                }
            } elseif ($inicioHabilitado->greaterThan($ahora) && ! $yaCompletado) {
                $diff = $ahora->diffInMinutes($inicio);
                if ($diff < $minutosParaSiguiente) {
                    $minutosParaSiguiente = $diff;
                    $siguienteTurno = $inicio;
                }
            }
        }

        if ($turnoVigente) {
            return ['habilitarEntrada' => true, 'proximoHorario' => null, 'tiempoRestante' => null, 'jornadaTerminada' => false, 'ausenteTotal' => false];
        }
        if ($siguienteTurno) {
            return ['habilitarEntrada' => false, 'proximoHorario' => $siguienteTurno, 'tiempoRestante' => $siguienteTurno->locale('es')->diffForHumans($ahora, ['parts' => 2, 'join' => true, 'syntax' => Carbon::DIFF_ABSOLUTE]), 'jornadaTerminada' => false, 'ausenteTotal' => false];
        }

        // --- NUEVA LÓGICA DE FIN DE DÍA ---
        // Si hay turnos asignados pero NO hay marcaciones, es ausencia total.
        $ausenteTotal = $candidatos->isNotEmpty() && $historialHoy->isEmpty();

        // Si hay turnos y SÍ hay marcaciones (completó su día, o al menos vino a 1 turno)
        $jornadaTerminada = $candidatos->isNotEmpty() && $historialHoy->isNotEmpty();

        return [
            'habilitarEntrada' => false,
            'proximoHorario' => null,
            'tiempoRestante' => null,
            'jornadaTerminada' => $jornadaTerminada,
            'ausenteTotal' => $ausenteTotal,
        ];
    }

    private function validarBloqueoSalida($horarioRequiereSalida, $entradaActiva, $horarioActivo)
    {
        if ($horarioRequiereSalida == 1 && $entradaActiva && $horarioActivo) {
            $salidaTeorica = Carbon::parse($entradaActiva->created_at->format('Y-m-d').' '.$horarioActivo->hora_fin);
            if (Carbon::parse($horarioActivo->hora_fin)->lessThan(Carbon::parse($horarioActivo->hora_ini))) {
                $salidaTeorica->addDay();
            }
            if (now()->greaterThan($salidaTeorica->copy()->addHour())) {
                return [true, $entradaActiva];
            }
        }

        return [false, null];
    }

    private function isDiaLaboralSucursal($sucursal, $diaSemana)
    {
        $diasLaboralesNorm = array_map(fn ($d) => Str::slug($d), $sucursal->dias_laborales ?? []);

        return in_array(Str::slug($diaSemana), $diasLaboralesNorm);
    }

    private function getDiaSemanaEspanol($fecha)
    {
        return $fecha->locale('es')->isoFormat('dddd');
    }

    public function distanciaEnMetros($lat1, $lon1, $lat2, $lon2)
    {
        $radioTierra = 6371000;
        $a = sin(deg2rad($lat2 - $lat1) / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin(deg2rad($lon2 - $lon1) / 2) ** 2;

        return $radioTierra * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    public function validaPermisos()
    {
        $hoy = now()->toDateString();
        $permisos = Permiso::where('id_empleado', Auth::user()->empleado->id)
            ->where('estado', 1)
            ->where(function ($q) use ($hoy) {
                $q->where(function ($q2) use ($hoy) {
                    $q2->whereNotNull('fecha_inicio')->whereNotNull('fecha_fin')->whereDate('fecha_inicio', '<=', $hoy)->whereDate('fecha_fin', '>=', $hoy);
                })->orWhere(function ($q2) use ($hoy) {
                    $q2->whereNotNull('dias_activa')->where(function ($q3) use ($hoy) {
                        $q3->whereNull('fecha_inicio')->orWhereDate('fecha_inicio', '<=', $hoy);
                    });
                });
            })->with('tipoPermiso')->get();

        $porCodigo = $permisos->keyBy(fn ($p) => $p->tipoPermiso->codigo);

        return [
            'permisos' => $porCodigo,
            'fuera_rango' => $porCodigo->get('FUERA_RANGO'),
            'llegada_tarde' => $porCodigo->get('LLEGADA_TARDE'),
            'salida_temprana' => $porCodigo->get('SALIDA_TEMPRANA'),
            'teletrabajo' => $porCodigo->get('TELETRABAJO'),
            'sin_marcacion' => $porCodigo->get('SIN_MARCACION'),
            'incapacidad' => $porCodigo->get('INCAPACIDAD'),
        ];
    }
}
