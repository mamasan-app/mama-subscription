<?php

return [
    'secret_key' => env('STRIPE_SECRET_KEY'),
    'publishable_key' => env('STRIPE_PUBLIC_KEY'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    'webhook_checkout' => env('STRIPE_WEBHOOK_CHECKOUT'),
    'webhook_invoice' => env('STRIPE_WEBHOOK_INVOICE'),
];
