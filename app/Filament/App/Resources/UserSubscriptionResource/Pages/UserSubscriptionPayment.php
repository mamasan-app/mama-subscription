<?php

namespace App\Filament\App\Resources\UserSubscriptionResource\Pages;

use App\Filament\App\Resources\UserSubscriptionResource;
use App\Models\Subscription;
use Filament\Pages\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Notifications\Notification;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Http;
use Exception;
use App\Enums\BankEnum;
use Filament\Forms\Components\Select;
use App\Services\StripeService;

class UserSubscriptionPayment extends Page
{
    protected static string $resource = UserSubscriptionResource::class;
    protected static string $view = 'filament.pages.user-subscription-payment';

    public function getTitle(): string
    {
        return 'Primer Pago';
    }

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

            redirect(UserSubscriptionResource::getUrl('index'));

        } elseif ($filter = request()->query('success') === "0") {
            Notification::make()
                ->title('Pago cancelado')
                ->body('No se pudo completar el pago de tu suscripción. Inténtalo nuevamente.')
                ->danger()
                ->send();
        }
    }

    public function createStripeSession(StripeService $stripeService)
    {
        // Obtener o crear el cliente en Stripe
        $customer = $stripeService->getOrCreateCustomer($this->subscription->user);

        // Obtener o crear el producto en Stripe
        $product = $stripeService->getOrCreateProduct($this->subscription->service);

        // Determinar el intervalo y el intervalo_count
        $frequency_days = $this->subscription->frequency_days;
        [$interval, $intervalCount] = $this->getIntervalDetails($frequency_days);

        // Crear el precio en Stripe
        $price = $stripeService->createPrice(
            $product,
            $this->subscription->service_price_cents,
            $interval,
            $intervalCount,
            $this->subscription->service_grace_period,
        );

        // Crear la sesión de Stripe Checkout
        $session = $stripeService->createCheckoutSession(
            $customer,
            $price,
            static::getResource()::getUrl('payment', [
                'record' => $this->subscription->id,
                'success' => true,
            ]),
            static::getResource()::getUrl('payment', [
                'record' => $this->subscription->id,
                'success' => false,
            ]),
            [
                'payment_id' => $this->subscription->id,
                'subscription_id' => $this->subscription->id,
            ]
        );

        if (isset($session->subscription)) {
            $this->subscription->update([
                'stripe_subscription_id' => $session->subscription,
            ]);
        }

        return redirect($session->url);
    }

    private function getIntervalDetails($frequency_days)
    {
        if ($frequency_days < 7) {
            return ['day', $frequency_days];
        } elseif ($frequency_days % 7 === 0 && $frequency_days < 28) {
            return ['week', $frequency_days / 7];
        } elseif ($frequency_days % 30 === 0) {
            return ['month', $frequency_days / 30];
        } elseif ($frequency_days % 365 === 0) {
            return ['year', $frequency_days / 365];
        }

        throw new Exception('La frecuencia no es compatible con los intervalos permitidos por Stripe.');
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

        dd($response->json());

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
                    $stripeService = app(StripeService::class); // Inyectar StripeService
                    $this->createStripeSession($stripeService);
                }),

            Action::make('payInBolivares')
                ->label('Pagar en Bolívares')
                ->color('warning')
                ->form([
                    Select::make('bank')
                        ->label('Banco')
                        ->options(
                            collect(BankEnum::cases())
                                ->mapWithKeys(fn($bank) => [$bank->value => $bank->getLabel()])
                                ->toArray()
                        )
                        ->required(),
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
