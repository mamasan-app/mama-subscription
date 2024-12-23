<?php

namespace App\Filament\App\Resources\StoreResource\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use App\Models\Subscription;

class StoreSubscriptionsWidget extends BaseWidget
{
    public $record;

    protected function getTableHeading(): ?string
    {
        return 'Suscripciones de la Tienda';
    }

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                $this->getQuery()
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('plan.name')
                    ->label('Plan')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'active' => 'success',
                        'inactive' => 'warning',
                        'canceled' => 'danger',
                        default => 'secondary',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de Creación')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Última Actualización')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                // Puedes añadir filtros aquí si es necesario
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }

    protected function getQuery()
    {

        $storeId = $this->record->id;

        $userSubscriptions = Subscription::query()
            ->where('user_id', auth()->id())
            ->pluck('id');

        $storeSubscriptions = Subscription::query()
            ->where('store_id', $storeId)
            ->pluck('id');

        $intersectedSubscriptions = $userSubscriptions->intersect($storeSubscriptions);


        return Subscription::query()
            ->whereIn('id', $intersectedSubscriptions);
    }
}
