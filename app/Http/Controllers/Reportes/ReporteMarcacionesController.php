<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use App\Models\Empleado\Empleado; // Ajusta tus namespaces
use App\Models\Empresa\Empresa;
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
    private function generarDataReporte(Request $request)
{
    $user = Auth::user();

    // 1. Rango de Fechas
    $desde = $request->input('desde') ? Carbon::parse($request->input('desde'))->startOfDay() : Carbon::now()->startOfMonth()->startOfDay();
    $hasta = $request->input('hasta') ? Carbon::parse($request->input('hasta'))->endOfDay() : Carbon::now()->endOfMonth()->endOfDay();

    // ... (Tu lógica de tope de fechas se mantiene igual) ...
    $hoy = Carbon::now()->endOfDay();
    $ultimaMarca = MarcacionEmpleado::max('created_at');
    $fechaUltimaMarca = $ultimaMarca ? Carbon::parse($ultimaMarca)->endOfDay() : Carbon::now();
    $tope = $fechaUltimaMarca->greaterThan(Carbon::now()) ? $fechaUltimaMarca : Carbon::now()->endOfDay();
    $hastaReal = $hasta->greaterThan($tope) ? $tope : $hasta;

    // 2. Obtener Empleados CON SUS PERMISOS
    // <--- CORRECCIÓN 1: Cargamos 'permisos.tipoPermiso' aquí para saber si tiene permiso aunque falte
    $queryEmpleados = Empleado::where('estado', 1)
        ->with(['sucursal', 'horarios', 'permisos.tipoPermiso']); 

    if ($request->filled('sucursal')) {
        $queryEmpleados->where('id_sucursal', $request->sucursal);
    }
    if ($request->filled('empleado')) {
        $queryEmpleados->where('id', $request->empleado);
    }
    $empleados = $queryEmpleados->orderBy('apellidos')->get();

    // 3. Obtener Marcaciones
    $queryMarcaciones = MarcacionEmpleado::whereBetween('created_at', [$desde, $hastaReal])
        ->with(['horarioHistorico', 'sucursal', 'permiso.tipoPermiso']); // También lo dejamos aquí por si el permiso está vinculado solo a la marca

    if ($request->filled('empleado')) {
        $queryMarcaciones->where('id_empleado', $request->empleado);
    }
    if ($request->filled('sucursal')) {
        $queryMarcaciones->where('id_sucursal', $request->sucursal);
    }

    $todasMarcaciones = $queryMarcaciones->get();

    $mapaDias = [0 => 'domingo', 1 => 'lunes', 2 => 'martes', 3 => 'miercoles', 4 => 'jueves', 5 => 'viernes', 6 => 'sabado'];
    $reporte = collect();
    $periodo = CarbonPeriod::create($desde, $hastaReal);

    foreach ($periodo as $dia) {
        $diaIndice = $dia->dayOfWeek;
        $nombreDiaHoy = $mapaDias[$diaIndice];
        $fechaStr = $dia->format('Y-m-d');

        foreach ($empleados as $emp) {

            // A. Filtro de Horarios
            $horariosDia = $emp->horarios->filter(function ($h) use ($nombreDiaHoy) {
                $diasDb = array_map(function ($d) { return Str::slug($d); }, $h->dias ?? []);
                return in_array($nombreDiaHoy, $diasDb);
            });

            if ($horariosDia->isEmpty()) continue;

            // <--- CORRECCIÓN 2: Buscamos si el empleado tiene un permiso general activo para ESTE DÍA
            // (Ej: Vacaciones, Incapacidad) que cubra la fecha actual
            $permisoDelDia = $emp->permisos->first(function($p) use ($dia) {
                return $dia->between(Carbon::parse($p->fecha_inicio), Carbon::parse($p->fecha_fin));
            });

            foreach ($horariosDia as $horarioTeorico) {
                $horaEntradaTeorica = Carbon::parse($fechaStr.' '.$horarioTeorico->hora_ini);

                $marca = $todasMarcaciones->filter(function ($m) use ($emp, $fechaStr, $horaEntradaTeorica) {
                    return $m->id_empleado == $emp->id
                        && $m->tipo_marcacion == 1
                        && $m->created_at->format('Y-m-d') == $fechaStr
                        && abs($m->created_at->diffInMinutes($horaEntradaTeorica)) < (4 * 60);
                })->first();

                // C. Definir Datos Base
                $row = [
                    'fecha' => $dia->copy(),
                    'empleado' => $emp,
                    'sucursal' => $emp->sucursal,
                    'horario_programado' => Carbon::parse($horarioTeorico->hora_ini)->format('H:i').' - '.Carbon::parse($horarioTeorico->hora_fin)->format('H:i'),
                    'entrada_real' => null,
                    'salida_real' => null,
                    'minutos_tarde' => 0,
                    'estado_key' => 'ausente',
                    'foto_entrada' => null,
                    'foto_salida' => null,
                    'permiso_info' => null,
                ];

                // <--- CORRECCIÓN 3: Pre-cargamos la info del permiso si existe permiso del día
                if ($permisoDelDia) {
                    $row['permiso_info'] = [
                        'tipo' => $permisoDelDia->tipoPermiso->nombre ?? 'Permiso',
                        'motivo' => $permisoDelDia->motivo,
                        'desde' => $permisoDelDia->fecha_inicio,
                        'hasta' => $permisoDelDia->fecha_fin,
                    ];
                    // Si hay permiso global del día, el estado base es 'permiso', no 'ausente'
                    $row['estado_key'] = 'permiso';
                }

                if ($marca) {
                    $estado = 'presente';

                    if ($marca->horarioHistorico) {
                        $hHist = $marca->horarioHistorico;
                        $row['horario_programado'] = Carbon::parse($hHist->hora_entrada)->format('H:i').' - '.Carbon::parse($hHist->hora_salida)->format('H:i');
                        $horaEntradaTeorica = Carbon::parse($fechaStr.' '.$hHist->hora_entrada);
                    }

                    $row['entrada_real'] = $marca->created_at;
                    $row['foto_entrada'] = $marca->ubi_foto;

                    // <--- CORRECCIÓN 4: Si la marcación tiene un permiso específico (ej: Llegada tardía), este SOBRESCRIBE al general
                    if ($marca->permiso) {
                        $row['permiso_info'] = [
                            'tipo' => $marca->permiso->tipoPermiso->nombre ?? 'Permiso',
                            'motivo' => $marca->permiso->motivo,
                            'desde' => $marca->permiso->fecha_inicio,
                            'hasta' => $marca->permiso->fecha_fin,
                        ];
                    }

                    // Buscar salida
                    $salida = $todasMarcaciones->where('id_empleado', $emp->id)
                        ->where('tipo_marcacion', 2)
                        ->where('created_at', '>', $marca->created_at)
                        ->where('created_at', '<', $dia->copy()->endOfDay()->addHours(6))
                        ->sortBy('created_at')
                        ->first();

                    if ($salida) {
                        $row['salida_real'] = $salida->created_at;
                        $row['foto_salida'] = $salida->ubi_foto;
                    } elseif (! $dia->isToday()) {
                        $estado = 'sin_cierre';
                    }

                    // Calcular incidencias (Estado Final)
                    $esTarde = $marca->created_at->greaterThan($horaEntradaTeorica->copy()->addMinutes($horarioTeorico->tolerancia));

                    if ($esTarde) {
                        $row['minutos_tarde'] = $marca->created_at->diffInMinutes($horaEntradaTeorica);
                        
                        // Si es tarde Y tiene permiso (ya sea en la marca o general del día)
                        if (!empty($row['permiso_info'])) {
                            $estado = 'tarde_con_permiso';
                        } else {
                            $estado = 'tarde';
                        }
                    } else {
                        // Llegó bien. Verificamos si tiene permiso (ej: salida anticipada o permiso de entrada activo)
                        if (!empty($row['permiso_info'])) {
                            $estado = 'permiso'; 
                        } else {
                            $estado = 'presente';
                        }
                    }
                    
                    // Prioridad de estado: Si no cerró turno, gana 'sin_cierre' a menos que sea un permiso justificado
                    if ($estado == 'sin_cierre' && !empty($row['permiso_info'])) {
                         // Opcional: Si quieres que 'sin_cierre' se muestre aunque tenga permiso, deja esto.
                         // Si prefieres que el permiso oculte el olvido de salida, cambia la lógica aquí.
                    }

                    $row['estado_key'] = $estado;
                }

                $reporte->push($row);
            }
        }
    }

    // 5. Filtrado Final (Igual que antes)
    if ($request->filled('incidencia')) {
        $filtro = $request->incidencia;
        $reporte = $reporte->filter(function ($row) use ($filtro) {
            if ($filtro == 'asistencia_ok') return $row['estado_key'] == 'presente';
            return $row['estado_key'] == $filtro;
        });
    }

    return $reporte;
}

    public function generarPdf(Request $request)
    {
        $registros = $this->generarDataReporte($request); // Devuelve Collection
        $empresa = Empresa::first(); // Asegúrate de tener este modelo o pasa null

        $filtros = [
            'desde' => $request->input('desde') ?? date('Y-m-01'),
            'hasta' => $request->input('hasta') ?? date('Y-m-d'),
            'sucursal' => $request->filled('sucursal') ? Sucursal::find($request->sucursal)->nombre : 'Todas',
        ];

        // IMPORTANTE: Asegúrate de que la vista 'reportes.marcaciones.pdf' espere $registros
        $pdf = Pdf::loadView('reportes.marcaciones.pdf', compact('registros', 'empresa', 'filtros'));
        $pdf->setPaper('letter', 'landscape');

        return $pdf->stream('reporte_asistencia.pdf');
    }

    // ... tu método index ...
    public function index(Request $request)
    {
        // Listas para los selects
        $sucursales = Sucursal::visiblePara(Auth::user())
            ->where('estado', 1)->get();
        $empleadosList = Empleado::where('estado', 1)->orderBy('nombres')->get();

        // Inicializamos la colección vacía para que no de error al cargar la página
        $marcaciones = collect();

        // Solo procesamos si el usuario envió filtros (ej: botón buscar)
        // O si quieres que cargue por defecto al entrar, quita el 'if'

        $marcaciones = $this->generarDataReporte($request);

        // Pasamos la variable con el nombre correcto: 'marcaciones'
        return view('reportes.marcaciones.marcaciones_rep', compact('marcaciones', 'sucursales', 'empleadosList'));
    }
}
