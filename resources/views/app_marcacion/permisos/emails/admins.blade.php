<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f7f6; margin: 0; padding: 20px; }
        .container { max-width: 600px; background-color: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin: auto; }
        .header { border-bottom: 2px solid #3b82f6; padding-bottom: 10px; margin-bottom: 20px; }
        .header.panel { border-bottom: 2px solid #6366f1; } /* Color índigo si es del panel */
        .header h2 { color: #1e293b; margin: 0; }
        .content p { color: #475569; line-height: 1.6; }
        .details { background-color: #f8fafc; padding: 15px; border-radius: 6px; margin: 20px 0; border: 1px solid #e2e8f0; }
        .details strong { color: #334155; display: inline-block; width: 120px; }
        .btn { display: inline-block; background-color: #3b82f6; color: #ffffff; text-decoration: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; margin-top: 15px; }
        .btn.panel { background-color: #6366f1; } /* Botón índigo si es del panel */
        .footer { margin-top: 30px; font-size: 12px; color: #94a3b8; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 15px; }
    </style>
</head>
<body>
    @php
        // 🌟 LÓGICA DE TEXTOS DINÁMICOS PARA EL ADMINISTRADOR
        $origen = $permiso->app_creacion;

        if ($origen == 1) {
            $claseCss = 'panel';
            $titulo = "Notificación de Nuevo Permiso";
            $mensaje = "Se ha registrado y asignado un nuevo permiso en el sistema para el empleado <strong>{$empleado->nombres} {$empleado->apellidos}</strong>.";
            $textoBoton = "Ver Permiso en el Directorio";
        } else {
            $claseCss = '';
            $titulo = "Nueva Solicitud Pendiente";
            $mensaje = "El empleado <strong>{$empleado->nombres} {$empleado->apellidos}</strong> ha registrado una nueva solicitud de permiso desde la App que requiere tu revisión.";
            $textoBoton = "Revisar Solicitud en el Sistema";
        }
    @endphp

    <div class="container">
        <div class="header {{ $claseCss }}">
            <h2>{{ $titulo }}</h2>
        </div>
        <div class="content">
            <p>Hola,</p>
            <p>{!! $mensaje !!}</p>
            
            <div class="details">
                <p><strong>Sucursal:</strong> {{ $empleado->sucursal->nombre ?? 'N/A' }}</p>
                <p><strong>Cód. Empleado:</strong> {{ $empleado->cod_trabajador }}</p>
                <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 10px 0;">
                <p><strong>Tipo Permiso:</strong> {{ $permiso->tipoPermiso->nombre ?? 'General' }}</p>
                <p><strong>Motivo:</strong> {{ $permiso->motivo }}</p>
                
                {{-- Mostramos los datos condicionales si existen --}}
                @if($permiso->fecha_inicio)
                    <p><strong>Vigencia:</strong> {{ \Carbon\Carbon::parse($permiso->fecha_inicio)->format('d/m/Y') }} 
                    @if($permiso->fecha_inicio != $permiso->fecha_fin)
                        al {{ \Carbon\Carbon::parse($permiso->fecha_fin)->format('d/m/Y') }}
                    @endif
                    </p>
                @endif

                {{--  NUEVO: HORARIO DEL PERMISO --}}
                @if($permiso->hora_ini && $permiso->hora_fin)
                    <p><strong>Horario:</strong> {{ \Carbon\Carbon::parse($permiso->hora_ini)->format('H:i') }} a {{ \Carbon\Carbon::parse($permiso->hora_fin)->format('H:i') }}</p>
                @endif

                @if($permiso->valor)
                    <p><strong>Tiempo:</strong> {{ $permiso->valor }} minutos</p>
                @endif
                @if($permiso->cantidad_mts)
                    <p><strong>Rango GPS:</strong> {{ $permiso->cantidad_mts }} mts</p>
                @endif

                <p><strong>Fecha Registro:</strong> {{ $permiso->created_at->format('d/m/Y h:i A') }}</p>
            </div>

            <center>
                <a href="{{ route('permisos.index') }}" class="btn {{ $claseCss }}">{{ $textoBoton }}</a>
            </center>
        </div>
        <div class="footer">
            <p>Sistema de Control de Asistencia</p>
        </div>
    </div>
</body>
</html>