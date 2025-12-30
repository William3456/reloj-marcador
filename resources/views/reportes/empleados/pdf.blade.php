@extends('layouts.pdf_layout')

@section('title', 'Reporte de empleados PDF')

@section('content')
    <div style="margin-bottom: 20px;">
        <h2 class="text-center">Reporte de Empleados</h2>
        <p><strong>Se muestran {{ count($empleados) }} registros.</p></strong>
    </div>

    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Nombre Completo</th>
                <th>Sucursal</th>
                <th>Puesto</th>
                <th>Depto.</th>
                <th>Estado</th>
                <th>Login</th>
            </tr>
        </thead>
        <tbody>
            @foreach($empleados as $empleado)
                <tr>
                    <td>{{ $empleado->cod_trabajador }}</td>
                    <td>{{ $empleado->nombres }} {{ $empleado->apellidos }}</td>
                    <td>{{ $empleado->sucursal->nombre ?? 'N/A' }}</td>
                    <td>{{ $empleado->puesto->desc_puesto ?? 'N/A' }}</td>
                    <td>{{ $empleado->departamento->nombre_depto ?? 'N/A' }}</td>
                    <td>
                        {{ $empleado->estado == 1 ? 'Activo' : 'Inactivo' }}
                    </td>
                    <td style="text-align: center">
                        {{ $empleado->login == 1 ? 'Sí' : 'No' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection