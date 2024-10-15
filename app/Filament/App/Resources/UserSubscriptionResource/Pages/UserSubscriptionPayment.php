<?php

namespace App\Filament\App\Resources\UserSubscriptionResource\Pages;

use App\Filament\App\Resources\UserSubscriptionResource;
use App\Models\Subscription;
use Filament\Pages\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Notifications\Notification;
use Filament\Forms; // Importa Filament Forms para que los componentes funcionen correctamente.
use Filament\Forms\Components\Radio; // Importa Radio directamente si lo necesitas.
use Filament\Forms\Components\TextInput;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;

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

    public function processPayment($paymentMethod)
    {
        if ($paymentMethod === 'usd') {
            return $this->createStripeSession(); // Crear sesión de Stripe
        }

        Notification::make()
            ->title('Pago iniciado')
            ->body('Se ha iniciado el proceso de pago en bolívares.')
            ->send();
    }

    protected function createStripeSession()
    {
        Stripe::setApiKey(config('stripe.secret_key'));

        // Crear sesión de Stripe para el pago
        $session = StripeSession::create([
            'payment_method_types' => ['card'],
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => $this->service->name,
                        ],
                        'unit_amount' => $this->subscription->formattedPriceInCents(),
                    ],
                    'quantity' => 1,
                ]
            ],
            'mode' => 'payment',
            'success_url' => route('payment.success', ['subscription' => $this->subscription->id]),
            'cancel_url' => route('payment.cancel'),
        ]);

        // Redirigir a la URL de Stripe
        return redirect($session->url);
    }

    protected function getActions(): array
    {
        return [
            Action::make('iniciarPago')
                ->label('Iniciar Pago')
                ->color('success')
                ->modalHeading('Selecciona el Método de Pago')
                ->modalSubheading('Elige la forma en que deseas pagar')
                ->modalButton('Confirmar Pago')
                ->action(function (array $data) {
                    $this->processPayment($data['payment_method']);
                })
                ->form([
                    Radio::make('payment_method')
                        ->label('Elige tu forma de pago')
                        ->options([
                            'usd' => 'USD',
                            'bs' => 'Bolívares (Bs)',
                        ])
                        ->required(),
                ])
                ->button(),
        ];
    }

    protected function getViewData(): array
    {
        return [
            'subscription' => $this->subscription,
            'service' => $this->service,
        ];
    }

}





