<?php

namespace App\Filament\App\Resources\UserSubscriptionResource\Pages;

use App\Filament\App\Resources\UserSubscriptionResource;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use Filament\Pages\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Notifications\Notification;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Http;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Customer;
use Stripe\Product;
use Stripe\Price;
use Exception;


class UserSubscriptionPayment extends Page
{
    protected static string $resource = UserSubscriptionResource::class;
    protected static string $view = 'filament.pages.user-subscription-payment';

    public Subscription $subscription;
    public $bank;
    public $phone;
    public $identity;
    public $amount;
    public $otp = null;

    public function mount($record): void
    {
        $this->subscription = Subscription::findOrFail($record);
        $this->amount = $this->subscription->service_price_cents / 100; // Convertir a dólares

        // Capturar el resultado del pago
        if ($filter = request()->query('success') === "1") {
            Notification::make()
                ->title('Pago exitoso')
                ->body('Tu suscripción se activó correctamente.')
                ->success()
                ->send();
        } elseif ($filter = request()->query('success') === "0") {
            Notification::make()
                ->title('Pago cancelado')
                ->body('No se pudo completar el pago de tu suscripción. Inténtalo nuevamente.')
                ->danger()
                ->send();
        }
    }

    protected function createStripeSession()
    {
        Stripe::setApiKey(config('stripe.secret_key'));

        // 1. Crear un cliente en Stripe si no existe
        if (!$this->subscription->user->stripe_customer_id) {
            $customer = Customer::create([
                'email' => $this->subscription->user->email,
                'name' => $this->subscription->user->name,
            ]);

            $this->subscription->user->update(['stripe_customer_id' => $customer->id]);
        } else {
            $customer = Customer::retrieve($this->subscription->user->stripe_customer_id);
        }

        // 2. Crear un producto en Stripe si no existe
        if (!$this->subscription->service->stripe_product_id) {
            $product = Product::create([
                'name' => $this->subscription->service_name,
                'description' => $this->subscription->service_description,
            ]);

            $this->subscription->service->update(['stripe_product_id' => $product->id]);
        } else {
            $product = Product::retrieve($this->subscription->service->stripe_product_id);
        }

        // 3. Determinar el intervalo y el intervalo_count basados en frequency_days
        $frequency_days = $this->subscription->frequency_days;
        $interval = null;
        $interval_count = null;

        if ($frequency_days < 7) {
            // Frecuencia menor a 7 días: usa días
            $interval = 'day';
            $interval_count = $frequency_days;
        } elseif ($frequency_days % 7 === 0 && $frequency_days < 28) {
            // Múltiplo de 7 pero menor a 28 días: usa semanas
            $interval = 'week';
            $interval_count = $frequency_days / 7;
        } elseif ($frequency_days % 30 === 0) {
            // Múltiplo de 30 días: usa meses
            $interval = 'month';
            $interval_count = $frequency_days / 30;
        } elseif ($frequency_days % 365 === 0) {
            // Múltiplo de 365 días: usa años
            $interval = 'year';
            $interval_count = $frequency_days / 365;
        } else {
            // Si no es compatible con las reglas, arroja una excepción o maneja el caso
            throw new Exception('La frecuencia no es compatible con los intervalos permitidos por Stripe.');
        }

        // 4. Crear el precio en Stripe
        $price = Price::create([
            'product' => $product->id,
            'unit_amount' => $this->subscription->service_price_cents,
            'currency' => 'usd',
            'recurring' => [
                'interval' => $interval,
                'interval_count' => $interval_count,
            ],
        ]);

        // 5. Crear la Checkout Session
        $session = StripeSession::create([
            'customer' => $customer->id,
            'payment_method_types' => ['card'],
            'line_items' => [
                [
                    'price' => $price->id,
                    'quantity' => 1,
                ],
            ],
            'mode' => 'subscription',
            'success_url' => static::getResource()::getUrl('payment', [
                'record' => $this->subscription->id,
                'success' => true,
            ]),
            'cancel_url' => static::getResource()::getUrl('payment', [
                'record' => $this->subscription->id,
                'success' => false,
            ]),
        ]);


        Transaction::create([
            'from_type' => get_class($this->subscription->user), // Origen: Usuario
            'from_id' => $this->subscription->user->id,
            'to_type' => get_class($this->subscription->service->store), // Destino: Tienda del plan
            'to_id' => $this->subscription->service->store->id,
            'type' => TransactionTypeEnum::Subscription->value,
            'status' => TransactionStatusEnum::Pending->value,
            'date' => now(),
            'amount_cents' => $this->subscription->service_price_cents,
            'metadata' => [
                'checkout_session' => $session->toArray(),
            ],
            'subscription_id' => $this->subscription->id,
        ]);

        // 6. Redirigir a la URL de Stripe Checkout
        return redirect($session->url);
    }


