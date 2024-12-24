<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MBConsultaController;
use App\Http\Controllers\MBNotificaController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\CheckoutWebhookController;
use App\Http\Controllers\InvoiceWebhookController;
use App\Http\Controllers\PaymentIntentWebhookController;

Route::post('/webhooks/stripe/payment-intents', [PaymentIntentWebhookController::class, 'handle']);
Route::post('/webhooks/stripe/checkout', [CheckoutWebhookController::class, 'handle']);
Route::post('/webhooks/stripe/invoice', [InvoiceWebhookController::class, 'handle']);

//Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handle']);

Route::post('/MBnotifica', [MBNotificaController::class, 'notificarTransaccion'])
    ->withoutMiddleware([\Illuminate\Auth\Middleware\Authenticate::class]);


Route::post('/MBconsulta', [MBConsultaController::class, 'validarUsuario'])
    ->withoutMiddleware([\Illuminate\Auth\Middleware\Authenticate::class]);
