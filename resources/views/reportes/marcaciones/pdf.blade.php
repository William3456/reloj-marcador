@extends('layouts.pdf_layout')

@section('title', 'Reporte de Asistencia')

@section('content')
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #334155; }
        .header-table { width: 100%; border-bottom: 2px solid #e2e8f0; padding-bottom: 15px; margin-bottom: 20px; }
        .title { margin: 0; font-size: 22px; color: #0f172a; text-transform: uppercase; letter-spacing: 1px; }
        .meta-data { font-size: 11px; color: #64748b; margin-top: 5px; }
        
        .data-table { width: 100%; border-collapse: collapse; font-size: 10px; margin-bottom: 20px; }
        .data-table th { background-color: #f8fafc; color: #475569; text-transform: uppercase; font-weight: bold; padding: 10px 8px; text-align: left; border-bottom: 2px solid #cbd5e1; }
        .data-table td { padding: 10px 8px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        .data-table tr:nth-child(even) { background-color: #fcfcfc; }
        
        .badge { padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 9px; display: inline-block; text-align: center; }
        .badge-green { background-color: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .badge-red { background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .badge-orange { background-color: #ffedd5; color: #9a3412; border: 1px solid #fed7aa; }
        .badge-blue { background-color: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
        .badge-yellow { background-color: #fef9c3; color: #854d0e; border: 1px solid #fef08a; }
        .badge-purple { background-color: #f3e8ff; color: #6b21a8; border: 1px solid #e9d5ff; }

        .summary-wrapper { width: 45%; float: right; margin-top: 10px; }
        .summary-table { width: 100%; border-collapse: collapse; font-size: 10px; }
        .summary-table th { background-color: #1e293b; color: white; padding: 8px; text-align: left; font-size: 11px; }
        .summary-table td { padding: 8px; border-bottom: 1px solid #e2e8f0; }
        .val-col { text-align: right; font-weight: bold; font-size: 12px;}
    </style>

    {{-- Encabezado Formal --}}
    <table class="header-table">
        <tr>
            <td style="width: 50%;">
                <h2 class="title">Auditoría de Asistencia</h2>
                <div class="meta-data">
                    <strong>Fecha Generación:</strong> {{ now()->format('d/m/Y H:i A') }}<br>
                    <strong>Filtro Aplicado:</strong> {{ $filtros['incidencia'] }}
                </div>
            </td>
            <td style="width: 50%; text-align: right; vertical-align: top;">
                <div style="font-size: 12px; color: #0f172a; font-weight: bold;">Rango Evaluado:</div>
                <div style="font-size: 11px; color: #64748b; margin-bottom: 4px;">
                    {{ \Carbon\Carbon::parse($filtros['desde'])->format('d M, Y') }} - {{ \Carbon\Carbon::parse($filtros['hasta'])->format('d M, Y') }}
                </div>
                
                {{-- NUEVO: INFO ESPECÍFICA DE SUCURSAL SI FUE FILTRADA --}}
                {{-- INFO ESPECÍFICA DE SUCURSAL SI FUE FILTRADA --}}
                @if($filtros['sucursal_obj'])
                    <div style="font-size: 10px; color: #475569; line-height: 1.4;">
                        <strong style="font-size: 11px; color: #0f172a;">Sucursal: {{ $filtros['sucursal_obj']->nombre }}</strong><br>
                        @if($filtros['sucursal_obj']->direccion) {{ $filtros['sucursal_obj']->direccion }}<br> @endif
                        @if($filtros['sucursal_obj']->telefono) Tel: {{ $filtros['sucursal_obj']->telefono }} | @endif
                        @if($filtros['sucursal_obj']->correo ?? $filtros['sucursal_obj']->encargado) 
                            {{ $filtros['sucursal_obj']->correo ?? $filtros['sucursal_obj']->encargado }} <br> 
                        @endif
                        Rango GPS: {{ $filtros['sucursal_obj']->rango_marcacion_mts }}m | Margen Error: {{ $filtros['sucursal_obj']->margen_error_gps_mts }}m
                        
                        {{-- 🌟 NUEVO: DÍAS Y HORARIOS DE OPERACIÓN --}}
                        <div style="margin-top: 6px; padding-top: 6px; border-top: 1px dotted #cbd5e1;">
                            @if(!empty($filtros['sucursal_obj']->dias_laborales))
                                @php 
                                    $diasL = is_array($filtros['sucursal_obj']->dias_laborales) ? $filtros['sucursal_obj']->dias_laborales : json_decode($filtros['sucursal_obj']->dias_laborales, true);
                                @endphp
                                <strong style="color: #0f172a;">Días de Operación (Actuales):</strong> 
                                {{ implode(', ', array_map('ucfirst', $diasL ?? [])) }}<br>
                            @endif
                            
                            @if($filtros['sucursal_obj']->horarios && $filtros['sucursal_obj']->horarios->isNotEmpty())
                                <strong style="color: #0f172a;">Horarios de Atención (Actuales):</strong><br>
                                @foreach($filtros['sucursal_obj']->horarios as $hs)
                                    @php
                                        $diasH = is_array($hs->dias) ? $hs->dias : json_decode($hs->dias, true);
                                        $strDias = implode(', ', array_map(function($d) { return ucfirst(substr(trim($d), 0, 3)); }, $diasH ?? []));
                                    @endphp
                                    <div style="font-size: 9px; margin-bottom: 2px;">
                                        {{ \Carbon\Carbon::parse($hs->hora_ini)->format('H:i') }} a {{ \Carbon\Carbon::parse($hs->hora_fin)->format('H:i') }} 
                                        <span style="color: #94a3b8;">({{ $strDias }})</span>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                @else
                    <div style="font-size: 11px; color: #64748b;">
                        <strong>Sucursal:</strong> Consolidado General (Todas)
                    </div>
                @endif
            </td>
        </tr>
    </table>

    {{-- Tabla Principal --}}
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 10%;">Fecha</th>
                <th style="width: 20%;">Empleado / Sucursal</th>
                <th style="width: 10%; text-align: center;">Horario</th>
                {{-- NUEVA COLUMNA DE TOLERANCIA --}}
                <th style="width: 8%; text-align: center;">Tolerancia</th>
                <th style="width: 12%; text-align: center;">Marcación Real</th>
                <th style="width: 14%; text-align: center;">Estado</th>
                <th style="width: 26%;">Observaciones / Permisos</th>
            </tr>
        </thead>
        <tbody>
            @forelse($registros as $row)
                @php
                    $badgeClass = 'badge-green';
                    $estadoLabel = 'PUNTUAL';

                    switch ($row['estado_key']) {
                        case 'ausente': $badgeClass = 'badge-red'; $estadoLabel = 'AUSENTE'; break;
                        case 'tarde': $badgeClass = 'badge-orange'; $estadoLabel = 'RETARDO'; break;
                        case 'tarde_con_permiso': $badgeClass = 'badge-orange'; $estadoLabel = 'RETARDO C/PERMISO'; break;
                        case 'sin_cierre': 
                            if ($row['salida_real']) { $badgeClass = 'badge-red'; $estadoLabel = 'CIERRE ATRASADO'; } 
                            else { $badgeClass = 'badge-yellow'; $estadoLabel = 'SIN SALIDA'; }
                            break;
                        case 'permiso': $badgeClass = 'badge-blue'; $estadoLabel = 'PERMISO APLICADO'; break;
                        case 'extra': $badgeClass = 'badge-purple'; $estadoLabel = 'TURNO EXTRA'; break;
                        case 'presente': $badgeClass = 'badge-green'; $estadoLabel = 'ASISTENCIA'; break;
                    }
                @endphp

                <tr>
                    <td>
                        <div style="font-weight: bold; color: #0f172a;">{{ $row['fecha']->format('d/m/Y') }}</div>
                        <div style="font-size: 8px; color: #94a3b8; text-transform: uppercase; margin-bottom: 3px;">
                            {{ $row['fecha']->locale('es')->isoFormat('dddd') }}
                        </div>

                        
                        @if($row['es_dia_remoto'])
                            <div style="font-size: 7px; font-weight: bold; color: #6b21a8; background-color: #f3e8ff; border: 1px solid #e9d5ff; padding: 2px 4px; border-radius: 3px; display: inline-block;">
                                REMOTO
                            </div>
                        @endif
                    </td>
                    <td>
                        <div style="font-weight: bold; color: #0f172a; font-size: 11px;">{{ $row['empleado']->nombres }} {{ $row['empleado']->apellidos }}</div>
                        <div style="font-size: 9px; color: #64748b;">{{ $row['sucursal']->nombre ?? '' }}</div>
                    </td>
                    <td style="text-align: center; font-family: monospace; color: #475569;">
                        {{ $row['horario_programado'] }}
                    </td>

                    {{-- NUEVO: DATO DE TOLERANCIA --}}
                    <td style="text-align: center; color: #475569; font-weight: bold;">
                        {{ $row['tolerancia'] > 0 ? $row['tolerancia'] . ' min' : '-' }}
                    </td>

                    <td style="text-align: center;">
                        <span style="color: #64748b;">E:</span> <strong>{{ $row['entrada_real'] ? $row['entrada_real']->format('H:i') : '--:--' }}</strong><br>
                        <span style="color: #64748b;">S:</span> <strong>{{ $row['salida_real'] ? $row['salida_real']->format('H:i') : '--:--' }}</strong>
                        @if($row['es_olvido_salida'])
                            <br><span style="color: #ef4444; font-size: 8px; font-weight: bold;">(OLVIDO)</span>
                        @endif
                    </td>
                    <td style="text-align: center;">
                        <span class="badge {{ $badgeClass }}">{{ $estadoLabel }}</span>
                    </td>
                    
                    <td>
                        @if($row['minutos_tarde'] > 0)
                            @php
                                $mTotal = round($row['minutos_tarde']);
                                $txtTiempo = $mTotal >= 60 ? floor($mTotal/60)."h ".($mTotal%60)."m" : "{$mTotal} min";
                            @endphp
                            <div style="color: #c2410c; font-weight: bold; font-size: 9px; margin-bottom: 4px;">
                                Retraso: {{ $txtTiempo }}
                            </div>
                        @endif

                        @if(!empty($row['permiso_info']))
                            <div style="border-left: 2px solid #3b82f6; padding-left: 5px; margin-top: 3px;">
                                <div style="font-weight: bold; color: #1d4ed8; font-size: 9px;">
                                    {{ $row['permiso_info']['tipo'] }}
                                    
                                    {{-- NUEVO: HORARIO DEL PERMISO EN EL PDF --}}
                                    @if(!empty($row['permiso_info']['hora_ini']) && !empty($row['permiso_info']['hora_fin']))
                                        <span style="background-color: #e0e7ff; color: #4338ca; padding: 1px 4px; border-radius: 3px; font-size: 8px; margin-left: 4px; border: 0.5px solid #c7d2fe;">
                                            {{ \Carbon\Carbon::parse($row['permiso_info']['hora_ini'])->format('H:i') }} a {{ \Carbon\Carbon::parse($row['permiso_info']['hora_fin'])->format('H:i') }}
                                        </span>
                                    @endif
                                </div>
                                
                                @if($row['permiso_info']['motivo'])
                                    <div style="font-style: italic; color: #475569; font-size: 8px; margin-top: 2px;">
                                        "{{ Str::limit($row['permiso_info']['motivo'], 80) }}"
                                    </div>
                                @endif
                            </div>
                        @endif

                        @if(!$row['minutos_tarde'] && empty($row['permiso_info']))
                            <span style="color: #cbd5e1; font-style: italic; font-size: 9px;">Ninguna</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="padding: 30px; text-align: center; color: #94a3b8; font-style: italic;">No se encontraron registros para los filtros seleccionados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="summary-wrapper">
        <table class="summary-table">
            <thead>
                <tr>
                    <th colspan="2">RESUMEN DEL PERIODO</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Total Registros Evaluados</td>
                    <td class="val-col">{{ $registros->count() }}</td>
                </tr>
                <tr>
                    <td style="color: #166534;">Asistencias Perfectas</td>
                    <td class="val-col" style="color: #166534;">{{ $registros->where('estado_key', 'presente')->count() }}</td>
                </tr>
                <tr>
                    <td style="color: #991b1b;">Ausencias Injustificadas</td>
                    <td class="val-col" style="color: #991b1b;">{{ $registros->where('estado_key', 'ausente')->count() }}</td>
                </tr>
                <tr>
                    <td style="color: #9a3412;">Llegadas Tarde</td>
                    <td class="val-col" style="color: #9a3412;">{{ $registros->whereIn('estado_key', ['tarde', 'tarde_con_permiso'])->count() }}</td>
                </tr>
                <tr>
                    <td style="color: #ef4444;">Faltas de Cierre (Olvidos)</td>
                    <td class="val-col" style="color: #ef4444;">{{ $registros->where('estado_key', 'sin_cierre')->count() }}</td>
                </tr>
                <tr>
                    <td style="color: #1d4ed8;">Permisos y Justificaciones</td>
                    <td class="val-col" style="color: #1d4ed8;">{{ $registros->filter(fn($r) => !empty($r['permiso_info']))->count() }}</td>
                </tr>
            </tbody>
        </table>
    </div>
@endsection