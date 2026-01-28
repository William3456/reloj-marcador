<?php

namespace App\Http\Controllers\MarcacionApp;

use App\Http\Controllers\Controller;
use App\Models\Marcacion\MarcacionEmpleado;
use App\Models\Permiso\Permiso;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Log;

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
        // Carbon::setTestNow(now()->setTime(19, 15, 0));

        // 🕒 ESCENARIO 3: Salida Temprana (3:00 PM hoy)
        // \Carbon\Carbon::setTestNow(now()->setTime(15, 0, 0));

        // 🕒 ESCENARIO 4: Mañana a las 8:00 AM
        // Carbon::setTestNow(now()->addDay()->setTime(20, 0, 0));

    }

    public function indexPanel(Request $request)
    {
        $query = MarcacionEmpleado::visiblePara(Auth::user())->with(['empleado', 'sucursal', 'salida'])
            ->where('tipo_marcacion', 1);

        // 1. Filtro por Nombre (Igual que antes)
        if ($request->has('empleado') && $request->empleado != '') {
            $query->whereHas('empleado', function ($q) use ($request) {
                $q->where('nombres', 'like', '%'.$request->empleado.'%')
                    ->orWhere('apellidos', 'like', '%'.$request->empleado.'%');
            });
        }

        // 2. NUEVO FILTRO: "En Proceso" (Sin Salida)
        if ($request->get('estado') == 'sin_cierre') {
            // Busca marcaciones que NO tengan una salida registrada
            $query->doesntHave('salida');

            // Opcional: Si quieres ver marcaciones "En proceso" históricas o solo las de hoy.
            // Si quieres ver TODAS las que quedaron abiertas alguna vez, quita el filtro de fechas abajo.
            // Si quieres ver solo las de hoy que siguen abiertas, deja el filtro de fechas.
        }

        // 3. Filtro de Fechas
        // Nota: Si presionas el botón "Turno Actual", el request ya traerá la fecha de hoy
        $desde = $request->input('desde', date('Y-m-01'));
        $hasta = $request->input('hasta', date('Y-m-d'));

        $query->whereBetween('created_at', [
            Carbon::parse($desde)->startOfDay(),
            Carbon::parse($hasta)->endOfDay(),
        ]);

        $marcaciones = $query->latest()->get();

        return view('marcaciones.index', compact('marcaciones'));
    }

    public function index()
    {
        $hoy = Carbon::today();
        $user = Auth::user();
        $empleadoId = $user->empleado->id;
        $sucursal = $user->empleado->sucursal;

        // 1. Datos visuales del día actual
        $entradaHoy = MarcacionEmpleado::where('id_empleado', $empleadoId)
            ->whereDate('created_at', $hoy)
            ->where('tipo_marcacion', 1)
            ->latest()
            ->first();

        $salidaHoy = MarcacionEmpleado::where('id_empleado', $empleadoId)
            ->whereDate('created_at', $hoy)
            ->where('tipo_marcacion', 2)
            ->latest()
            ->first();

        // --- CORRECCIÓN CRÍTICA ---
        // Si tenemos entrada y encontramos una salida creada hoy...
        if ($entradaHoy && $salidaHoy) {
            // Verificamos: ¿Esta salida pertenece a la entrada de hoy?
            // Si la salida apunta a otra entrada (ej: ID 52 del día 19), entonces NO es la salida de hoy.
            if ($salidaHoy->id_marcacion_entrada != $entradaHoy->id) {
                $salidaHoy = null; // La ignoramos visualmente para permitir marcar la salida real de hoy
            }
        }
        // ---------------------------

        $horarioRequiereSalida = $sucursal->horario->requiere_salida;

        // 2. LÓGICA DE DETECCIÓN DE OLVIDOS (CICLO ABIERTO)
        // ... (El resto de tu código sigue igual)
        $marcacionPendiente = null;
        $mostrarModalBloqueo = false;
        if ($horarioRequiereSalida == 1) {

            // Buscamos la última entrada que NO tenga salida vinculada
            $entradaAbierta = MarcacionEmpleado::where('id_empleado', $empleadoId)
                ->where('tipo_marcacion', 1)
                ->whereDoesntHave('salida')
                ->latest()
                ->first();

            if ($entradaAbierta) {
                // --- AQUÍ ESTÁ LA LÓGICA CORREGIDA ---

                // 1. Obtenemos la fecha en la que se marcó esa entrada (pudo ser hoy o ayer)
                $fechaEntrada = $entradaAbierta->created_at->format('Y-m-d');

                // 2. Obtenemos la hora de salida definida en la SUCURSAL
                // AJUSTAR CAMPO: 'hora_cierre', 'hora_salida', o como lo tengas en la BD
                $horaSalidaSucursal = $sucursal->horario->hora_fin;

                // 3. Construimos el Timestamp de cuándo debió salir la sucursal ESE día
                $momentoSalidaSucursal = Carbon::parse($fechaEntrada.' '.$horaSalidaSucursal);

                // 4. Regla: Hora Salida Sucursal + 1 Hora de tolerancia
                $limiteOlvido = $momentoSalidaSucursal->copy()->addHour();

                // 5. Comparación: ¿La hora actual ya superó ese límite?
                if (now()->greaterThan($limiteOlvido)) {
                    $marcacionPendiente = $entradaAbierta;
                    $mostrarModalBloqueo = true; // Activa el modal de "Olvido"
                }
            }
        }

        return view('app_marcacion.inicio', compact(
            'entradaHoy',
            'salidaHoy',
            'horarioRequiereSalida',
            'mostrarModalBloqueo',
            'marcacionPendiente'
        ));
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

        $validated = $request->validate([
            'latitud' => 'required|numeric|between:-90,90',
            'longitud' => 'required|numeric|between:-180,180',
            'ubicacion' => 'nullable|string|max:255',
            'tipo_marcacion' => 'required|in:1,2',
            'ubi_foto' => 'required|image|max:5120', // 5 MB

        ], [
            // --- MENSAJES PERSONALIZADOS PARA LATITUD ---
            'latitud.required' => 'Ubicación no detectada. Valide si está encendido el GPS.',
            'latitud.not_in' => 'El GPS devolvió coordenadas en 0. Valide si tiene buena señal.',
            'latitud.numeric' => 'Formato de latitud inválido.',

            // --- MENSAJES PERSONALIZADOS PARA LONGITUD ---
            'longitud.required' => 'Ubicación no detectada. Valide si está encendido el GPS.',
            'longitud.not_in' => 'El GPS devolvió coordenadas en 0. Valide si tiene buena señal.',

            // Otros mensajes opcionales para que se vea bien en el formulario
            'ubi_foto.required' => 'Debes tomar la foto de evidencia.',
            'ubi_foto.image' => 'El archivo de evidencia debe ser una imagen.',
            'ubi_foto.max' => 'La foto es demasiado pesada (Máx 5MB).',
        ]);

        // =========================
        // DATOS BASE
        // =========================
        $empleado = Auth::user()->empleado;
        $sucursal = $empleado->sucursal;
        $fueraHorario = null;

        // Variable bandera para detectar olvido
        $esOlvidoSalida = false;

        // =========================
        // VALIDAR PERMISOS
        // =========================
        $permisos = $this->validaPermisos();
        $permisoActivo = collect($permisos)->except('permisos')->filter()->first();
        $permisoExime = collect([$permisos['sin_marcacion'], $permisos['incapacidad']])->filter()->first();

        if ($permisoExime) {
            return back()->withErrors([
                'error' => 'Tienes un permiso activo que exime la marcación hasta: '.
                            Carbon::parse($permisoExime->fecha_fin)->locale('es')->translatedFormat('l d \\d\\e F \\d\\e Y'),
            ]);
        }

        // =========================
        // BUSCAR ENTRADA PENDIENTE
        // =========================
        $entradaAbierta = MarcacionEmpleado::where('id_empleado', $empleado->id)
            ->where('tipo_marcacion', 1)
            ->whereDoesntHave('salida')
            ->latest()
            ->first();

        // =========================
        // VALIDACIÓN DE HORARIOS (ENTRADA Y SALIDA)
        // =========================
        if ($validated['tipo_marcacion'] == 1) {
            // --- LOGICA ENTRADA ---

            // 1. Convertimos la hora fin de la BD en un objeto Carbon con fecha de HOY
            $horaFinTurno = Carbon::parse($empleado->sucursal->horario->hora_fin);

            // 2. Comparamos: Si "ahora" es MENOR o IGUAL a la hora fin, permitimos marcar
            if (now()->lessThanOrEqualTo($horaFinTurno)) {

                // --- CÓDIGO DE LLEGADA TARDE (Tu lógica original) ---
                $horaEntrada = $sucursal->horario->hora_ini;
                $tolerancia = $sucursal->horario->tolerancia;

                if ($permisos['llegada_tarde']) {
                    $tolerancia += $permisos['llegada_tarde']->valor;
                }

                // Convertimos hora entrada a Carbon para sumar minutos fácilmente
                $horaMaxima = Carbon::parse($horaEntrada)->addMinutes($tolerancia);
                // dd($permisos);
                if (now()->greaterThan($horaMaxima)) {
                    $fueraHorario = 1; // Llegada tarde
                }
                // ----------------------------------------------------

            } else {
                // Si "ahora" es MAYOR a la hora fin:
                return back()->withErrors([
                    'error' => 'No puedes marcar entrada, la jornada laboral de hoy ya finalizó.',
                ]);
            }
        } else {
            // --- LOGICA SALIDA ---
            if ($entradaAbierta) {
                $horaSalida = $sucursal->horario->hora_fin;
                $horaActual = now()->format('H:i:s');

                // 1. VERIFICAMOS SI ES DE OTRO DÍA
                // Si la entrada NO fue creada hoy, asumimos automáticamente que es un cierre pendiente (olvido).
                $esDiaDistinto = ! $entradaAbierta->created_at->isToday();

                // 2. DEFINIR LÍMITE (Hora salida + 1 hora) para el mismo día
                $horaLimiteNormal = Carbon::parse($horaSalida)->addHour()->format('H:i:s');

                // CONDICIÓN CORREGIDA:
                // Es olvido si: Es otro día O BIEN (es el mismo día Y ya pasó la hora límite)
                if ($esDiaDistinto || $horaActual > $horaLimiteNormal) {

                    // >>> ES UN OLVIDO DE SALIDA <<<
                    $esOlvidoSalida = true;
                    $fueraHorario = 1;
                    // Al ser olvido (o día distinto), NO validamos hora mínima.

                } else {
                    // >>> ES UNA SALIDA NORMAL (Mismo día y dentro del rango/temprano) <<<
                    $fueraHorario = null;

                    $horaMinimaSalida = $horaSalida;

                    if ($permisos['salida_temprana']) {
                        // Usamos la fecha de HOY para el cálculo correcto de la resta de minutos
                        $horaMinimaSalida = Carbon::parse(now()->format('Y-m-d').' '.$horaSalida)
                            ->subMinutes($permisos['salida_temprana']->valor)
                            ->format('H:i:s');
                    }

                    // Aquí la comparación de strings funciona bien porque ya sabemos que estamos en el MISMO día
                    if ($horaActual < $horaMinimaSalida) {
                        return back()->withErrors([
                            'error' => "No puedes marcar salida antes de la hora mínima ($horaMinimaSalida).",
                        ]);
                    }
                }
            }
        }

        // =========================
        // VALIDACIÓN GPS
        // =========================
        $rango = $sucursal->rango_marcacion_mts;
        $margenError = $sucursal->margen_error_gps_mts;

        // Ajuste de rango por permisos (Solo aplica si no es olvido, o según tu lógica de negocio)
        if ($permisos['fuera_rango']) {
            if ($permisos['fuera_rango']->cantidad_mts !== null) {
                $rango = $permisos['fuera_rango']->cantidad_mts;
            } else {
                $rango = PHP_INT_MAX; // Exonerado
            }
        }

        $distanciaReal = $this->distanciaEnMetros(
            $validated['latitud'], $validated['longitud'],
            $sucursal->latitud, $sucursal->longitud
        );

        // >>> LOGICA CORREGIDA DE VALIDACION <<<
        // Validamos GPS si:
        // 1. Es Entrada (Siempre valida)
        // 2. O Es Salida PERO NO es olvido (Está en horario laboral + 1 hora)
        $validarGPS = true;

        if ($validated['tipo_marcacion'] == 2 && $esOlvidoSalida) {
            $validarGPS = false; // Apagamos validación si se le olvidó marcar
        }

        if ($validarGPS) {
            if ($distanciaReal > ($rango + $margenError)) {
                return back()->withErrors([
                    'error' => "Estás fuera del rango permitido ($distanciaReal m).",
                ]);
            }
        }

        // =========================
        // GUARDAR MARCACIÓN
        // =========================
        $marcacion = MarcacionEmpleado::create([
            'id_empleado' => $empleado->id,
            'id_sucursal' => $sucursal->id,
            'latitud' => $validated['latitud'],
            'longitud' => $validated['longitud'],
            'distancia_real_mts' => $distanciaReal,
            'ubicacion' => null,
            'tipo_marcacion' => $validated['tipo_marcacion'],
            'ubi_foto' => null,
            'id_permiso_aplicado' => $permisoActivo?->id,
            'fuera_horario' => $fueraHorario,
            'id_marcacion_entrada' => $validated['tipo_marcacion'] == 2 ? $entradaAbierta?->id : null,
        ]);


        // =========================
        // GUARDAR FOTO (ROBUSTO)
        // =========================
        $file = $request->file('ubi_foto');

        // Nombres de archivo
        $tipoTexto = $validated['tipo_marcacion'] == 1 ? 'entrada' : 'salida';
        $fechaHora = now()->format('YmdHis');
        $nombreArchivo = "{$empleado->cod_trabajador}_{$marcacion->id}_{$tipoTexto}_{$fechaHora}.jpg";

        try {
            // 2. Instanciar Manager (Si falla aquí, es que no activaste php_gd en WAMP)
            $manager = new ImageManager(new Driver());

            // 3. Leer imagen
            $image = $manager->read($file);

            // --- PROCESO A: MINIATURA (400px) ---
            // scaleDown: Si es menor a 400, no la toca. Si es mayor, la reduce.
            $encodedMini = $image->scaleDown(width: 400)->toJpeg(quality: 60);

            // --- PROCESO B: FULL (1280px) ---
            // Leemos de nuevo el archivo original para procesar la versión grande limpia
            $imageFull = $manager->read($file);
            $encodedFull = $imageFull->scaleDown(width: 1280)->toJpeg(quality: 85);

            // 4. Guardar en Storage
            $rutaMini = "marcaciones_empleados/{$nombreArchivo}";
            $rutaFull = "marcaciones_empleados/full/{$nombreArchivo}";

            Storage::disk('public')->put($rutaMini, (string) $encodedMini);
            Storage::disk('public')->put($rutaFull, (string) $encodedFull);

            // 5. Actualizar BD
            $marcacion->update([
                'ubi_foto'      => $rutaMini,
                'ubi_foto_full' => $rutaFull
            ]);

            //return response()->json(['message' => 'Guardado correctamente']);

        } catch (\Exception $e) {
            Log::error("Error subiendo foto: " . $e->getMessage());
            
            
            return response()->json([
                'error' => 'Ocurrió un error al procesar la imagen.',
                'detalle' => $e->getMessage() 
            ], 500);
        }

        // Mensaje personalizado
        if ($esOlvidoSalida) {
            $msj = 'Salida registrada (Regularización por olvido).';
        } else {
            $msj = $validated['tipo_marcacion'] == 1
                ? ($fueraHorario ? 'Entrada registrada (Llegada tardía).' : 'Entrada registrada correctamente.')
                : 'Salida registrada correctamente.';
        }

        return back()->with('success', $msj);
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
