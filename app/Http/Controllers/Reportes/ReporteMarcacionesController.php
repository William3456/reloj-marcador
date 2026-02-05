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

        $hoy = Carbon::now()->endOfDay();
        // Ajuste: Si el reporte es histórico (ej: mes pasado), respetamos la fecha fin. Si es actual, cortamos en hoy.
        // Opción más robusta: El tope es la fecha elegida O la fecha de la última marcación registrada (lo que sea mayor)
        $ultimaMarca = MarcacionEmpleado::max('created_at');
        $fechaUltimaMarca = $ultimaMarca ? Carbon::parse($ultimaMarca)->endOfDay() : Carbon::now();

        // Si hay datos en el "futuro" (test data), extendemos el reporte hasta allá
        $tope = $fechaUltimaMarca->greaterThan(Carbon::now()) ? $fechaUltimaMarca : Carbon::now()->endOfDay();

        $hastaReal = $hasta->greaterThan($tope) ? $tope : $hasta;

        // 2. Obtener Empleados
        $queryEmpleados = Empleado::where('estado', 1)->with(['sucursal', 'horarios']);

        if ($request->filled('sucursal')) {
            $queryEmpleados->where('id_sucursal', $request->sucursal);
        }
        if ($request->filled('empleado')) {
            $queryEmpleados->where('id', $request->empleado);
        }
        $empleados = $queryEmpleados->orderBy('apellidos')->get();

        // 3. Obtener Marcaciones (OPTIMIZADO: Filtramos por SQL, no por PHP)
        $queryMarcaciones = MarcacionEmpleado::whereBetween('created_at', [$desde, $hastaReal])
            ->with(['horarioHistorico', 'sucursal']);

        // IMPORTANTE: Filtrar también aquí para no traer basura de otros empleados
        if ($request->filled('empleado')) {
            $queryMarcaciones->where('id_empleado', $request->empleado);
        }
        if ($request->filled('sucursal')) {
            $queryMarcaciones->where('id_sucursal', $request->sucursal);
        }

        $todasMarcaciones = $queryMarcaciones->get();

        // 4. Mapeo de Días
        $mapaDias = [
            0 => 'domingo', 1 => 'lunes', 2 => 'martes', 3 => 'miercoles', 4 => 'jueves', 5 => 'viernes', 6 => 'sabado',
        ];

        $reporte = collect();
        $periodo = CarbonPeriod::create($desde, $hastaReal);

        foreach ($periodo as $dia) {
            $diaIndice = $dia->dayOfWeek;
            $nombreDiaHoy = $mapaDias[$diaIndice];
            $fechaStr = $dia->format('Y-m-d');

            foreach ($empleados as $emp) {

                // A. Filtro de Horarios (Normalizando texto)
                $horariosDia = $emp->horarios->filter(function ($h) use ($nombreDiaHoy) {
                    $diasDb = array_map(function ($d) {
                        return Str::slug($d);
                    }, $h->dias ?? []);

                    return in_array($nombreDiaHoy, $diasDb);
                });

                // Si no hay horario, saltamos
                if ($horariosDia->isEmpty()) {
                    continue;
                }

                foreach ($horariosDia as $horarioTeorico) {

                    // Hora exacta teórica de entrada para ese día
                    $horaEntradaTeorica = Carbon::parse($fechaStr.' '.$horarioTeorico->hora_ini);

                    // B. Buscar Marcación (Match Inteligente)
                    // Buscamos una entrada que ocurra el mismo día y tenga un margen razonable (ej: +/- 4 horas del inicio)
                    $marca = $todasMarcaciones->filter(function ($m) use ($emp, $fechaStr, $horaEntradaTeorica) {
                        return $m->id_empleado == $emp->id
                            && $m->tipo_marcacion == 1
                            && $m->created_at->format('Y-m-d') == $fechaStr
                            // Usamos floatDiffInHours para mayor precisión o diffInMinutes
                            && abs($m->created_at->diffInMinutes($horaEntradaTeorica)) < (4 * 60);
                    })->first();

                    // C. Definir Estado Inicial
                    $estado = 'ausente';

                    $row = [
                        'fecha' => $dia->copy(), // Usar copy() por seguridad en bucles de fecha
                        'empleado' => $emp,
                        'sucursal' => $emp->sucursal,
                        'horario_programado' => Carbon::parse($horarioTeorico->hora_ini)->format('H:i').' - '.Carbon::parse($horarioTeorico->hora_fin)->format('H:i'),
                        'entrada_real' => null,
                        'salida_real' => null,
                        'minutos_tarde' => 0,
                        'estado_key' => 'ausente',
                        'foto_entrada' => null,
                        'foto_salida' => null,
                    ];

                    if ($marca) {
                        $estado = 'presente';

                        // Si existe histórico, usamos esa foto congelada
                        if ($marca->horarioHistorico) {
                            $hHist = $marca->horarioHistorico;
                            $row['horario_programado'] = Carbon::parse($hHist->hora_entrada)->format('H:i').' - '.Carbon::parse($hHist->hora_salida)->format('H:i');

                            // Recalculamos la teórica basada en el histórico para medir tardanza real
                            $horaEntradaTeorica = Carbon::parse($fechaStr.' '.$hHist->hora_entrada);
                        }

                        $row['entrada_real'] = $marca->created_at;
                        $row['foto_entrada'] = $marca->ubi_foto;

                        // Buscar su salida correspondiente
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
                            // Solo es "sin cierre" si el día ya pasó
                            $estado = 'sin_cierre';
                        }

                        // Calcular incidencias
                        if ($marca->id_permiso_aplicado) {
                            $estado = 'permiso';
                        } elseif ($marca->created_at->greaterThan($horaEntradaTeorica->copy()->addMinutes($horarioTeorico->tolerancia))) {
                            $estado = 'tarde';
                            $row['minutos_tarde'] = $marca->created_at->diffInMinutes($horaEntradaTeorica);
                        }
                    }

                    $row['estado_key'] = $estado;
                    $reporte->push($row);
                }
            }
        }

        // 5. Filtrado Final
        if ($request->filled('incidencia')) {
            $filtro = $request->incidencia;
            $reporte = $reporte->filter(function ($row) use ($filtro) {
                if ($filtro == 'asistencia_ok') {
                    return $row['estado_key'] == 'presente';
                }

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
