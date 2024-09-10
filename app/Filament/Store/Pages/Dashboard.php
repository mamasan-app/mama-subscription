<?php

namespace App\Filament\Store\Pages;

use App\Filament\Store\Actions\HelpAction;
use App\Enums\SubscriptionStatusEnum;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\User;
use App\Filament\Store\Widgets\SubscriptionChart;
use App\Filament\Store\Widgets\SubscriptionStats;
use App\Filament\Store\Widgets\TodaySubscriptionsTable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard as FilamentDashboard;

class Dashboard extends FilamentDashboard
{
    protected function getHeaderActions(): array
    {
        return [
            Action::make('addSubscription')
                ->visible(fn() => auth()->user()?->can('create subscriptions')) // Verificar permisos de suscripción
                ->label('Registrar Suscripción')
                ->form([
                    // Seleccionar un servicio para la suscripción
                    Select::make('service_id')
                        ->label('Servicio')
                        ->searchable()
                        ->getSearchResultsUsing(function (string $search): array {
                            return Service::query()
                                ->where('name', 'like', "%$search%")
                                ->get()
                                ->mapWithKeys(fn(Service $service) => [$service->id => $service->name])
                                ->all();
                        }),

                    // Fecha de fin del periodo de prueba
                    DatePicker::make('trial_ends_at')
                        ->label('Fin del Periodo de Prueba')
                        ->default(now()->addDays(7)),

                    // Fecha de renovación de la suscripción
                    DatePicker::make('renews_at')
                        ->label('Fecha de Renovación')
                        ->default(now()->addMonth()),

                    // Seleccionar el cliente (usuario con el rol 'customer')
                    Select::make('user_id')
                        ->label('Cliente')
                        ->searchable()
                        ->getSearchResultsUsing(function (string $search): array {
                            return User::role('customer') // Buscar solo usuarios con rol 'customer'
                                ->where('first_name', 'like', "%$search%")
                                ->orWhere('last_name', 'like', "%$search%")
                                ->get()
                                ->mapWithKeys(fn(User $user) => [$user->id => $user->name])
                                ->all();
                        }),
                ])
                ->action(function (array $data) {
                    // Registrar una nueva suscripción
                    Subscription::create([
                        'service_id' => $data['service_id'],
                        'user_id' => $data['user_id'],  // Relacionar la suscripción con el usuario
                        'store_id' => auth()->user()->ownedStores->first()->id ?? null,  // Obtener la tienda del propietario
                        'status' => SubscriptionStatusEnum::OnTrial,
                        'trial_ends_at' => $data['trial_ends_at'],
                        'renews_at' => $data['renews_at'],
                        'price_usd_cents' => Service::find($data['service_id'])->price * 100,  // Obtener el precio del servicio
                        'creator_id' => auth()->id(),
                    ]);

                    Notification::make('subscriptionAdded')
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
            SubscriptionChart::make(),
            TodaySubscriptionsTable::make(),
        ];
    }
}
