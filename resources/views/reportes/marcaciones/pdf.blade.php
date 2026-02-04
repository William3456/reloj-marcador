@extends('layouts.pdf_layout')
{{-- Asegúrate que tu layout cargue estilos básicos de tabla --}}

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
                <th style="border: 1px solid #d1d5db; padding: 6px;">Turno (Histórico/Actual)</th>
                <th style="border: 1px solid #d1d5db; padding: 6px;">Entrada Real</th>
                <th style="border: 1px solid #d1d5db; padding: 6px;">Salida Real</th>
                <th style="border: 1px solid #d1d5db; padding: 6px;">Estado</th>
                <th style="border: 1px solid #d1d5db; padding: 6px;">Notas</th>
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
                            $color = '#991b1b'; // Rojo fuerte
                            $estadoLabel = 'AUSENTE';
                            break;
                        case 'tarde':
                            $bg = '#ffedd5'; // Naranja suave
                            $color = '#9a3412'; // Naranja fuerte
                            $estadoLabel = 'RETARDO';
                            break;
                        case 'sin_cierre':
                            $bg = '#fef9c3'; // Amarillo suave
                            $color = '#854d0e';
                            $estadoLabel = 'SIN SALIDA';
                            break;
                        case 'permiso':
                            $bg = '#dbeafe'; // Azul suave
                            $color = '#1e40af';
                            $estadoLabel = 'PERMISO';
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
                    {{-- HORARIO (Histórico si marcó, Actual si faltó) --}}
                    <td style="border: 1px solid #d1d5db; padding: 5px; text-align: center;">
                        {{ $row['horario_programado'] }}
                    </td>
                    {{-- ENTRADA --}}
                    <td style="border: 1px solid #d1d5db; padding: 5px; text-align: center;">
                        @if($row['entrada_real'])
                            {{ $row['entrada_real']->format('H:i:s') }}
                        @else
                            -
                        @endif
                    </td>
                    {{-- SALIDA --}}
                    <td style="border: 1px solid #d1d5db; padding: 5px; text-align: center;">
                        @if($row['salida_real'])
                            {{ $row['salida_real']->format('H:i:s') }}
                        @else
                            -
                        @endif
                    </td>
                    {{-- ESTADO --}}
                    <td
                        style="border: 1px solid #d1d5db; padding: 5px; text-align: center; font-weight: bold; color: {{ $color }};">
                        {{ $estadoLabel }}
                    </td>
                    {{-- NOTAS (Minutos tarde) --}}
                    <td style="border: 1px solid #d1d5db; padding: 5px; text-align: center;">
                        @if($row['minutos_tarde'] > 0)
    @php
        $minTotal = round($row['minutos_tarde']);
        $horas = floor($minTotal / 60);
        $minutos = $minTotal % 60;
        
        $textoTiempo = $horas > 0 
            ? "+{$horas}h {$minutos}m" 
            : "+{$minTotal} min";
    @endphp
    <span style="color: #9a3412; font-weight: bold; font-size: 9px;">
        {{ $textoTiempo }}
    </span>
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
                <td style="border: 1px solid #ddd; padding: 4px;">Total Turnos:</td>
                <td style="border: 1px solid #ddd; padding: 4px; text-align: right;">{{ $registros->count() }}</td>
            </tr>
            <tr>
                <td style="border: 1px solid #ddd; padding: 4px; color: #991b1b;">Ausencias:</td>
                <td style="border: 1px solid #ddd; padding: 4px; text-align: right;">
                    {{ $registros->where('estado_key', 'ausente')->count() }}</td>
            </tr>
            <tr>
                <td style="border: 1px solid #ddd; padding: 4px; color: #9a3412;">Llegadas Tarde:</td>
                <td style="border: 1px solid #ddd; padding: 4px; text-align: right;">
                    {{ $registros->where('estado_key', 'tarde')->count() }}</td>
            </tr>
        </table>
    </div>
@endsection