<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function success(Subscription $subscription)
    {
        // Aquí puedes actualizar el estado de la suscripción, marcándola como pagada
        $subscription->update(['status' => 'active']);

        return redirect()->route('subscriptions.index')
            ->with('success', 'El pago se ha procesado correctamente.');
    }

    public function cancel()
    {
        return redirect()->route('subscriptions.index')
            ->with('error', 'El pago fue cancelado.');
    }
}
