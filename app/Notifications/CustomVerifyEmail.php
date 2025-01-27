<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

class CustomVerifyEmail extends Notification
{
    use Queueable;

    protected string $panel;

    /**
     * Crear una nueva instancia de la notificación.
     */
    public function __construct(string $panel)
    {
        $this->panel = $panel;
    }

    /**
     * Canales de entrega de la notificación.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Representación del correo.
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Por favor, verifica tu correo electrónico')
            ->line('Haz clic en el botón de abajo para verificar tu correo.')
            ->action('Verificar correo', $this->verificationUrl($notifiable))
            ->line('Gracias por usar nuestra aplicación.');
    }

    /**
     * Generar la URL de verificación.
     */
    protected function verificationUrl($notifiable)
    {
        return URL::temporarySignedRoute(
            'verification.verify', // Nombre de la ruta
            Carbon::now()->addMinutes(config('auth.verification.expire', 60)), // Tiempo de expiración
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
                'panel' => $this->panel, // Agregamos el panel a la URL
            ]
        );
    }
}
