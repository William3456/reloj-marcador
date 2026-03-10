<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SolicitudPermisoAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    // 1. Las variables DEBEN ser públicas
    public $permiso;
    public $empleado;

    // 2. El constructor debe recibir ambos parámetros
    public function __construct($permiso, $empleado)
    {
        $this->permiso = $permiso;
        $this->empleado = $empleado;
    }

    public function envelope(): Envelope
    {
        
        if ($this->permiso->app_creacion == 1) {
            // Creado desde el panel
            $asunto = 'Notificación: Permiso asignado a ' . $this->empleado->nombres;
        } else {
            // Creado desde la App (Pendiente)
            $asunto = 'Requiere Revisión: Solicitud de ' . $this->empleado->nombres;
        }

        return new Envelope(
            subject: $asunto,
        );
    }

    public function content(): Content
    {
        return new Content(
            // 3. Ponemos la ruta exacta de tu vista (basado en el error que me mandaste)
            view: 'app_marcacion.permisos.emails.admins',
            
            // 4. MAGIA AQUÍ: Inyectamos explícitamente las variables a la vista
            with: [
                'permiso' => $this->permiso,
                'empleado' => $this->empleado,
            ]
        );
    }
}