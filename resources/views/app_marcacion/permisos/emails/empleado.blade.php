<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f7f6; margin: 0; padding: 20px; }
        .container { max-width: 600px; background-color: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin: auto; }
        .header { border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 20px; }
        .header h2 { color: #1e293b; margin: 0; }
        .content p { color: #475569; line-height: 1.6; }
        .details { background-color: #f8fafc; padding: 15px; border-radius: 6px; margin: 20px 0; border: 1px solid #e2e8f0; }
        .details strong { color: #334155; display: inline-block; width: 120px; }
        .footer { margin-top: 30px; font-size: 12px; color: #94a3b8; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 15px; }
    </style>
</head>
<body>
    @php
        // Lógica de textos dinámicos según el estado y origen
        $estado = $permiso->estado_solicitud;
        $origen = $permiso->app_creacion;

        if ($origen == 1 || $estado == 0) {
            $badge = '<span style="background-color: #e0e7ff; color: #4338ca; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: bold;">Asignado por admin</span>';
            $titulo = "Nuevo permiso asignado";
            $mensaje = "Se te ha asignado un nuevo permiso directamente desde el área de administración.";
        } elseif ($estado == 1) {
            $badge = '<span style="background-color: #fef08a; color: #854d0e; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: bold;">En revisión</span>';
            $titulo = "Confirmación de solicitud";
            $mensaje = "Hemos recibido correctamente tu solicitud de permiso. Actualmente se encuentra en revisión.";
        } elseif ($estado == 2) {
            $badge = '<span style="background-color: #dcfce3; color: #166534; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: bold;">Aprobada</span>';
            $titulo = "Solicitud aprobada";
            $mensaje = "Tu solicitud de permiso ha sido revisada y <strong>aprobada</strong> exitosamente.";
        } elseif ($estado == 3) {
            $badge = '<span style="background-color: #fee2e2; color: #991b1b; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: bold;">Rechazada</span>';
            $titulo = "Solicitud rechazada";
            $mensaje = "Tu solicitud de permiso ha sido revisada y lamentablemente <strong>no pudo ser aprobada</strong> en esta ocasión.";
        }
    @endphp

    <div class="container">
        <div class="header">
            <h2>{{ $titulo }}</h2>
        </div>
        <div class="content">
            <p>Hola <strong>{{ $permiso->empleado->nombres }}</strong>,</p>
            
            <p>{!! $mensaje !!} Estado actual: {!! $badge !!}</p>
            
            <div class="details">
                <p><strong>Tipo de permiso:</strong> {{ $permiso->tipoPermiso->nombre ?? 'General' }}</p>
                <p><strong>Motivo:</strong> {{ $permiso->motivo }}</p>
                @if($permiso->fecha_inicio)
                    <p><strong>Fechas:</strong> {{ \Carbon\Carbon::parse($permiso->fecha_inicio)->format('d/m/Y') }} 
                    @if($permiso->fecha_inicio != $permiso->fecha_fin)
                        al {{ \Carbon\Carbon::parse($permiso->fecha_fin)->format('d/m/Y') }}
                    @endif
                    </p>
                @endif
                
                {{-- Horario del permiso --}}
                @if($permiso->hora_ini && $permiso->hora_fin)
                    <p><strong>Horario:</strong> {{ \Carbon\Carbon::parse($permiso->hora_ini)->format('H:i') }} a {{ \Carbon\Carbon::parse($permiso->hora_fin)->format('H:i') }}</p>
                @endif
            </div>

            <p>Puedes consultar todos los detalles y el historial ingresando a la App de marcaciones.</p>
        </div>
        <div class="footer">
            <p>Este es un correo automático del sistema de control de asistencia. Por favor no respondas a este mensaje.</p>
        </div>
    </div>
</body>
</html>