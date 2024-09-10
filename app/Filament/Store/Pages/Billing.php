<?php

declare(strict_types=1);

namespace App\Filament\Store\Pages;

use App\Enums\PaymentTypeEnum;
use App\Enums\SubscriptionStatusEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Filament\Forms\PaymentMethodCustomerForm;
use App\Models\ExchangeRate;
use App\Models\PaymentMethod;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\Store;
use App\Models\Transaction;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Log;

class Billing extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $title = 'Subscripción';

    protected static ?string $navigationIcon = 'icon-credit-card-pos';

    protected static string $view = 'filament.store.pages.billing';

    protected static string $layout = 'filament-panels::components.layout.base';

    protected static bool $shouldRegisterNavigation = false;

    /** @var Collection<int, Service>|null */
    public ?Collection $services;

    public Store $store;

    public static function canAccess(): bool
    {
        if (!auth()->user()) {
            return false;
        }

        return auth()->user()->can('manageSubscription', Store::class);
    }

    public function mount(): void
    {
        /** @var Store $store */
        $store = Filament::getTenant();
        $this->store = $store;

        // Obtener servicios disponibles para la tienda
        $this->services = Service::query()
            ->where('published', true)
            ->orderBy('name') // Ordenar por nombre de servicio
            ->get();
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Historial de Transacciones')
            ->defaultSort('date', 'desc')
            ->query(
                Transaction::query()
                    ->whereHasMorph('from', Store::class, fn(Builder $query) => $query->where('id', $this->store->id))
                    ->whereNull('to_id')
                    ->where('type', TransactionTypeEnum::Subscription)
            )
            ->columns([
                Tables\Columns\TextColumn::make('status')
                    ->label('Estatus')
                    ->badge(),

                Tables\Columns\TextColumn::make('metadata.payment_method')
                    ->label('Tipo de pago')
                    ->formatStateUsing(fn(string $state) => PaymentTypeEnum::from($state)->getLabel()),

                Tables\Columns\TextColumn::make('date')
                    ->label('Fecha')
                    ->tooltip('Es la fecha de cuando se realizó esta operación.')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount_cents')
                    ->label('Monto ($)')
                    ->money('USD', 100)
                    ->sortable(),
            ]);
    }

    public function subscribeAction(): Action
    {
        return Action::make('subscribe')
            ->label('Subscríbete')
            ->extraAttributes([
                'class' => 'w-full',
            ])
            ->outlined(fn(array $arguments) => !$arguments['featured'])
            ->action(function (array $arguments) {
                /** @var int $serviceId */
                $serviceId = $arguments['serviceId'];

                /** @var string $serviceName */
                $serviceName = $arguments['serviceName'];

                if ($this->store->subscription && $this->store->subscription->status !== SubscriptionStatusEnum::Cancelled) {
                    Log::error(
                        '[Billing] User tried to activate a new subscription, but the current one is not cancelled by the store.',
                        [
                            'store_id' => $this->store->id,
                            'store_name' => $this->store->name,
                            'subscription_status' => $this->store->subscription->status,
                        ]
                    );
                    Notification::make('subscriptionIsNotCancelled')
                        ->danger()
                        ->title('Hubo un error')
                        ->body('Para activar una nueva subscripción, primero se debe cancelar la subscripción actual')
                        ->send();

                    return;
                }

                if ($this->store->subscription) {
                    $this->store->subscription->update([
                        'service_id' => $serviceId,
                        'renews_at' => now(),
                        'status' => SubscriptionStatusEnum::Active,
                        'ends_at' => null,
                        'expires_at' => now()->addDays(3),
                    ]);

                    Notification::make('subscriptionUpdated')
                        ->success()
                        ->title('Subscripción actualizada')
                        ->body("Tu subscripción ha sido actualizada al servicio \"$serviceName\" satisfactoriamente.")
                        ->send();
                } else {
                    $this->store->subscription()->create([
                        'service_id' => $serviceId,
                        'status' => SubscriptionStatusEnum::OnTrial,
                        'trial_ends_at' => now()->addDays(30),
                        'expires_at' => now()->addDays(33),
                    ]);

                    Notification::make('subscriptionCreated')
                        ->success()
                        ->title('Afiliación exitosa')
                        ->body("Te has subscrito al servicio \"$serviceName\" satisfactoriamente.")
                        ->send();
                }

                $this->redirect(Filament::getUrl($this->store));
            });
    }

    public function reportPaymentAction(): Action
    {
        return CreateAction::make('reportPayment')
            ->label('Reportar Pago')
            ->model(Transaction::class)
            ->modelLabel('Pago')
            ->successNotificationTitle('Su pago ha sido reportado')
            ->form([
                Forms\Components\Select::make('payment_method_id')
                    ->required()
                    ->label('Tipo de pago')
                    ->native(false)
                    ->options(
                        fn() => PaymentMethod::query()
                            ->where('enabled', true)
                            ->get()
                            ->mapWithKeys(fn(PaymentMethod $paymentMethod) => [$paymentMethod->id => $paymentMethod->type->getLabel()])
                    )
                    ->live(),

                Forms\Components\DatePicker::make('date')
                    ->label('Fecha de pago')
                    ->required()
                    ->default(now('America/Caracas')),

                Forms\Components\Fieldset::make('Información de pago')
                    ->visible(fn(Forms\Get $get) => $get('payment_method_id'))
                    ->schema([
                        Forms\Components\Placeholder::make('info')
                            ->label('')
                            ->reactive()
                            ->content(function (Forms\Get $get) {
                                /** @var int|null $paymentMethodId */
                                $paymentMethodId = $get('payment_method_id');

                                if (!$paymentMethodId) {
                                    return '';
                                }

                                /** @var PaymentMethod $paymentMethod */
                                $paymentMethod = PaymentMethod::find($paymentMethodId);
                                /** @var array<string, string> $metadata */
                                $metadata = $paymentMethod->metadata;
                                $bank = '';

                                if (
                                    $paymentMethod->type === PaymentTypeEnum::MobilePaymentBs
                                    || $paymentMethod->type === PaymentTypeEnum::WireTransferBs
                                ) {
                                    $bank = BankEnum::from($metadata['bank']);
                                    $bank = $bank->getLabel();
                                }

                                /** @var Subscription $subscription */
                                $subscription = $this->store->subscription;

                                $exchangeRate = ExchangeRate::latest()->first();

                                if (!$exchangeRate || !$subscription->service) {
                                    return '';
                                }

                                $amountInVE = $exchangeRate->convertToVE($subscription->service->price);

                                return match ($paymentMethod->type) {
                                    PaymentTypeEnum::CashUsd => new HtmlString('Coordinar la entrega.'),
                                    PaymentTypeEnum::MobilePaymentBs => new HtmlString("
                                        Número de teléfono: <b>{$metadata['phone_number']}</b> <br />
                                        Documento de identidad: <b>{$metadata['identity_document']}</b> <br />
                                        Banco: <b>{$bank}</b> <br />
                                        Tasa $ BCV: <b>{$exchangeRate->rate}</b> <br />
                                        Monto ($) <b>{$subscription->service->formatted_price}</b> <br />
                                        Monto (Bs) <b>{$amountInVE}</b> <br />
                                    "),
                                    PaymentTypeEnum::WireTransferBs => new HtmlString("
                                        Banco: <b>{$bank}</b> <br />
                                        Número de cuenta: <b>{$metadata['bank_account_number']}</b> <br />
                                        Documento de identidad: <b>{$metadata['identity_document']}</b> <br />
                                        Tasa $ BCV: <b>{$exchangeRate->rate}</b> <br />
                                        Monto ($) <b>{$subscription->service->formatted_price}</b> <br />
                                        Monto (Bs) <b>{$amountInVE}</b> <br />
                                    "),
                                    PaymentTypeEnum::Zelle => new HtmlString("
                                        Email: <b>{$metadata['email']}</b> <br />
                                        Monto en $: <b>{$subscription->service->formatted_price}</b> <br />
                                    "),
                                    default => '',
                                };
                            }),

                    ]),

                Forms\Components\Fieldset::make('Detalles de pago')
                    ->visible(fn(Forms\Get $get) => $get('payment_method_id'))
                    ->reactive()
                    ->schema(function (Forms\Get $get) {
                        /** @var int|null $paymentMethodId */
                        $paymentMethodId = $get('payment_method_id');

                        /** @var PaymentMethod|null $paymentMethod */
                        $paymentMethod = PaymentMethod::find($paymentMethodId);

                        return match ($paymentMethod?->type) {
                            PaymentTypeEnum::MobilePaymentBs => PaymentMethodCustomerForm::mobilePaymentForm(),
                            PaymentTypeEnum::WireTransferBs => PaymentMethodCustomerForm::wireTransferBsForm(),
                            PaymentTypeEnum::Zelle => PaymentMethodCustomerForm::zelleForm(),
                            PaymentTypeEnum::CashUsd => PaymentMethodCustomerForm::cashUsdForm(),
                            default => [],
                        };
                    }),
            ])
            ->mutateFormDataUsing(function (array $data) {
                $data['from_type'] = Store::class;
                $data['from_id'] = $this->store->id;
                $data['type'] = TransactionTypeEnum::Subscription;
                $data['amount_cents'] = $this->store->subscription?->service?->price_usd_cents;
                $data['status'] = TransactionStatusEnum::Pending;

                /** @var int $paymentMethodId */
                $paymentMethodId = $data['payment_method_id'];
                /** @var PaymentMethod|null $paymentMethod */
                $paymentMethod = PaymentMethod::find($paymentMethodId);

                /** @var ExchangeRate $exchangeRate */
                $exchangeRate = ExchangeRate::latest()->first();
                /** @var Subscription $subscription */
                $subscription = $this->store->subscription;
                /** @var Service $service */
                $service = $subscription->service;

                $amountInVE = $exchangeRate->convertToVE($service->price);

                $data['metadata']['payment_method'] = $paymentMethod?->type;
                $data['metadata']['amount_bs'] = $amountInVE;

                unset($data['payment_method_id']);

                return $data;
            });
    }

    public function cancelSubscriptionAction(): Action
    {
        return Action::make('cancelSubscription')
            ->label('Cancelar')
            ->requiresConfirmation()
            ->hidden(fn() => $this->store->subscription?->is_cancelled || $this->store->subscription?->is_about_to_be_cancelled)
            ->color('danger')
            ->modalHeading('Cancelar subscripción')
            ->action(function () {
                $subscription = $this->store->subscription;
                if (!$subscription) {
                    return;
                }
                $subscription->cancel();

                Notification::make('subscriptionCancelled')
                    ->success()
                    ->title('Subscripción cancelada')
                    ->body(
                        'Tu subscripción estará disponible hasta el '
                        . $subscription->ends_at?->timezone('America/Caracas')->format('d/m/Y')
                    )
                    ->send();
            });
    }

    public function reactivateSubscriptionAction(): Action
    {
        return Action::make('reactivateSubscription')
            ->label('Reactivar')
            ->requiresConfirmation()
            ->modalHeading('Reactivar subscripción')
            ->visible(fn() => $this->store->subscription?->is_about_to_be_cancelled)
            ->action(function () {
                $subscription = $this->store->subscription;
                if (!$subscription) {
                    return;
                }
                $subscription->reactivate();

                Notification::make('subscriptionReactivated')
                    ->success()
                    ->title('Subscripción reactivada')
                    ->body(
                        'Tu subscripción será renovada el '
                        . $subscription->renews_at?->timezone('America/Caracas')->format('d/m/Y')
                    )
                    ->send();
            });
    }

    public static function isTenantSubscriptionRequired(Panel $panel): bool
    {
        return false;
    }
}