@extends('layouts.pdf_layout')

@section('title', 'Reporte de Asistencia')

@section('content')
    <div style="margin-bottom: 20px; border-bottom: 1px solid #ccc; padding-bottom: 10px;">
        <h2 style="text-align: center; margin: 0; color: #444;">Reporte de Turnos y Asistencia</h2>
        <div style="text-align: center; font-size: 11px; margin-top: 5px; color: #666;">
            <strong>Periodo:</strong> {{ \Carbon\Carbon::parse($filtros['desde'])->format('d/m/Y') }} al
            {{ \Carbon\Carbon::parse($filtros['hasta'])->format('d/m/Y') }}
            | <strong>Sucursal:</strong> {{ $filtros['sucursal'] }}
        </div>
    </div>

    <table width="100%" style="border-collapse: collapse; font-size: 10px; font-family: sans-serif;">
        <thead>
            <tr style="background-color: #e5e7eb; color: #1f2937;">
                <th style="border: 1px solid #d1d5db; padding: 6px;">Fecha</th>
                <th style="border: 1px solid #d1d5db; padding: 6px;">Empleado</th>
                <th style="border: 1px solid #d1d5db; padding: 6px;">Turno</th>
                <th style="border: 1px solid #d1d5db; padding: 6px;">Entrada</th>
                <th style="border: 1px solid #d1d5db; padding: 6px;">Salida</th>
                <th style="border: 1px solid #d1d5db; padding: 6px;">Estado</th>
                <th style="border: 1px solid #d1d5db; padding: 6px; width: 25%;">Notas / Permisos</th>
            </tr>
        </thead>
        <tbody>
            @forelse($registros as $row)
                @php
                    $bg = '#fff';
                    $color = '#000';
                    $estadoLabel = 'PUNTUAL';

                    switch ($row['estado_key']) {
                        case 'ausente':
                            $bg = '#fee2e2'; // Rojo suave
                            $color = '#991b1b';
                            $estadoLabel = 'AUSENTE';
                            break;
                        case 'tarde':
                            $bg = '#ffedd5'; // Naranja suave
                            $color = '#9a3412';
                            $estadoLabel = 'RETARDO';
                            break;
                        // NUEVO CASO: RETARDO CON PERMISO
                        case 'tarde_con_permiso':
                            $bg = '#fff7ed'; // Naranja muy pálido
                            $color = '#c2410c'; // Naranja oscuro
                            $estadoLabel = 'RETARDO (C/ PERMISO)';
                            break;
                        case 'sin_cierre':
                            $bg = '#fef9c3'; // Amarillo suave
                            $color = '#854d0e';
                            $estadoLabel = 'SIN SALIDA';
                            break;
                        case 'permiso':
                            $bg = '#eff6ff'; // Azul muy suave
                            $color = '#1e40af';
                            $estadoLabel = 'PERMISO APLICADO';
                            break;
                        case 'presente':
                            $color = '#166534'; // Verde
                            $estadoLabel = 'ASISTENCIA';
                            break;
                    }
                @endphp

                <tr style="background-color: {{ $bg }};">
                    <td style="border: 1px solid #d1d5db; padding: 5px; text-align: center;">
                        {{ $row['fecha']->format('d/m/Y') }}<br>
                        <small style="color: #666;">{{ ucfirst($row['fecha']->locale('es')->isoFormat('dddd')) }}</small>
                    </td>
                    <td style="border: 1px solid #d1d5db; padding: 5px;">
                        <strong>{{ $row['empleado']->nombres }} {{ $row['empleado']->apellidos }}</strong><br>
                        <small>{{ $row['sucursal']->nombre ?? '' }}</small>
                    </td>
                    <td style="border: 1px solid #d1d5db; padding: 5px; text-align: center;">
                        {{ $row['horario_programado'] }}
                    </td>
                    <td style="border: 1px solid #d1d5db; padding: 5px; text-align: center;">
                        {{ $row['entrada_real'] ? $row['entrada_real']->format('H:i:s') : '-' }}
                    </td>
                    <td style="border: 1px solid #d1d5db; padding: 5px; text-align: center;">
                        {{ $row['salida_real'] ? $row['salida_real']->format('H:i:s') : '-' }}
                    </td>
                    <td style="border: 1px solid #d1d5db; padding: 5px; text-align: center; font-weight: bold; color: {{ $color }}; font-size: 9px;">
                        {{ $estadoLabel }}
                    </td>
                    
                    {{-- COLUMNA NOTAS / PERMISOS --}}
                    <td style="border: 1px solid #d1d5db; padding: 5px;">
                        {{-- 1. Minutos Tarde --}}
                        @if($row['minutos_tarde'] > 0)
                            @php
                                $minTotal = round($row['minutos_tarde']);
                                $horas = floor($minTotal / 60);
                                $minutos = $minTotal % 60;
                                $textoTiempo = $horas > 0 ? "+{$horas}h {$minutos}m" : "+{$minTotal} min";
                            @endphp
                            <div style="color: #9a3412; font-weight: bold; font-size: 9px; margin-bottom: 3px;">
                                Tiempo de retraso: {{ $textoTiempo }}
                            </div>
                        @endif

                        {{-- 2. Información del Permiso --}}
                        @if(!empty($row['permiso_info']))
                            <div style="background-color: rgba(255,255,255,0.5); border: 1px dashed #cbd5e1; padding: 3px; border-radius: 4px;">
                                <div style="font-weight: bold; color: #1e40af; font-size: 9px;">
                                    {{ $row['permiso_info']['tipo'] }}
                                </div>
                                
                                @if($row['permiso_info']['motivo'])
                                    <div style="font-style: italic; color: #475569; font-size: 9px; margin-top: 1px;">
                                        "{{ Str::limit($row['permiso_info']['motivo'], 50) }}"
                                    </div>
                                @endif

                                <div style="font-size: 8px; color: #64748b; margin-top: 2px;">
                                    Vigencia: 
                                    {{ \Carbon\Carbon::parse($row['permiso_info']['desde'])->format('d/m') }} - 
                                    {{ \Carbon\Carbon::parse($row['permiso_info']['hasta'])->format('d/m') }}
                                </div>
                            </div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="padding: 20px; text-align: center; color: #666;">No se encontraron registros.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Resumen de totales --}}
    <div style="margin-top: 20px; width: 40%; float: right;">
        <table width="100%" style="border-collapse: collapse; font-size: 10px;">
            <tr style="background-color: #333; color: #fff;">
                <th colspan="2" style="padding: 5px;">Resumen</th>
            </tr>
            <tr>
                <td style="border: 1px solid #ddd; padding: 4px;">Total Turnos Evaluados:</td>
                <td style="border: 1px solid #ddd; padding: 4px; text-align: right;">{{ $registros->count() }}</td>
            </tr>
            <tr>
                <td style="border: 1px solid #ddd; padding: 4px; color: #991b1b;">Ausencias Totales:</td>
                <td style="border: 1px solid #ddd; padding: 4px; text-align: right;">
                    {{ $registros->where('estado_key', 'ausente')->count() }}
                </td>
            </tr>
            <tr>
                <td style="border: 1px solid #ddd; padding: 4px; color: #9a3412;">Llegadas Tarde (Sin Permiso):</td>
                <td style="border: 1px solid #ddd; padding: 4px; text-align: right;">
                    {{ $registros->where('estado_key', 'tarde')->count() }}
                </td>
            </tr>
            
            {{-- NUEVO ROW: PERMISOS --}}
            <tr>
                <td style="border: 1px solid #ddd; padding: 4px; color: #1e40af;">Permisos Aplicados:</td>
                <td style="border: 1px solid #ddd; padding: 4px; text-align: right; font-weight: bold;">
                    {{-- Contamos tanto 'permiso' como 'tarde_con_permiso' --}}
                    {{ $registros->filter(function($r){ 
                        return in_array($r['estado_key'], ['permiso', 'tarde_con_permiso']); 
                    })->count() }}
                </td>
            </tr>
        </table>
    </div>
@endsection