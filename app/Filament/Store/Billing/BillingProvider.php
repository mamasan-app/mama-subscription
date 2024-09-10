<?php

declare(strict_types=1);

namespace App\Filament\Store\Billing;

use App\Filament\Store\Pages\Billing;
use App\Http\Middleware\Store\RedirectIfNotSubscribed;
use App\Models\Store;
use Closure;
use Filament\Billing\Providers\Contracts\Provider;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;

class BillingProvider implements Provider
{
    public function getRouteAction(): string|Closure|array
    {
        return function (): RedirectResponse {
            /** @var Store $store */
            $store = Filament::getTenant();

            return redirect()->to(Billing::getUrl(tenant: $store));
        };
    }

    public function getSubscribedMiddleware(): string
    {
        return RedirectIfNotSubscribed::class;
    }
}
