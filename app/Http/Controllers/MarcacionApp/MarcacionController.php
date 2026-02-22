<?php

namespace App\Http\Controllers\MarcacionApp;

use App\Http\Controllers\Controller;
use App\Models\Empleado\Empleado;
use App\Models\Horario\horario;
use App\Models\Horario\HorarioHistorico;
use App\Models\Marcacion\MarcacionEmpleado;
use App\Models\Permiso\Permiso;
use App\Models\Sucursales\Sucursal;
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
    /**
     * Display a listing of the resource.
     */
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
        // Carbon::setTestNow(now()->setTime( 8, 0, 0));

        // 🕒 ESCENARIO 3: Salida Temprana (3:00 PM hoy)
        // \Carbon\Carbon::setTestNow(now()->setTime(15, 0, 0));

        // 🕒 ESCENARIO 4: Mañana a las 8:00 AM //martes = 10
        // Carbon::setTestNow(now()->addDay(3)->setTime( 13,0, 0));

    }

    public function indexPanel(Request $request)
    {
        // 1. Obtener datos auxiliares para los filtros (Selects)
        $sucursales = Sucursal::visiblePara(Auth::user())
            ->where('estado', 1)
            ->get();

        $empleadosList = Empleado::where('estado', 1)
            ->orderBy('nombres')
            ->get();

        // 2. Construir la consulta principal
        $query = MarcacionEmpleado::visiblePara(Auth::user())
            ->with([
                'empleado',
                'sucursal',
                'salida',
                'permiso.tipoPermiso',
                'salida.permiso.tipoPermiso',
            ])
            ->where('tipo_marcacion', 1);

        // ... (Tus filtros existentes: Empleado, Estado, Fechas) ...
        if ($request->has('empleado') && $request->empleado != '') {
            $query->whereHas('empleado', function ($q) use ($request) {
                $q->where('nombres', 'like', '%'.$request->empleado.'%')
                    ->orWhere('apellidos', 'like', '%'.$request->empleado.'%');
            });
        }

        // Filtro por Sucursal (¡Faltaba agregarlo a la query!)
        if ($request->filled('sucursal')) {
            $query->where('id_sucursal', $request->sucursal);
        }

        if ($request->get('estado') == 'sin_cierre') {
            $query->doesntHave('salida');
        }

        $desde = $request->input('desde', date('Y-m-d')); // Cambiado a date('Y-m-d') para que por defecto sea hoy, no inicio de mes
        $hasta = $request->input('hasta', date('Y-m-d'));

        $query->whereBetween('created_at', [
            Carbon::parse($desde)->startOfDay(),
            Carbon::parse($hasta)->endOfDay(),
        ]);

        $marcaciones = $query->latest()->get();

        // 3. Retornar vista con TODAS las variables
        return view('marcaciones.index', compact('marcaciones', 'sucursales', 'empleadosList'));
    }

    public function index()
    {
        $hoy = Carbon::today();
        $user = Auth::user();
        $empleado = $user->empleado;
        $sucursal = $empleado->sucursal;
        $diaSemanaHoy = Carbon::now()->locale('es')->isoFormat('dddd');
        // ---------------------------------------------------------
        // 0. VERIFICAR PERMISOS (NUEVO)
        // ---------------------------------------------------------
        $permisos = $this->validaPermisos(); // Reutilizamos tu función
        $permisoActivo = null;

        // Buscamos si hay algún permiso que EXIMA de marcar
        // (Ajusta las claves según lo que devuelve tu función validaPermisos)
        if ($permisos['sin_marcacion']) {
            $permisoActivo = $permisos['sin_marcacion']; // Objeto Permiso
        } elseif ($permisos['incapacidad']) {
            $permisoActivo = $permisos['incapacidad'];
        }
        $historialHoy = collect();
        // Si hay permiso activo, no necesitamos calcular nada más complejo
        // dd();
        if ($permisoActivo) {
            return view('app_marcacion.inicio', compact('permisoActivo', 'historialHoy'));
        }

        // =========================================================
        // 1. OBTENER HISTORIAL DE HOY
        // =========================================================
        $historialHoy = MarcacionEmpleado::where('id_empleado', $empleado->id)
            ->whereDate('created_at', $hoy)
            ->orderBy('created_at', 'desc')
            ->get();

        $ultimoRegistro = MarcacionEmpleado::where('id_empleado', $empleado->id)
            ->latest()->first();

        // =========================================================
        // 2. DETERMINAR ESTADO ACTUAL
        // =========================================================
        $entradaActiva = null;
        if ($ultimoRegistro && $ultimoRegistro->tipo_marcacion == 1) {
            $entradaActiva = $ultimoRegistro;
        }

        // =========================================================
        // 3. BUSCAR Y FILTRAR HORARIOS
        // =========================================================
        $normalizarDia = function ($dia) {
            // 1. Convertir a limpio (miércoles -> miercoles)
            $limpio = Str::slug($dia); // Str::slug es excelente para esto

            // 2. Obtener las primeras 3 letras (mie)
            return substr($limpio, 0, 3);
        };

        $hoyNorm = $normalizarDia($diaSemanaHoy);

        // A. Horarios del empleado
        $candidatosRaw = $empleado->horarios->filter(function ($h) use ($hoyNorm, $normalizarDia) {
            $diasHorario = array_map($normalizarDia, $h->dias ?? []);

            return in_array($hoyNorm, $diasHorario);
        });

        // B. Backup Sucursal
        if ($candidatosRaw->isEmpty() && $empleado->horarios->isEmpty()) {
            $candidatosRaw = $sucursal->horarios->filter(function ($h) use ($hoyNorm, $normalizarDia) {
                $diasHorario = array_map($normalizarDia, $h->dias ?? []);

                return in_array($hoyNorm, $diasHorario);
            });
        }

        // C. Filtro Maestro (Cruce con Horario Sucursal)
        $horariosSucursalHoy = $sucursal->horarios->filter(function ($h) use ($hoyNorm, $normalizarDia) {
            $dias = array_map($normalizarDia, $h->dias ?? []);

            return in_array($hoyNorm, $dias);
        });

        $candidatos = $candidatosRaw->filter(function ($horarioEmpleado) use ($horariosSucursalHoy, $hoy) {
            if ($horariosSucursalHoy->isEmpty()) {
                return false;
            }

            $iniEmp = Carbon::parse($hoy->format('Y-m-d').' '.$horarioEmpleado->hora_ini);
            $finEmp = Carbon::parse($hoy->format('Y-m-d').' '.$horarioEmpleado->hora_fin);
            if ($finEmp->lessThan($iniEmp)) {
                $finEmp->addDay();
            }

            foreach ($horariosSucursalHoy as $hs) {
                $iniSuc = Carbon::parse($hoy->format('Y-m-d').' '.$hs->hora_ini);
                $finSuc = Carbon::parse($hoy->format('Y-m-d').' '.$hs->hora_fin);
                if ($finSuc->lessThan($iniSuc)) {
                    $finSuc->addDay();
                }

                if ($iniEmp->greaterThanOrEqualTo($iniSuc->copy()->subMinutes(30)) &&
                    $finEmp->lessThanOrEqualTo($finSuc->copy()->addMinutes(15))) {
                    return true;
                }
            }

            return false;
        });

        $horarioActivo = ($entradaActiva && $entradaActiva->id_horario) ? $entradaActiva->horario : null;

        // =========================================================
        // 4. VALIDACIÓN DE BLOQUEO
        // =========================================================
        $horarioRequiereSalida = $sucursal->horarios()->where('requiere_salida', 1)->exists() ? 1 : 0;
        $marcacionPendiente = null;
        $mostrarModalBloqueo = false;

        if ($horarioRequiereSalida == 1 && $entradaActiva && $horarioActivo) {
            $fechaEntrada = $entradaActiva->created_at->format('Y-m-d');
            $salidaTeorica = Carbon::parse($fechaEntrada.' '.$horarioActivo->hora_fin);
            if (Carbon::parse($horarioActivo->hora_fin)->lessThan(Carbon::parse($horarioActivo->hora_ini))) {
                $salidaTeorica->addDay();
            }
            if (now()->greaterThan($salidaTeorica->copy()->addHour())) {
                $marcacionPendiente = $entradaActiva;
                $mostrarModalBloqueo = true;
            }
        }

        // =========================================================
        // 5. CÁLCULO DE PRÓXIMO TURNO Y ESTADO DE ENTRADA
        // =========================================================
        $tiempoRestante = null;
        $proximoHorario = null;
        $habilitarEntrada = false;
        $jornadaTerminada = false;

        if (! $entradaActiva) {
            $ahora = now();
            $turnoVigente = null;
            $siguienteTurno = null;
            $minutosParaSiguiente = PHP_INT_MAX;

            // Ordenar para evaluar cronológicamente
            $candidatos = $candidatos->sortBy('hora_ini');

            foreach ($candidatos as $h) {
                $inicio = Carbon::parse($hoy->format('Y-m-d').' '.$h->hora_ini);
                $fin = Carbon::parse($hoy->format('Y-m-d').' '.$h->hora_fin);

                if ($fin->lessThan($inicio)) {
                    $fin->addDay();
                }

                $inicioHabilitado = $inicio->copy()->subMinutes(60); // Permitir marcar hasta 60 minutos antes del inicio

                // 1. ¿Turno VIGENTE? (Dentro del rango)
                if ($ahora->between($inicioHabilitado, $fin)) {
                    $yaCompletado =
                        // Debe existir una entrada hoy...
                    $historialHoy->contains(function ($m) use ($h) {
                        return $m->tipo_marcacion == 1 && $m->id_horario == $h->id;
                    })
                    &&
                    // ...Y una salida hoy
                    $historialHoy->contains(function ($m) use ($h) {
                        return $m->tipo_marcacion == 2 && $m->id_horario == $h->id;
                    });

                    if (! $yaCompletado) {
                        $turnoVigente = $h;
                        break;
                    }
                }

                // 2. ¿Turno FUTURO?
                if (! $turnoVigente && $inicioHabilitado->greaterThan($ahora)) {
                    $yaCompletado = $historialHoy->contains(function ($m) use ($h) {
                        return $m->tipo_marcacion == 2 && $m->id_horario == $h->id;
                    });

                    if (! $yaCompletado) {
                        $diff = $ahora->diffInMinutes($inicio);
                        if ($diff < $minutosParaSiguiente) {
                            $minutosParaSiguiente = $diff;
                            $siguienteTurno = $inicio;
                        }
                    }
                }
            }

            // --- TOMA DE DECISIONES VISUALES ---

            if ($turnoVigente) {
                $habilitarEntrada = true;
                $tiempoRestante = null;
            } elseif ($siguienteTurno) {
                $habilitarEntrada = false;
                $proximoHorario = $siguienteTurno;
                $tiempoRestante = $proximoHorario->locale('es')->diffForHumans($ahora, [
                    'parts' => 2, 'join' => true, 'syntax' => Carbon::DIFF_ABSOLUTE,
                ]);
            } else {
                // CASO: NO HAY MÁS TURNOS (Ni vigentes ni futuros)

                // Si hay candidatos (hubo turnos hoy) O hay historial -> Jornada Terminada
                if ($candidatos->isNotEmpty() || $historialHoy->isNotEmpty()) {
                    $jornadaTerminada = true;
                    $habilitarEntrada = false;
                } else {
                    // Día libre
                    $habilitarEntrada = false;
                }
            }
        }

        return view('app_marcacion.inicio', compact(
            'entradaActiva', 'horarioRequiereSalida', 'mostrarModalBloqueo',
            'marcacionPendiente', 'tiempoRestante', 'proximoHorario',
            'habilitarEntrada', 'historialHoy', 'jornadaTerminada', 'candidatos', 'permisoActivo'
        ));
    }

    public function store(Request $request)
    {
        // 1. Validar Request
        $validated = $this->validateRequest($request);

        $empleado = Auth::user()->empleado;
        $sucursal = $empleado->sucursal;
        $fechaReferencia = now();
        $entradaAbierta = null;
        if ($request->tipo_marcacion == 1) {
            if (! $this->isSucursalAbierta($sucursal, now())) {
                return back()->withErrors(['error' => 'La sucursal se encuentra cerrada en este horario.']);
            }
        }
        // 2. Buscar Entrada Previa (si es Salida)
        if ($validated['tipo_marcacion'] == 2) {
            $entradaAbierta = MarcacionEmpleado::where('id_empleado', $empleado->id)
                ->where('tipo_marcacion', 1)
                ->whereDoesntHave('salida')
                ->latest()
                ->first();

            if ($entradaAbierta) {
                $fechaReferencia = $entradaAbierta->created_at;
            }
        }

        // 3. Validar si la Sucursal trabaja hoy

        $diaSemana = $this->getDiaSemanaEspanol($fechaReferencia);

        if (! $this->isDiaLaboralSucursal($sucursal, $diaSemana)) {
            return back()->withErrors(['error' => "La sucursal no labora los días $diaSemana."]);
        }

        // 4. Obtener el Horario Correcto (Aquí usamos el ID nuevo)
        $horarioHoy = $this->determinarHorario($empleado, $sucursal, $diaSemana, $validated['tipo_marcacion'], $entradaAbierta);

        if (! $horarioHoy) {
            return back()->withErrors(['error' => "No se encontró un horario asignado para el $diaSemana."]);
        }

        // 5. Validar Tiempos (Entrada/Salida, Nocturnos, Tolerancias)
        $validacionTiempo = $this->validarTiemposTurno($horarioHoy, $fechaReferencia, $validated['tipo_marcacion'], $entradaAbierta);

        if (isset($validacionTiempo['error'])) {
            return back()->withErrors(['error' => $validacionTiempo['error']]);
        }

        // 6. Validar GPS
        $validacionGPS = $this->validarGPS($validated, $sucursal, $validacionTiempo['es_olvido']);
        if (isset($validacionGPS['error'])) {
            return back()->withErrors(['error' => $validacionGPS['error']]);
        }

        // 7. Guardar en BD
        $marcacion = $this->guardarRegistro($validated, $empleado, $sucursal, $horarioHoy, $validacionTiempo, $validacionGPS['distancia'], $entradaAbierta, $diaSemana);

        // 8. Procesar Foto
        $this->procesarImagen($request->file('ubi_foto'), $marcacion, $empleado, $validated['tipo_marcacion']);

        $msj = $validacionTiempo['es_olvido'] ? 'Salida registrada (Regularización).' : ($validated['tipo_marcacion'] == 1 ? 'Entrada registrada.' : 'Salida registrada.');

        return back()->with('success', $msj);
    }

    /**
     * Verifica si la hora dada cae dentro del horario operativo de la sucursal
     * con un margen de tolerancia (ej: permitir marcar 30 min antes de abrir y 60 después de cerrar).
     */
    private function isSucursalAbierta($sucursal, Carbon $fechaHora)
    {
        $diaSemana = $this->getDiaSemanaEspanol($fechaHora);

        // CORRECCIÓN: Usar Str::slug para estandarizar (quita tildes y mayúsculas)
        $normalizar = function ($s) {
            return Str::slug($s);
        };
        $diaNormalizado = $normalizar($diaSemana);

        // 2. Obtener horarios de la sucursal para HOY
        $horariosSucursal = $sucursal->horarios->filter(function ($h) use ($diaNormalizado, $normalizar) {
            // Aplicamos slug a los días que vienen de la BD para comparar peras con peras
            $dias = array_map($normalizar, $h->dias ?? []);

            return in_array($diaNormalizado, $dias);
        });

        if ($horariosSucursal->isEmpty()) {
            return false;
        }

        // 3. Verificar si la hora encaja en algún rango
        foreach ($horariosSucursal as $hs) {
            $inicio = Carbon::parse($fechaHora->format('Y-m-d').' '.$hs->hora_ini);
            $fin = Carbon::parse($fechaHora->format('Y-m-d').' '.$hs->hora_fin);

            if ($fin->lessThan($inicio)) {
                $fin->addDay();
            }

            if ($fechaHora->between($inicio->copy()->subMinutes(30), $fin->copy()->addHour())) {
                return true;
            }
        }

        return false;
    }

    private function getDiaSemanaEspanol($fecha)
    {
        // Esto garantiza que siempre salga 'miércoles' en UTF-8 válido,
        // sin importar si el servidor es Linux, Windows o si el archivo está en ANSI.
        return $fecha->locale('es')->isoFormat('dddd');
    }

    /**
     * MÉTODO PRINCIPAL: ORQUESTADOR
     */

    // -------------------------------------------------------------------------
    // FUNCIONES PRIVADAS (LOGICA SECCIONADA)
    // -------------------------------------------------------------------------

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

    private function isDiaLaboralSucursal($sucursal, $diaSemana)
    {
        $diasLaboralesSucursal = $sucursal->dias_laborales ?? [];

        // Normalizamos la lista de la BD
        $diasLaboralesNorm = array_map(function ($d) {
            return Str::slug($d);
        }, $diasLaboralesSucursal);

        // Comparamos usando slug en ambos lados
        return in_array(Str::slug($diaSemana), $diasLaboralesNorm);
    }

    private function determinarHorario($empleado, $sucursal, $diaSemana, $tipoMarcacion, $entradaAbierta)
    {
        // ---------------------------------------------------------
        // ESCENARIO 1: SALIDA (USAR ID GUARDADO) - ¡INFALIBLE!
        // ---------------------------------------------------------
        if ($tipoMarcacion == 2 && $entradaAbierta) {
            // Si la entrada ya tiene el ID guardado, lo usamos directo.
            if ($entradaAbierta->id_horario) {
                return horario::find($entradaAbierta->id_horario);
            }

            // FALLBACK PARA REGISTROS VIEJOS (SIN ID)
            // Buscamos el horario cuyo inicio coincida mejor con la hora de entrada registrada
            $candidatos = $empleado->horarios()->whereJsonContains('dias', $diaSemana)->get();
            if ($candidatos->isEmpty()) {
                $candidatos = $sucursal->horarios()->whereJsonContains('dias', $diaSemana)->get();
            }

            $horaEntrada = Carbon::parse($entradaAbierta->created_at->format('H:i:s'));
            foreach ($candidatos as $h) {
                $inicio = Carbon::parse($h->hora_ini);
                // Si la diferencia es menor a 4 horas, asumimos que es este
                if ($horaEntrada->diffInMinutes($inicio) < 240) {
                    return $h;
                }
            }

            return $candidatos->first();
        }

        // ---------------------------------------------------------
        // ESCENARIO 2: ENTRADA (SELECCIÓN INTELIGENTE)
        // ---------------------------------------------------------

        // 1. Obtener candidatos (Prioridad Empleado -> Sucursal)
        $candidatos = $empleado->horarios()->whereJsonContains('dias', $diaSemana)->get();
        if ($candidatos->isEmpty()) {
            $candidatos = $sucursal->horarios()->whereJsonContains('dias', $diaSemana)->get();
        }

        if ($candidatos->isEmpty()) {
            return null;
        }

        $ahora = now();
        $mejorCandidato = null;
        $minutosDiferencia = PHP_INT_MAX;

        // PASO A: Buscar si "CAIGO DENTRO" de algún turno activo (Prioridad Alta)
        // Esto arregla el bug: Si son las 15:50 (Turno 14:00-16:40), caerá aquí en vez de irse al de las 17:00
        foreach ($candidatos as $h) {
            $inicio = Carbon::parse($ahora->format('Y-m-d').' '.$h->hora_ini);
            $fin = Carbon::parse($ahora->format('Y-m-d').' '.$h->hora_fin);

            if ($fin->lessThan($inicio)) {
                $fin->addDay();
            } // Ajuste nocturno

            // Si la hora actual está DENTRO del intervalo (con 1 hora de margen post-cierre)
            if ($ahora->greaterThanOrEqualTo($inicio->copy()->subMinutes(60)) && $ahora->lessThanOrEqualTo($fin)) {
                return $h; // ¡Encontrado! Retorno inmediato.
            }
        }

        // PASO B: Si no estoy dentro de ninguno (Llegada Temprano), buscar el MÁS CERCANO en el futuro
        foreach ($candidatos as $h) {
            $inicio = Carbon::parse($ahora->format('Y-m-d').' '.$h->hora_ini);

            // Calculamos distancia absoluta
            $diff = abs($ahora->diffInMinutes($inicio, false));

            // Solo consideramos turnos que no hayan pasado hace mucho (> 8 horas)
            if ($ahora->diffInHours($inicio, false) < -8) {
                continue;
            }

            if ($diff < $minutosDiferencia) {
                $minutosDiferencia = $diff;
                $mejorCandidato = $h;
            }
        }

        return $mejorCandidato ?? $candidatos->last();
    }

    private function validarTiemposTurno($horario, $fechaReferencia, $tipoMarcacion, $entradaAbierta)
    {
        $permisos = $this->validaPermisos(); // Tu función existente

        // Verificar permiso eximente
        $permisoExime = collect([$permisos['sin_marcacion'], $permisos['incapacidad']])->filter()->first();
        if ($permisoExime) {
            return ['error' => 'Permiso activo exime marcación.'];
        }

        // Construir tiempos
        $fechaBase = $fechaReferencia->format('Y-m-d');
        $inicioTurno = Carbon::parse($fechaBase.' '.$horario->hora_ini);
        $finTurno = Carbon::parse($fechaBase.' '.$horario->hora_fin);

        // Ajuste Nocturno
        if ($finTurno->lessThanOrEqualTo($inicioTurno)) {
            $finTurno->addDay();
        }

        $resultado = [
            'fuera_horario' => null,
            'es_olvido' => false,
            'permiso_aplicado' => collect($permisos)->except('permisos')->filter()->first()?->id,
        ];

        if ($tipoMarcacion == 1) {
            // --- LÓGICA ENTRADA ---
            if (now()->lessThanOrEqualTo($finTurno)) {
                $tolerancia = $horario->tolerancia;
                if ($permisos['llegada_tarde']) {
                    $tolerancia += $permisos['llegada_tarde']->valor;
                }

                $horaMaxima = $inicioTurno->copy()->addMinutes($tolerancia);
                if (now()->greaterThan($horaMaxima)) {
                    $resultado['fuera_horario'] = 1;
                }
            } else {
                return ['error' => 'Tu jornada para este turno ya finalizó.'];
            }
        } else {
            // --- LÓGICA SALIDA ---
            if ($entradaAbierta) {
                $limiteOlvido = $finTurno->copy()->addHour();
                $momentoMinimo = $finTurno->copy();
                if ($permisos['salida_temprana']) {
                    $momentoMinimo->subMinutes($permisos['salida_temprana']->valor);
                }

                if (now()->greaterThan($limiteOlvido)) {
                    $resultado['es_olvido'] = true;
                    $resultado['fuera_horario'] = 1;
                } else {
                    if (now()->lessThan($momentoMinimo)) {
                        return ['error' => 'Salida no permitida antes de las '.$momentoMinimo->format('d/m H:i')];
                    }
                }
            }
        }

        return $resultado;
    }

    private function validarGPS($validated, $sucursal, $esOlvido)
    {
        // Regla: Si es salida por olvido, NO validamos
        if ($validated['tipo_marcacion'] == 2 && $esOlvido) {
            return ['distancia' => 0]; // Distancia irrelevante
        }

        // Regla de Sucursal
        $sucursalExigeSalida = $sucursal->horarios()->where('requiere_salida', 1)->exists();

        // Opcional: Si quieres ser estricto y la sucursal NO exige salida, podrías saltar validación en salida.
        // Pero generalmente el GPS siempre se valida si estás marcando.

        $permisos = $this->validaPermisos();

        $rango = $sucursal->rango_marcacion_mts;
        if ($permisos['fuera_rango']) {
            if ($permisos['fuera_rango']->cantidad_mts == null) {
                $rango = PHP_INT_MAX;
            } else {
                $rango += $permisos['fuera_rango']->cantidad_mts;
            }
        }

        $distancia = $this->distanciaEnMetros(
            $validated['latitud'],
            $validated['longitud'],
            $sucursal->latitud,
            $sucursal->longitud
        );

        if ($distancia > ($rango + $sucursal->margen_error_gps_mts)) {
            return ['error' => "Estás fuera del rango permitido ($distancia mts)."];
        }

        return ['distancia' => $distancia];
    }

    private function guardarRegistro($validated, $empleado, $sucursal, $horario, $validacionTiempo, $distancia, $entradaAbierta, $diaSemana)
    {
        return DB::transaction(function () use (
            $diaSemana, $validated, $empleado, $sucursal, $horario, $validacionTiempo, $distancia, $entradaAbierta) {
            $horarioEmpleado = $horario;
            $horarioSucursal = null;

            foreach ($sucursal->horarios as $h) {
                if ($h->permitido_marcacion == 1 && in_array($diaSemana, $h->dias)) {
                    $horarioSucursal = $h;
                    break;
                }
            }

            $historicoEmpleado = HorarioHistorico::mismoHorario($horarioEmpleado)
                ->vigente()->first();

            if ($historicoEmpleado == null) {
                HorarioHistorico::where('id_horario', $horarioEmpleado->id)
                    ->where('tipo_horario', $horarioEmpleado->permitido_marcacion)
                    ->vigente()
                    ->update(['vigente_hasta' => now()]);

                $historicoEmpleado = HorarioHistorico::create([
                    'id_horario' => $horarioEmpleado->id,
                    'tipo_horario' => $horarioEmpleado->permitido_marcacion,
                    'hora_entrada' => $horarioEmpleado->hora_ini,
                    'hora_salida' => $horarioEmpleado->hora_fin,
                    'tolerancia' => $horarioEmpleado->tolerancia,
                    'dias' => $horarioEmpleado->dias,
                    'vigente_desde' => now(),
                ]);
            }
            $historicoSucursal = null;

            if ($horarioSucursal) {
                $historicoSucursal = HorarioHistorico::mismoHorario($horarioSucursal)
                    ->vigente()->first();

                if ($historicoSucursal == null) {

                    HorarioHistorico::where('id_horario', $horarioSucursal->id)
                        ->where('tipo_horario', $horarioSucursal->permitido_marcacion)
                        ->vigente()
                        ->update(['vigente_hasta' => now()]);

                    $historicoSucursal = HorarioHistorico::create([
                        'id_horario' => $horarioSucursal->id,
                        'tipo_horario' => $horarioSucursal->permitido_marcacion,
                        'hora_entrada' => $horarioSucursal->hora_ini,
                        'hora_salida' => $horarioSucursal->hora_fin,
                        'tolerancia' => $horarioSucursal->tolerancia,
                        'dias' => $horarioSucursal->dias,
                        'vigente_desde' => now(),
                    ]);
                }
            }

            // Guardar marcación (mínimo cambio)
            return MarcacionEmpleado::create([
                'id_empleado' => $empleado->id,
                'id_sucursal' => $sucursal->id,
                'id_horario' => $horario->id,
                'id_horario_historico_empleado' => $historicoEmpleado->id,
                'id_horario_historico_sucursal' => $historicoSucursal?->id,
                'latitud' => $validated['latitud'],
                'longitud' => $validated['longitud'],
                'distancia_real_mts' => $distancia,
                'tipo_marcacion' => $validated['tipo_marcacion'],
                'id_permiso_aplicado' => $validacionTiempo['permiso_aplicado'],
                'fuera_horario' => $validacionTiempo['fuera_horario'],
                'id_marcacion_entrada' => $validated['tipo_marcacion'] == 2
                    ? $entradaAbierta?->id
                    : null,
            ]);
        });
    }

    private function procesarImagen($file, $marcacion, $empleado, $tipo)
    {
        try {
            $tipoTexto = $tipo == 1 ? 'entrada' : 'salida';
            $nombre = "{$empleado->cod_trabajador}_{$marcacion->id}_{$tipoTexto}_".now()->format('YmdHis').'.jpg';

            $manager = new ImageManager(new Driver);

            // Miniatura
            $imgMini = $manager->read($file)->scaleDown(width: 400)->toJpeg(60);
            // Full
            $imgFull = $manager->read($file)->scaleDown(width: 1280)->toJpeg(85);

            $rutaMini = "marcaciones_empleados/$nombre";
            $rutaFull = "marcaciones_empleados/full/$nombre";

            Storage::disk('public')->put($rutaMini, (string) $imgMini);
            Storage::disk('public')->put($rutaFull, (string) $imgFull);

            $marcacion->update([
                'ubi_foto' => $rutaMini,
                'ubi_foto_full' => $rutaFull,
            ]);
        } catch (\Exception $e) {
            Log::error("Error procesando foto marcación ID {$marcacion->id}: ".$e->getMessage());
        }
    }

    private function guardaHistoricoHorarioEmpleado($empleado, $sucursal, $horario, $diaSemana) {}

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    public function distanciaEnMetros($lat1, $lon1, $lat2, $lon2)
    {
        $radioTierra = 6371000; // metros

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $radioTierra * $c;
    }

    public function validaPermisos()
    {
        $hoy = now()->toDateString();

        $permisos = Permiso::where('id_empleado', Auth::user()->empleado->id)
            ->where('estado', 1)
            ->where(function ($q) use ($hoy) {

                $q->where(function ($q2) use ($hoy) {
                    $q2->whereNotNull('fecha_inicio')
                        ->whereNotNull('fecha_fin')
                        ->whereDate('fecha_inicio', '<=', $hoy)
                        ->whereDate('fecha_fin', '>=', $hoy);
                })
                    ->orWhere(function ($q2) use ($hoy) {
                        $q2->whereNotNull('dias_activa')
                            ->where(function ($q3) use ($hoy) {
                                $q3->whereNull('fecha_inicio')
                                    ->orWhereDate('fecha_inicio', '<=', $hoy);
                            });
                    });

            })
            ->with('tipoPermiso')
            ->get();

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
