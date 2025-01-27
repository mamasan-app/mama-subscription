<?php

namespace App\Concerns;

use App\Models\Store;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasStore
{
    protected static function booted(): void
    {
        // Agregar el Scope global que filtra por Store
        static::addGlobalScope('store', function (Builder $query) {
            if (auth()->check() && Filament::getCurrentPanel()?->getId() === 'store') {
                /** @var Store|null $store */
                $store = Filament::getTenant();
                if (! $store) {
                    return;
                }
                $query->whereBelongsTo($store);
            }
        });

        // Establecer el store_id al crear un nuevo registro
        static::creating(function (Model $model) {
            if (auth()->check() && Filament::getCurrentPanel()?->getId() === 'store') {
                /** @var Store|null $store */
                $store = Filament::getTenant();
                if (! $store) {
                    return;
                }

                $model->store_id = $store->id;
            }
        });
    }

    /**
     * Relación con la tienda.
     *
     * @return BelongsTo<Store, self>
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
