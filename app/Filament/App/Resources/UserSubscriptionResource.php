<?php
namespace App\Filament\App\Resources;

use App\Models\Subscription;
use App\Models\Store;
use App\Models\Plan;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use App\Filament\App\Resources\UserSubscriptionResource\Pages;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\Tabs\Tab;

class UserSubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $modelLabel = 'Suscripciones';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('store_id')
                ->label('Tienda')
                ->required()
                ->reactive()
                ->options(function () {
                    $currentUser = auth()->user();
                    return $currentUser->stores()->select('stores.name', 'stores.id')->pluck('name', 'id');
                })
                ->afterStateHydrated(function (callable $set, callable $get) {
                    // Si el store_id viene en la URL, se establece como valor inicial
                    $storeId = request()->query('store_id');
                    if ($storeId && !$get('store_id')) {
                        $set('store_id', $storeId);
                    }
                })
                ->afterStateUpdated(fn(callable $set) => $set('service_id', null)), // Limpia el servicio seleccionado al cambiar la tienda


            Forms\Components\Select::make('service_id')
                ->label('Servicio')
                ->required()
                ->options(function (callable $get) {
                    $storeId = $get('store_id');
                    return $storeId
                        ? Plan::where('store_id', $storeId)->pluck('name', 'id')
                        : [];
                }),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(static::getTableQuery()) // Llama al método de la consulta personalizada
            ->defaultSort('created_at', 'desc') // Ordenar por defecto por fecha de creación, descendente
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('service.name')->label('Servicio')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->sortable()
                    ->formatStateUsing(fn($state) => $state?->getLabel()),
                Tables\Columns\TextColumn::make('trial_ends_at')->label('Fin del Período de Prueba')->dateTime(),
                Tables\Columns\TextColumn::make('expires_at')->label('Fecha de Expiración')->dateTime(),
            ])
            ->actions([
                Action::make('Pagar')
                    ->url(fn(Subscription $record): string => Pages\UserSubscriptionPayment::getUrl(['record' => $record]))
                    ->color('success')
                    ->icon('heroicon-o-currency-dollar')
                    ->label('Pagar')
                    ->button()
                    ->visible(fn(Subscription $record) => $record->payments->flatMap->transactions->isEmpty()), // Mostrar solo si no hay transacciones
            ]);

    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Tabs::make('Detalles de la Suscripción')
                    ->tabs([
                        // Pestaña de la suscripción
                        Tab::make('Suscripción')
                            ->schema([
                                TextEntry::make('status')
                                    ->label('Estado')
                                    ->getStateUsing(fn($record) => $record->status->getLabel())
                                    ->badge()
                                    ->color(fn($state) => match ($state) {
                                        'Activo' => 'success',
                                        'Cancelado' => 'danger',
                                        default => 'warning',
                                    }),
                                TextEntry::make('trial_ends_at')
                                    ->label('Fin del Periodo de Prueba')
                                    ->dateTime()
                                    ->placeholder('No disponible'),
                                TextEntry::make('renews_at')
                                    ->label('Renovación')
                                    ->dateTime()
                                    ->placeholder('No disponible'),
                                TextEntry::make('ends_at')
                                    ->label('Fecha de Finalización')
                                    ->dateTime()
                                    ->placeholder('No disponible'),
                                TextEntry::make('last_notification_at')
                                    ->label('Última Notificación')
                                    ->dateTime()
                                    ->placeholder('No disponible'),
                                TextEntry::make('expires_at')
                                    ->label('Fecha de Expiración')
                                    ->dateTime()
                                    ->placeholder('No disponible'),
                                TextEntry::make('frequency_days')
                                    ->label('Frecuencia de Pago (días)')
                                    ->placeholder('No disponible'),
                            ])->columns(2),

                        // Pestaña del plan
                        Tab::make('Plan')
                            ->schema([
                                TextEntry::make('service_name')
                                    ->label('Nombre del Servicio')
                                    ->placeholder('No disponible'),
                                TextEntry::make('service_description')
                                    ->label('Descripción del Servicio')
                                    ->placeholder('No disponible'),
                                TextEntry::make('formattedServicePrice')
                                    ->label('Precio del Servicio')
                                    ->placeholder('No disponible'),
                                TextEntry::make('service_free_days')
                                    ->label('Días Gratis')
                                    ->placeholder('No disponible'),
                                TextEntry::make('service_grace_period')
                                    ->label('Período de Gracia')
                                    ->placeholder('No disponible'),
                            ])->columns(2),

                        // Pestaña de la tienda
                        Tab::make('Tienda')
                            ->schema([
                                Group::make()
                                    ->schema([
                                        ImageEntry::make('store.logoUrl')
                                            ->label('Logo de la Tienda')
                                            ->circular()
                                            ->placeholder('No disponible')
                                            ->columnSpan(2), // Ocupa el ancho completo en dos columnas
                                        TextEntry::make('store.name')
                                            ->label('Nombre de la Tienda')
                                            ->placeholder('No disponible'),
                                        TextEntry::make('store.verified')
                                            ->label('Verificada')
                                            ->getStateUsing(fn($record) => $record->store?->verified ? 'Sí' : 'No')
                                            ->badge()
                                            ->color(fn($state) => $state === 'Sí' ? 'success' : 'danger'),
                                        TextEntry::make('store.owner.name')
                                            ->label('Nombre del Propietario')
                                            ->placeholder('No disponible'),
                                        TextEntry::make('store.owner.email')
                                            ->label('Correo Electrónico')
                                            ->placeholder('No disponible'),
                                        TextEntry::make('store.owner.phone')
                                            ->label('Teléfono')
                                            ->placeholder('No disponible'),
                                    ])
                                    ->columns(2), // Define que este grupo de elementos se mostrará en dos columnas
                            ]),

                    ])->columnSpanFull(),
            ]);
    }




    public static function getTableQuery()
    {
        // Obtén al usuario actualmente autenticado
        $currentUser = auth()->user();

        // Filtra las suscripciones asociadas al usuario autenticado
        return Subscription::query()->where('user_id', $currentUser->id)->with(['store.owner']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUserSubscriptions::route('/'),
            'create' => Pages\CreateUserSubscription::route('/create'),
            'view' => Pages\ViewUserSubscription::route('/{record}'),
            'payment' => Pages\UserSubscriptionPayment::route('/{record}/payment'),
        ];
    }
}
