<?php

namespace App\Filament\App\Pages;

use App\Models\Subscription;
use Filament\Forms;
use Filament\Pages\Page;
use Filament\Notifications\Notification;

class CreatePayment extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Gestión de Pagos';
    protected static string $view = 'filament.pages.subscription-payment'; // Vista personalizada

    protected static ?string $title = 'Crear Pagos';

    public $subscription_id;
    public $otp;
    public $bank;
    public $phone_prefix;
    public $phone_number;
    public $identity_prefix;
    public $identity_number;

    public $showOtpFields = false; // Controla la visibilidad de los campos de OTP

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
        $this->showOtpFields = false;
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Select::make('subscription_id')
                ->label('Suscripción')
                ->options(
                    Subscription::query()
                        ->where(fn($query) => $query->where('status', 'trial')
                            ->orWhereNotNull('payments')
                            ->whereNull('stripe_subscription_id'))
                        ->pluck('service_name', 'id')
                        ->toArray()
                )
                ->required()
                ->reactive()
                ->afterStateUpdated(function ($state, $set) {
                    $subscription = Subscription::find($state);

                    if ($subscription && $subscription->status === 'trial') {
                        $set('showOtpFields', false);
                    } else {
                        $set('showOtpFields', true);
                    }
                }),

            Forms\Components\TextInput::make('bank')
                ->label('Banco')
                ->required()
                ->hidden(fn($get) => !$get('showOtpFields')),

            Forms\Components\TextInput::make('phone_prefix')
                ->label('Prefijo Telefónico')
                ->required()
                ->hidden(fn($get) => !$get('showOtpFields')),

            Forms\Components\TextInput::make('phone_number')
                ->label('Número Telefónico')
                ->required()
                ->hidden(fn($get) => !$get('showOtpFields')),

            Forms\Components\TextInput::make('identity_prefix')
                ->label('Tipo de Cédula')
                ->required()
                ->hidden(fn($get) => !$get('showOtpFields')),

            Forms\Components\TextInput::make('identity_number')
                ->label('Número de Cédula')
                ->required()
                ->hidden(fn($get) => !$get('showOtpFields')),

            Forms\Components\TextInput::make('otp')
                ->label('Código OTP')
                ->required()
                ->hidden(fn($get) => !$get('showOtpFields')),
        ];
    }

    public function submit()
    {
        $subscription = Subscription::find($this->subscription_id);

        if (!$subscription) {
            Notification::make()
                ->title('Error')
                ->body('La suscripción seleccionada no existe.')
                ->danger()
                ->send();
            return;
        }

        if ($subscription->status === 'trial') {
            Notification::make()
                ->title('Suscripción en Prueba')
                ->body('Esta suscripción está en período de prueba.')
                ->success()
                ->send();
            return;
        }

        // Lógica para procesar el OTP
        try {
            $this->processOtp();
            Notification::make()
                ->title('Pago Procesado')
                ->body('El pago se procesó exitosamente.')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }

        $this->resetForm();
    }

    protected function processOtp()
    {
        // Implementar lógica de envío/confirmación de OTP
        if (!$this->otp) {
            throw new \Exception('El código OTP es requerido.');
        }

        // Procesar pago con OTP
        // Aquí iría la integración con el sistema bancario
    }
}
