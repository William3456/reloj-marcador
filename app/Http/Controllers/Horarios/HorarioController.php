<?php

namespace App\Http\Controllers\Horarios;

use App\Http\Controllers\Controller;
use App\Models\Horario\horario;
use App\Models\Horario\HorarioHistorico;
use App\Models\HorarioEmpleado\HorarioEmpleado;
use App\Models\HorarioSucursal\HorarioSucursal;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HorarioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $horarios = horario::orderBy('permitido_marcacion', 'asc')->get();

        return view('horarios.index', [
            'horarios' => $horarios,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $dias = config('dias.laborales');

        return view('horarios.create', compact('dias'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'hora_ini' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i', 'different:hora_ini'],
            'permitido_marcacion' => ['required', 'in:0,1'],
            'estado' => ['required', 'in:0,1'],
            'tolerancia' => ['required', 'integer', 'min:0'],
            'requiere_salida' => ['required', 'in:0,1'],
            'turno_txt' => ['required', 'string'],
            'turno' => ['required', 'integer'],
            'dias' => 'array|required|min:1',
        ]);

        $user = Auth::user();
        if (! isset($user->empleado->id_sucursal)) {
            $validated['sucursal_creacion'] = 0;
        } else {
            $validated['sucursal_id'] = $user->empleado->id_sucursal;
            $validated['sucursal_creacion'] = $validated['sucursal_id'];
        }

        $contaErrr = 0;

        // 1. Forzamos el orden y aseguramos que las tildes no se codifiquen (JSON_UNESCAPED_UNICODE)
        $diasJson = collect($validated['dias'])
            ->map(fn ($d) => mb_strtolower($d)) // Normalizamos a minúsculas
            ->sort()
            ->values()
            ->toJson(JSON_UNESCAPED_UNICODE);

        // 2. Buscamos la coincidencia
        $existe = Horario::where('hora_ini', $validated['hora_ini'])
            ->where('hora_fin', $validated['hora_fin'])
            ->where('permitido_marcacion', $validated['permitido_marcacion'])
            ->where('tolerancia', $validated['tolerancia'])
            ->where('requiere_salida', $validated['requiere_salida'])
            ->where('estado', $validated['estado'])
            // Usamos la función JSON de la BD para comparar objetos, no texto plano
            ->whereRaw('JSON_CONTAINS(dias, ?) AND JSON_LENGTH(dias) = ?', [
                $diasJson,
                count($validated['dias']),
            ])
            ->exists();

        if ($existe) {
            $contaErrr = 1;
        }

        if ($contaErrr > 0) {
            return back()->with('error', 'No se puede crear un horario con los mismos parámetros')->withInput();
        } else {

            try {
                DB::transaction(function () use ($validated) {

                    // 3. Crear el Horario Maestro (La "cáscara")
                    $nuevoHorario = Horario::create($validated);

                    // 4. Formatear horas a H:i:s para que coincidan con el formato del histórico
                    $horaEntradaFormat = Carbon::parse($validated['hora_ini'])->format('H:i:s');
                    $horaSalidaFormat = Carbon::parse($validated['hora_fin'])->format('H:i:s');

                    // 5. Nace la "Versión 1" (El Histórico inicial)
                    HorarioHistorico::create([
                        'id_horario' => $nuevoHorario->id,
                        'tipo_horario' => $nuevoHorario->permitido_marcacion,
                        'hora_entrada' => $horaEntradaFormat,
                        'hora_salida' => $horaSalidaFormat,
                        'tolerancia' => $nuevoHorario->tolerancia,
                        'dias' => $nuevoHorario->dias,
                        'vigente_desde' => now(),
                        'vigente_hasta' => null,
                    ]);

                });

                return redirect()->route('horarios.create')->with('success', 'Horario creado correctamente.');

            } catch (\Exception $e) {
                return back()->with('error', 'Error al crear el horario: '.$e->getMessage())->withInput();
            }
        }
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
        $horario = horario::findOrFail($id);
        $dias = config('dias.laborales');

        return view('horarios.edit', compact('horario', 'dias'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'hora_ini' => ['required', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'hora_fin' => ['required', 'regex:/^\d{2}:\d{2}(:\d{2})?$/', 'different:hora_ini'],
            'permitido_marcacion' => ['required', 'in:0,1'],
            'estado' => ['required', 'in:0,1'],
            'tolerancia' => ['required', 'integer', 'min:0'],
            'requiere_salida' => ['required', 'in:0,1'],
            'turno_txt' => ['required', 'string'],
            'turno' => ['required', 'integer'],
            'dias' => 'array|required|min:1',
        ]);

        $horario = horario::findOrFail($id);

        try {
            DB::transaction(function () use ($horario, $validated) {

                // 2. Actualizar el Maestro
                $horario->update($validated);

                // CORRECCIÓN: Formatear horas a H:i:s
                $horaEntradaFormat = Carbon::parse($validated['hora_ini'])->format('H:i:s');
                $horaSalidaFormat = Carbon::parse($validated['hora_fin'])->format('H:i:s');

                // 3. Manejo del Histórico

                // A. Cerrar vigente actual
                HorarioHistorico::where('id_horario', $horario->id)
                    ->vigente()
                    ->update(['vigente_hasta' => now()]);

                // B. Crear nuevo histórico
                $nuevoHistorico = HorarioHistorico::create([
                    'id_horario' => $horario->id,
                    'tipo_horario' => $horario->permitido_marcacion,
                    'hora_entrada' => $horaEntradaFormat,
                    'hora_salida' => $horaSalidaFormat,
                    'tolerancia' => $horario->tolerancia,
                    'dias' => $horario->dias,
                    'vigente_desde' => now(),
                    'vigente_hasta' => null,
                ]);

                // 4. ACTUALIZACIÓN DE TABLAS INTERMEDIAS (OPCIÓN B: Mantener el historial)
                $fechaCambio = now()->toDateString(); // Hoy (Ej. 2026-02-26)
                $fechaFinVieja = now()->subDay()->toDateString(); // Ayer (Ej. 2026-02-25)

                // Determinar la tabla y el campo foráneo según el tipo
                $tabla = ($horario->permitido_marcacion == 1) ? 'horarios_sucursales' : 'horarios_trabajadores';
                $campoForaneo = ($horario->permitido_marcacion == 1) ? 'id_sucursal' : 'id_empleado';

                // Buscar solo las asignaciones que están vigentes actualmente para este horario
                $asignacionesViejas = DB::table($tabla)
                    ->where('id_horario', $horario->id)
                    ->where('es_actual', 1)
                    ->get();

                foreach ($asignacionesViejas as $asignacion) {

                    // Si la asignación se creó hoy mismo, la cerramos hoy mismo para evitar fechas ilógicas
                    $fechaFinReal = ($asignacion->fecha_inicio >= $fechaCambio) ? $asignacion->fecha_inicio : $fechaFinVieja;

                    // 4.1 Cerrar la asignación vieja
                    DB::table($tabla)
                        ->where('id', $asignacion->id)
                        ->update([
                            'fecha_fin' => $fechaFinReal,
                            'es_actual' => 0,
                            'updated_at' => now(),
                        ]);

                    // 4.2 Insertar la nueva asignación en limpio
                    DB::table($tabla)->insert([
                        $campoForaneo => $asignacion->{$campoForaneo},
                        'id_horario' => $horario->id,
                        'id_horario_historico' => $nuevoHistorico->id,
                        'fecha_inicio' => $fechaCambio,
                        'fecha_fin' => null,
                        'es_actual' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });

            return redirect()->route('horarios.index')->with('success', 'Horario actualizado y versionado correctamente.');

        } catch (\Exception $e) {
            return back()->with('error', 'Error al actualizar: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $horario = horario::findOrFail($id);

        // 1. Verificamos usando Eloquent si tiene sucursales (pasadas o presentes)
        $tieneSucursal = HorarioSucursal::where('id_horario', $horario->id)->exists();

        // 2. Verificamos usando Eloquent si tiene empleados (pasados o presentes)
        $tieneTrabajador = HorarioEmpleado::where('id_horario', $horario->id)->exists();

        // 3. Bloqueo de seguridad con el mensaje contextualizado
        if ($tieneSucursal || $tieneTrabajador) {
            return redirect()->route('horarios.index')
                ->with('error', 'No se puede eliminar: Este horario está asignado actualmente o fue asignado en el pasado a uno o más empleados/sucursales. Esto afectaría el historial de reportes.');
        }

        // 4. Si el horario está "limpio", lo borramos de forma segura
        try {
            DB::transaction(function () use ($horario) {

                // A. Primero borramos todas sus versiones en la tabla histórica usando Eloquent
                HorarioHistorico::where('id_horario', $horario->id)->delete();

                // B. Finalmente, borramos el horario maestro
                $horario->delete();

            });

            return redirect()->route('horarios.index')->with('success', 'Horario y su historial de versiones eliminados correctamente.');

        } catch (\Exception $e) {
            return redirect()->route('horarios.index')->with('error', 'Error al intentar eliminar el horario: '.$e->getMessage());
        }
    }
}
