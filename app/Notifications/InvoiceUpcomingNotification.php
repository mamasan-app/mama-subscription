<?php
namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class InvoiceUpcomingNotification extends Notification
{
    protected $invoice;

    public function __construct($invoice)
    {
        $this->invoice = $invoice;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Factura próxima a vencerse')
            ->line('Tienes una factura próxima a vencerse.')
            ->line('Fecha límite: ' . now()->setTimestamp($this->invoice->due_date)->toDateTimeString())
            ->action('Ver detalles', url('/invoices/' . $this->invoice->id))
            ->line('Por favor realiza el pago antes de la fecha límite.');
    }
}
