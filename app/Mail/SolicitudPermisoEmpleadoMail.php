<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SolicitudPermisoEmpleadoMail extends Mailable
{
    use Queueable, SerializesModels;

    public $permiso;

    /**
     * Create a new message instance.
     */
    public function __construct($permiso)
    {
        $this->permiso = $permiso;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        // Determinamos el asunto según el origen y estado del permiso
        $estado = $this->permiso->estado_solicitud;
        $origen = $this->permiso->app_creacion;

        if ($origen == 1 || $estado == 0) {
            $asunto = 'Nuevo Permiso Asignado por Administración';
        } elseif ($estado == 1) {
            $asunto = 'Tu solicitud de permiso ha sido recibida - En Revisión';
        } elseif ($estado == 2) {
            $asunto = '¡Tu solicitud de permiso ha sido Aprobada!';
        } elseif ($estado == 3) {
            $asunto = 'Actualización sobre tu solicitud de permiso - Rechazada';
        } else {
            $asunto = 'Actualización de tu permiso'; // Fallback de seguridad
        }

        return new Envelope(
            subject: $asunto,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'app_marcacion.permisos.emails.empleado',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
