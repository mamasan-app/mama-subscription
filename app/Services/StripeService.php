<?php

namespace App\Services;

use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Product;
use Stripe\Price;
use Stripe\Checkout\Session as StripeSession;

class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey(config('stripe.secret_key'));
    }

    public function getOrCreateCustomer($user)
    {
        if (!$user->stripe_customer_id) {
            // Crear cliente en Stripe
            $customer = Customer::create([
                'email' => $user->email,
                'name' => "{$user->first_name} {$user->last_name}",
            ]);

            // Actualizar el modelo del usuario con el ID del cliente de Stripe
            $user->update(['stripe_customer_id' => $customer->id]);
        } else {
            // Recuperar cliente existente
            $customer = Customer::retrieve($user->stripe_customer_id);
        }

        return $customer;
    }

    public function getOrCreateProduct($service)
    {
        if (!$service->stripe_product_id) {
            $product = Product::create([
                'name' => $service->name,
                'description' => $service->description,
            ]);

            $service->update(['stripe_product_id' => $product->id]);
        } else {
            $product = Product::retrieve($service->stripe_product_id);
        }

        return $product;
    }

    public function createPrice($product, $amountCents, $interval, $intervalCount)
    {
        return Price::create([
            'product' => $product->id,
            'unit_amount' => $amountCents,
            'currency' => 'usd',
            'recurring' => [
                'interval' => $interval,
                'interval_count' => $intervalCount,
            ],
        ]);
    }

    public function createCheckoutSession($customer, $price, $successUrl, $cancelUrl, $metadata = [])
    {
        return StripeSession::create([
            'customer' => $customer->id,
            'payment_method_types' => ['card'],
            'line_items' => [
                [
                    'price' => $price->id,
                    'quantity' => 1,
                ],
            ],
            'mode' => 'subscription',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => $metadata,
        ]);
    }
}