    public function submitBolivaresPayment(array $data)
    {
        $this->bank = $data['bank'];
        $this->phone = $data['phone'];
        $this->identity = $data['identity'];

        try {
            $otpResponse = $this->generateOtp();

            if (!isset($otpResponse['success']) || !$otpResponse['success']) {
                Notification::make()
                    ->title('Error')
                    ->body('No se pudo generar el OTP. Intente nuevamente.')
                    ->danger()
                    ->send();
                return;
            }

            // OTP generado correctamente
            $this->otp = true;

            Notification::make()
                ->title('OTP Generado')
                ->body('Se ha enviado un código OTP a tu teléfono.')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error Interno')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }


    public function confirmOtp(array $data)
    {
        $this->otp = $data['otp'];

        try {
            $paymentResponse = $this->processImmediateDebit();

            if ($paymentResponse['code'] === 'ACCP') {
                Notification::make()
                    ->title('Pago Completado')
                    ->body('El pago se procesó exitosamente.')
                    ->success()
                    ->send();

                return redirect(static::getUrl(['record' => $this->subscription->id]));
            } else {
                Notification::make()
                    ->title('Error')
                    ->body('No se pudo completar el pago. Intente nuevamente.')
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error Interno')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function generateOtp()
    {
        $tokenAuthorization = hash_hmac(
            'sha256',
            "{$this->bank}{$this->amount}{$this->phone}{$this->identity}",
            config('banking.token_key')
        );

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => $tokenAuthorization,
            'Commerce' => config('banking.commerce_id'),
        ])->post(config('banking.otp_url'), [
                    'bank' => $this->bank,
                    'phone' => $this->phone,
                    'identity' => $this->identity,
                    'amount' => $this->amount,
                ]);

        return $response->json();
    }

    protected function processImmediateDebit()
    {
        $tokenAuthorization = hash_hmac(
            'sha256',
            "{$this->bank}{$this->identity}{$this->phone}{$this->amount}{$this->otp}",
            config('banking.token_key')
        );

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => $tokenAuthorization,
            'Commerce' => config('banking.commerce_id'),
        ])->post(config('banking.debit_url'), [
                    'bank' => $this->bank,
                    'identity' => $this->identity,
                    'phone' => $this->phone,
                    'amount' => $this->amount,
                    'otp' => $this->otp,
                ]);

        return $response->json();
    }

    protected function getActions(): array
    {
        return [
            Action::make('payInUSD')
                ->label('Pagar en USD')
                ->color('success')
                ->action(function () {
                    $this->createStripeSession();
                }),

            Action::make('payInBolivares')
                ->label('Pagar en Bolívares')
                ->color('warning')
                ->form([
                    TextInput::make('bank')->label('Banco')->required(),
                    TextInput::make('phone')->label('Teléfono')->required(),
                    TextInput::make('identity')->label('Cédula')->required(),
                    TextInput::make('amount')->label('Monto')->disabled()->default(fn() => $this->amount),
                ])
                ->action(function (array $data) {
                    $this->submitBolivaresPayment($data);
                }),

            Action::make('confirmOtp')
                ->label('Confirmar OTP')
                ->color('info')
                ->form([
                    TextInput::make('otp')->label('Código OTP')->required(),
                ])
                ->action(function (array $data) {
                    $this->confirmOtp($data);
                })
                ->visible(fn() => $this->otp !== null), // Solo visible cuando $otp no es null

        ];
    }
}
