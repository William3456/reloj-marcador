<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f7f6; margin: 0; padding: 20px; }
        .container { max-width: 600px; background-color: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin: auto; }
        .header { border-bottom: 2px solid #3b82f6; padding-bottom: 10px; margin-bottom: 20px; }
        .header h2 { color: #1e293b; margin: 0; font-size: 20px; text-transform: uppercase; letter-spacing: 0.5px;}
        .content p { color: #475569; line-height: 1.6; }
        .details { background-color: #f8fafc; padding: 15px; border-radius: 6px; margin: 20px 0; border: 1px solid #e2e8f0; }
        .details strong { color: #334155; display: inline-block; width: 160px; } /* Ancho ajustado para que quepa "Contraseña temporal:" */
        .btn { display: inline-block; background-color: #3b82f6; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: bold; margin-top: 10px; }
        .warning-box { background-color: #fef08a; color: #854d0e; padding: 12px; border-radius: 6px; font-size: 13px; font-weight: bold; text-align: center; border: 1px solid #fde047; margin: 20px 0; }
        .footer { margin-top: 30px; font-size: 12px; color: #94a3b8; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 15px; }
        .mono-text { font-family: monospace; background: #e2e8f0; padding: 3px 6px; border-radius: 4px; letter-spacing: 1px; color: #0f172a; font-weight: bold; font-size: 14px;}
    </style>
</head>
<body>
    <div class="container">
        
        <div class="header">
            <h2>Acceso al sistema</h2>
        </div>
        
        <div class="content">
            <p>Hola,</p>
            <p>Se ha creado exitosamente tu acceso al <strong>sistema de marcaciones</strong>. A continuación, te proporcionamos tus credenciales de ingreso:</p>
            
            <div class="details">
                <p style="margin-top: 0;"><strong>Usuario:</strong> <span style="color: #1d4ed8; font-weight:bold;">{{ $email }}</span></p>
                <p style="margin-bottom: 0;"><strong>Contraseña temporal:</strong> <span class="mono-text">{{ $password }}</span></p>
            </div>

            <div class="warning-box">
                Por tu seguridad, inicia sesión y cambia esta contraseña inmediatamente.
            </div>
            
            <center>
                <a href="https://{{ $hostName }}/login" class="btn">Iniciar sesión ahora</a>
            </center>
        </div>
        
        <div class="footer">
            <p>Saludos,<br><strong style="color: #475569;">Sistema de marcaciones</strong></p>
            <p style="margin-top: 10px; font-size: 10px;">Este es un mensaje automático, por favor no respondas a este correo.</p>
        </div>
        
    </div>
</body>
</html>