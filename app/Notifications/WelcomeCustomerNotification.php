<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

class WelcomeCustomerNotification extends Notification
{
    use Queueable;

    /**
     * URL del enlace mágico para inicio de sesión.
     *
     * @var string
     */
    protected $magicLinkUrl;

    /**
     * URL del enlace de verificación de correo.
     *
     * @var string
     */
    protected $verificationUrl;

    /**
     * Nombre de la tienda.
     *
     * @var string
     */
    protected $storeName;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $magicLinkUrl, string $storeName, $notifiable)
    {
        $this->magicLinkUrl = $magicLinkUrl;
        $this->storeName = $storeName;
        $this->verificationUrl = $this->generateVerificationUrl($notifiable);
    }

    /**
     * Canales de entrega de la notificación.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Genera la URL de verificación de correo.
     */
    protected function generateVerificationUrl($notifiable)
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(config('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
                'panel' => 'app', // Asegurar que el usuario vaya al panel app
            ]
        );
    }


    /**
     * Representación del correo.
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('¡Bienvenido al Sistema de ' . $this->storeName . '!')
            ->greeting('¡Hola ' . $notifiable->first_name . '!')
            ->line('Gracias por unirte al sistema de ' . $this->storeName . '. Estamos emocionados de tenerte con nosotros.')
            ->action('Acceder a tu Cuenta', $this->magicLinkUrl)
            ->line('Antes de continuar, por favor verifica tu dirección de correo electrónico.')
            ->action('Verificar correo', $this->verificationUrl)
            ->line('Haz clic en el botón para verificar tu cuenta y acceder sin problemas.')
            ->line('Si tienes alguna duda, no dudes en contactarnos.')
            ->salutation('Atentamente, el equipo de ' . $this->storeName . '.');
    }

    /**
     * Representación en array.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'magic_link_url' => $this->magicLinkUrl,
            'verification_url' => $this->verificationUrl,
            'store_name' => $this->storeName,
        ];
    }
}
