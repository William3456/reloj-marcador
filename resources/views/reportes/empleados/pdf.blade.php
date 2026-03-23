@extends('layouts.pdf_layout')

@section('title', 'Directorio de Empleados')

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
        .badge-blue { background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
        .badge-gray { background-color: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }

        .summary-wrapper { width: 35%; float: right; margin-top: 10px; }
        .summary-table { width: 100%; border-collapse: collapse; font-size: 10px; }
        .summary-table th { background-color: #1e293b; color: white; padding: 8px; text-align: left; font-size: 11px; }
        .summary-table td { padding: 8px; border-bottom: 1px solid #e2e8f0; }
        .val-col { text-align: right; font-weight: bold; font-size: 12px; color: #0f172a;}
    </style>

    {{-- Encabezado Formal --}}
    <table class="header-table">
        <tr>
            <td style="width: 60%;">
                <h2 class="title">Directorio de Empleados</h2>
                <div class="meta-data">
                    <strong>Fecha de Emisión:</strong> {{ now()->format('d/m/Y H:i A') }}<br>
                    <strong>Sucursal Filtrada:</strong> {{ $filtros['sucursal'] }}
                </div>
            </td>
            <td style="width: 40%; text-align: right; vertical-align: bottom;">
                <div style="font-size: 11px; color: #64748b; background-color: #f8fafc; padding: 8px; border: 1px solid #e2e8f0; border-radius: 4px;">
                    <strong style="color: #0f172a;">Parámetros de Búsqueda:</strong><br>
                    Estado: {{ $filtros['estado'] }} | Acceso: {{ $filtros['login'] }}<br>
                    Puesto: {{ $filtros['puesto'] }}<br>
                    Depto: {{ $filtros['departamento'] }}
                </div>
            </td>
        </tr>
    </table>

    {{-- Tabla Principal --}}
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 7%;">Cód.</th>
                <th style="width: 20%;">Nombre Completo</th>
                <th style="width: 14%;">Sucursal</th>
                <th style="width: 17%;">Área / Puesto</th>
                <th style="width: 17%;">Horarios / Días</th>
                <th style="width: 11%; text-align: center;">Rol</th>
                <th style="width: 7%; text-align: center;">Acceso</th>
                <th style="width: 7%; text-align: center;">Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($empleados as $empleado)
                <tr>
                    <td style="font-weight: bold; color: #475569;">
                        {{ $empleado->cod_trabajador ?? 'N/A' }}
                    </td>
                    
                    <td>
                        <strong style="color: #0f172a; font-size: 11px;">{{ $empleado->nombres }} {{ $empleado->apellidos }}</strong>
                    </td>
                    
                    <td style="color: #475569;">
                        {{ $empleado->sucursal->nombre ?? 'Sin Asignar' }}
                    </td>
                    
                    <td>
                        <div style="font-weight: bold; color: #334155;">{{ $empleado->puesto->desc_puesto ?? 'N/A' }}</div>
                        <div style="font-size: 8px; color: #94a3b8; text-transform: uppercase; margin-top: 2px;">{{ $empleado->departamento->nombre_depto ?? 'N/A' }}</div>
                    </td>

                    {{-- CELDA DE HORARIOS --}}
                    <td>
                        @if($empleado->horarios && $empleado->horarios->isNotEmpty())
                            @php
                                $horariosUnicos = $empleado->horarios->unique('id');
                            @endphp
                            @foreach($horariosUnicos as $horario)
                                @php
                                    $dias = is_array($horario->dias) ? $horario->dias : json_decode($horario->dias, true);
                                    $diasStr = implode(', ', array_map(function($d) { 
                                        return mb_convert_case(mb_substr(trim($d), 0, 3, 'UTF-8'), MB_CASE_TITLE, 'UTF-8'); 
                                    }, $dias ?? []));
                                @endphp
                                <div style="margin-bottom: 4px; padding-bottom: 4px; border-bottom: 1px dotted #e2e8f0;">
                                    <div style="font-family: monospace; font-weight: bold; color: #0f172a;">
                                        {{ \Carbon\Carbon::parse($horario->hora_ini)->format('H:i') }} - {{ \Carbon\Carbon::parse($horario->hora_fin)->format('H:i') }}
                                    </div>
                                    <div style="font-size: 8px; color: #1d4ed8; font-weight: bold; text-transform: uppercase;">
                                        {{ $diasStr }}
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <span style="color: #94a3b8; font-style: italic; font-size: 9px;">Sin asignar</span>
                        @endif
                    </td>

                    <td style="text-align: center; color: #64748b; font-size: 9px;">
                        @if($empleado->login == 1 && $empleado->user && $empleado->user->rol)
                            {{ $empleado->user->rol->rol_name ?? $empleado->user->rol->name }}
                        @else
                            -
                        @endif
                    </td>

                    <td style="text-align: center;">
                        @if($empleado->login == 1)
                            <span class="badge badge-blue">SÍ</span>
                        @else
                            <span class="badge badge-gray">NO</span>
                        @endif
                    </td>

                    <td style="text-align: center;">
                        @if($empleado->estado == 1)
                            <span class="badge badge-green">ACTIVO</span>
                        @else
                            <span class="badge badge-red">INACTIVO</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="padding: 30px; text-align: center; color: #94a3b8; font-style: italic;">
                        No se encontraron empleados que coincidan con los filtros seleccionados.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Widget de Resumen --}}
    @if(count($empleados) > 0)
        <div class="summary-wrapper">
            <table class="summary-table">
                <thead>
                    <tr>
                        <th colspan="2">RESUMEN DEL DIRECTORIO</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Total Empleados Listados</td>
                        <td class="val-col">{{ count($empleados) }}</td>
                    </tr>
                    <tr>
                        <td style="color: #166534;">Empleados Activos</td>
                        <td class="val-col" style="color: #166534;">{{ $empleados->where('estado', 1)->count() }}</td>
                    </tr>
                    <tr>
                        <td style="color: #991b1b;">Empleados Inactivos</td>
                        <td class="val-col" style="color: #991b1b;">{{ $empleados->where('estado', 0)->count() }}</td>
                    </tr>
                    <tr>
                        <td style="color: #1d4ed8;">Con Acceso al Sistema</td>
                        <td class="val-col" style="color: #1d4ed8;">{{ $empleados->where('login', 1)->count() }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endif
@endsection