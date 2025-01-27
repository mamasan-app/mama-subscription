<?php

namespace App\Services;

use Stripe\Checkout\Session as StripeSession;
use Stripe\Customer;
use Stripe\Price;
use Stripe\Product;
use Stripe\Stripe;

class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey(config('stripe.secret_key'));
    }

    public function getOrCreateCustomer($user)
    {
        if (! $user->stripe_customer_id) {
            // Crear cliente en Stripe
            $customer = Customer::create([
                'email' => $user->email,
                'name' => "{$user->first_name} {$user->last_name}",
            ]);

            // Actualizar el modelo del usuario con el ID del cliente de Stripe
            $user->update(['stripe_customer_id' => $customer->id]);
        } else {
            try {
                // Intentar recuperar cliente existente
                $customer = Customer::retrieve($user->stripe_customer_id);

                // Verificar que el cliente aún exista
                if (! $customer || $customer->deleted ?? false) {
                    throw new \Exception('Cliente no encontrado o eliminado en Stripe.');
                }
            } catch (\Exception $e) {
                // Si ocurre un error, crear un nuevo cliente
                $customer = Customer::create([
                    'email' => $user->email,
                    'name' => "{$user->first_name} {$user->last_name}",
                ]);

                // Actualizar el modelo del usuario con el nuevo ID del cliente
                $user->update(['stripe_customer_id' => $customer->id]);
            }
        }

        return $customer;
    }

    public function getOrCreateProduct($service)
    {
        if (! $service->stripe_product_id) {
            // Crear el producto en Stripe
            $product = Product::create([
                'name' => $service->name,
                'description' => $service->description,
                'metadata' => [
                    'retry_count' => $service->grace_period,   // Número máximo de reintentos
                    'retry_interval' => 1, // Intervalo fijo de 1 día
                ],
            ]);

            // Actualizar el producto en tu base de datos
            $service->update(['stripe_product_id' => $product->id]);
        } else {
            try {
                // Intentar recuperar el producto existente desde Stripe
                $product = Product::retrieve($service->stripe_product_id);

                // Validar si el producto existe o ha sido eliminado
                if (! $product || $product->deleted ?? false) {
                    throw new \Exception('Producto no encontrado o eliminado en Stripe.');
                }

                // Actualizar metadata si cambió el período de gracia
                if ($product->metadata->retry_count != $service->grace_period) {
                    $product->metadata = [
                        'retry_count' => $service->grace_period,
                        'retry_interval' => 1, // Siempre 1 día
                    ];
                    $product->save();
                }
            } catch (\Exception $e) {
                // Si ocurre un error, crear un nuevo producto
                $product = Product::create([
                    'name' => $service->name,
                    'description' => $service->description,
                    'metadata' => [
                        'retry_count' => $service->grace_period,   // Número máximo de reintentos
                        'retry_interval' => 1, // Intervalo fijo de 1 día
                    ],
                ]);

                // Actualizar el producto en tu base de datos con el nuevo ID
                $service->update(['stripe_product_id' => $product->id]);
            }
        }

        return $product;
    }

    public function createPrice($product, $amountCents, $interval, $intervalCount, $gracePeriod)
    {
        return Price::create([
            'product' => $product->id,
            'unit_amount' => $amountCents,
            'currency' => 'usd',
            'recurring' => [
                'interval' => $interval,
                'interval_count' => $intervalCount,
            ],
            'metadata' => [
                'retry_count' => $gracePeriod,   // Número máximo de reintentos
                'retry_interval' => 1,          // Intervalo fijo de 1 día
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
            'metadata' => array_merge($metadata, [
                'retry_count' => $price->metadata->retry_count ?? null,  // Número máximo de reintentos
                'retry_interval' => $price->metadata->retry_interval ?? null, // Intervalo en días
            ]),
        ]);
    }
}
