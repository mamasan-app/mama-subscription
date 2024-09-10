<?php

declare(strict_types=1);

namespace App\Filament\Store\Widgets;

use App\Models\Subscription;
use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TodaySubscriptionsTable extends BaseWidget
{
    protected static ?string $heading = 'Suscripciones de hoy';

    public static function canView(): bool
    {
        /** @var User $authUser */
        $authUser = auth()->user();

        // Verificar si el usuario tiene permiso para ver suscripciones
        return $authUser->can('viewAny', Subscription::class);
    }

    public function getColumnSpan(): int|string|array
    {
        return 'full';
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->query(
                Subscription::query()
                    ->where('created_at', '>=', now('America/Caracas')->startOfDay()->setTimezone('UTC'))
                    ->where('created_at', '<=', now('America/Caracas')->endOfDay()->setTimezone('UTC'))
            )
            ->columns([
                Tables\Columns\Layout\Split::make([
                    Tables\Columns\TextColumn::make('user.name')
                        ->label('Usuario')
                        ->sortable(),
                    Tables\Columns\TextColumn::make('service.name')
                        ->label('Servicio'),
                    Tables\Columns\TextColumn::make('created_at')
                        ->label('Fecha de Suscripción')
                        ->dateTime('d M Y, h:i A')
                        ->sortable(),
                ])->from('md'),
            ]);
    }
}
