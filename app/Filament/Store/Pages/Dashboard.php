<?php

namespace App\Filament\Store\Pages;

use App\Filament\Store\Actions\HelpAction;
use App\Enums\SubscriptionStatusEnum;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Filament\Store\Widgets\SubscriptionChart;
use App\Filament\Store\Widgets\SubscriptionStats;
use App\Filament\Store\Widgets\TodaySubscriptionsTable;
use App\Filament\Store\Widgets\PaymentStatsWidget;
use App\Filament\Store\Widgets\StoreRevenueChart;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard as FilamentDashboard;
use Carbon\Carbon;
use Filament\Facades\Filament;

class Dashboard extends FilamentDashboard
{
    protected function getHeaderActions(): array
    {
        return [
            Action::make('addSubscription')
                ->visible(fn() => auth()->user()?->can('create subscriptions')) // Verificar permisos
                ->label('Registrar Suscripción')
                ->form([
                    Select::make('service_id')
                        ->label('Plan')
                        ->options(fn() => Plan::where('store_id', Filament::getTenant()->id)
                            ->where('published', true)
                            ->pluck('name', 'id'))
                        ->searchable()
                        ->required(),
                    Select::make('user_id')
                        ->label('Cliente')
                        ->options(fn() => User::whereHas('stores', fn($query) => $query
                            ->where('store_id', Filament::getTenant()->id)
                            ->where('store_user.role', 'customer'))
                            ->pluck('name', 'id'))
                        ->searchable()
                        ->required(),
                ])
                ->action(function (array $data) {
                    $currentStore = Filament::getTenant();
                    if (!$currentStore) {
                        Notification::make()->danger()
                            ->title('Error')
                            ->body('No se encontró una tienda para asociar la suscripción.')
                            ->send();
                        return;
                    }

                    $plan = Plan::with('frequency')->find($data['service_id']);
                    if (!$plan) {
                        Notification::make()->danger()
                            ->title('Error')
                            ->body('El plan seleccionado no se encontró.')
                            ->send();
                        return;
                    }

                    $now = Carbon::now('America/Caracas');
                    $trialEndsAt = $now->clone()->addDays($plan->free_days ?? 0);
                    $expiresAt = $now->clone()->addDays(($plan->grace_period ?? 0) + ($plan->free_days ?? 0));

                    Subscription::create([
                        'store_id' => $currentStore->id,
                        'user_id' => $data['user_id'],
                        'service_id' => $data['service_id'],
                        'status' => SubscriptionStatusEnum::OnTrial->value,
                        'trial_ends_at' => $trialEndsAt,
                        'renews_at' => $trialEndsAt,
                        'expires_at' => $expiresAt,
                        'service_name' => $plan->name,
                        'service_description' => $plan->description,
                        'service_price_cents' => $plan->price_cents,
                        'service_free_days' => $plan->free_days,
                        'service_grace_period' => $plan->grace_period,
                        'frequency_days' => $plan->getFrequencyDays(),
                    ]);

                    Notification::make()
                        ->success()
                        ->title('Suscripción registrada satisfactoriamente')
                        ->send();
                }),

            HelpAction::iconButton()
            //->modalContent(view('filament.store.actions.help.dashboard')),
        ];
    }

    public function getWidgets(): array
    {
        return [
            SubscriptionStats::make(),
            PaymentStatsWidget::make(),
            StoreRevenueChart::make(),
            SubscriptionChart::make(),
            TodaySubscriptionsTable::make(),
        ];
    }
}
