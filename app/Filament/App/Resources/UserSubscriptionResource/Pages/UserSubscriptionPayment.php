<?php

namespace App\Filament\App\Resources\UserSubscriptionResource\Pages;

use App\Filament\App\Resources\UserSubscriptionResource;
use App\Models\Subscription;
use Filament\Pages\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Notifications\Notification;

class UserSubscriptionPayment extends Page
{
    protected static string $resource = UserSubscriptionResource::class;
    protected static string $view = 'filament.pages.user-subscription-payment';


    public Subscription $subscription;
    public $service;

    public function mount($record): void
    {
        // Cargar la suscripción y su servicio asociado
        $this->subscription = Subscription::with('service')->findOrFail($record);
        $this->service = $this->subscription->service;
    }

    public function processPayment()
    {
        // Lógica para iniciar el proceso de pago
        Notification::make()
            ->title('Pago iniciado')
            ->body('Se ha iniciado el proceso de pago para la suscripción.')
            ->send();
    }

    protected function getViewData(): array
    {
        return [
            'subscription' => $this->subscription,
            'service' => $this->service,
        ];
    }

    // Método para definir las acciones, como el botón de pago
    protected function getActions(): array
    {
        return [
            Action::make('pagar')
                ->label('Iniciar Pago')
                ->color('success')
                ->action('processPayment')
                ->button(),
        ];
    }
}





