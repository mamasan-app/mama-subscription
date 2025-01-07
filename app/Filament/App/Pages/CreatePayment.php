<?php

namespace App\Filament\App\Pages;

use App\Models\Subscription;
use App\Enums\SubscriptionStatusEnum;
use Filament\Forms;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use App\Enums\PhonePrefixEnum;
use App\Enums\IdentityPrefixEnum;
use App\Enums\BankEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Grid;
use Filament\Pages\Actions\Action;
use Illuminate\Support\Facades\Http;
use Exception;

class CreatePayment extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Gestión de Pagos';
    protected static string $view = 'filament.pages.subscription-payment';

    protected static ?string $title = 'Crear Pagos';

    public $subscription_id;
    public $otp;
    public $bank;
    public $phone_prefix;
    public $phone_number;
    public $identity_prefix;
    public $identity_number;
    public $amount;
    public $showOtpFields = false; // Valor predeterminado corregido

    public function mount(): void
    {
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->subscription_id = null;
        $this->otp = null;
        $this->bank = null;
        $this->phone_prefix = null;
        $this->phone_number = null;
        $this->identity_prefix = null;
        $this->identity_number = null;
        $this->amount = null;
        $this->showOtpFields = false; // Aseguramos que siempre inicie en `false`
    }

    protected function getFormSchema(): array
    {
        return [
            Select::make('subscription_id')
                ->label('Suscripción')
                ->options(
                    Subscription::query()
                        ->where(function ($query) {
                            $query->where('status', SubscriptionStatusEnum::OnTrial->value)
                                ->orWhereHas('payments', function ($query) {
                                    $query->whereNotNull('subscription_id');
                                });
                        })
                        ->whereNull('stripe_subscription_id')
                        ->pluck('service_name', 'id')
                        ->toArray()
                )
                ->required()
                ->reactive() // Reactividad habilitada
                ->afterStateUpdated(function ($state, $set) {
                    // Se ejecuta cada vez que cambia la selección
                    $subscription = Subscription::find($state);

                    if ($subscription && $subscription->status === SubscriptionStatusEnum::OnTrial->value) {
                        $set('showOtpFields', false); // Ocultar OTP si está en periodo de prueba
                    } else {
                        $set('showOtpFields', true); // Mostrar OTP si requiere pago
                    }
                }),

            Select::make('bank')
                ->label('Banco')
                ->options(
                    collect(BankEnum::cases())
                        ->mapWithKeys(fn($bank) => [$bank->code() => $bank->getLabel()])
                        ->toArray()
                )
                ->required()
                ->hidden(fn($get) => $get('showOtpFields')), // Reactivo a la visibilidad de `showOtpFields`

            Grid::make(2)
                ->schema([
                    Select::make('phone_prefix')
                        ->label('Prefijo Telefónico')
                        ->options(
                            collect(PhonePrefixEnum::cases())
                                ->mapWithKeys(fn($prefix) => [$prefix->value => $prefix->getLabel()])
                                ->toArray()
                        )
                        ->required()
                        ->hidden(fn($get) => $get('showOtpFields')),
                    TextInput::make('phone_number')
                        ->label('Número Telefónico')
                        ->numeric()
                        ->minLength(7)
                        ->maxLength(7)
                        ->required()
                        ->hidden(fn($get) => $get('showOtpFields')),
                ]),

            Grid::make(2)
                ->schema([
                    Select::make('identity_prefix')
                        ->label('Tipo de Cédula')
                        ->options(
                            collect(IdentityPrefixEnum::cases())
                                ->mapWithKeys(fn($prefix) => [$prefix->value => $prefix->getLabel()])
                                ->toArray()
                        )
                        ->required()
                        ->hidden(fn($get) => $get('showOtpFields')),
                    TextInput::make('identity_number')
                        ->label('Número de Cédula')
                        ->numeric()
                        ->minLength(6)
                        ->maxLength(20)
                        ->required()
                        ->hidden(fn($get) => $get('showOtpFields')),
                ]),

            TextInput::make('amount')
                ->label('Monto')
                ->disabled()
                ->default(fn() => $this->amount)
                ->hidden(fn($get) => $get('showOtpFields')),
        ];
    }

    protected function getActions(): array
    {
        return [
            Action::make('submit')
                ->label('Procesar Pago')
                ->color('primary')
                ->action(function (array $data) {
                    if ($this->showOtpFields) {
                        $this->submitBolivaresPayment($data);
                    } else {
                        Notification::make()
                            ->title('Información')
                            ->body('Este es un período de prueba. No se requiere OTP.')
                            ->info()
                            ->send();
                    }
                }),
        ];
    }

    public function submitBolivaresPayment(array $data)
    {
        $this->bank = $data['bank'];
        $this->phone = $data['phone_prefix'] . $data['phone_number'];
        $this->identity = $data['identity_prefix'] . $data['identity_number'];

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

            $this->otp = true;

            Notification::make()
                ->title('OTP Generado')
                ->body('Se ha enviado un código OTP a tu teléfono.')
                ->success()
                ->send();

            $this->openOtpModal();
        } catch (Exception $e) {
            Notification::make()
                ->title('Error Interno')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function generateOtp()
    {
        $bank = (string) $this->bank;
        $amount = (string) number_format((float) $this->amount, 2, '.', '');
        $phone = (string) $this->phone;
        $identity = (string) $this->identity;

        $stringToHash = "{$bank}{$amount}{$phone}{$identity}";
        $tokenAuthorization = hash_hmac(
            'sha256',
            $stringToHash,
            config('banking.commerce_id')
        );

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => $tokenAuthorization,
            'Commerce' => config('banking.commerce_id'),
        ])->post(config('banking.otp_url'), [
                    'Banco' => $bank,
                    'Monto' => $amount,
                    'Telefono' => $phone,
                    'Cedula' => $identity,
                ]);

        return $response->json();
    }

    public function openOtpModal()
    {
        $this->dispatchBrowserEvent('open-otp-modal');
    }
}
