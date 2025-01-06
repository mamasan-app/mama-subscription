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
use Filament\Forms\Components\Modal;
use Filament\Pages\Actions\Action;
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
                ->reactive()
                ->afterStateUpdated(function ($state, $set) {
                    $subscription = Subscription::find($state);

                    if ($subscription && $subscription->status === SubscriptionStatusEnum::OnTrial->value) {
                        $set('showOtpFields', false);
                    } else {
                        $set('showOtpFields', true);
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
                ->hidden(fn($get) => $get('showOtpFields')),

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
                ->action(function () {
                    $this->openOtpModal();
                }),
        ];
    }

    public function openOtpModal(): void
    {
        $this->dispatchBrowserEvent('open-otp-modal');
    }

    public function confirmOtp(array $data): void
    {
        $this->otp = $data['otp'];

        try {
            $this->processOtp();
            Notification::make()
                ->title('Pago Completado')
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
    }

    protected function processOtp(): void
    {
        if (!$this->otp) {
            throw new Exception('El código OTP es requerido.');
        }

        // Implementa la lógica para procesar el OTP aquí.
    }
}
