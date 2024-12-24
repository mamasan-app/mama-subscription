<?php

use App\Http\Controllers\MagicLinkLoginController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StripeWebhookController;

Route::get('/magiclink/send', [MagicLinkLoginController::class, 'sendMagicLink'])->name('magiclink.send');
Route::get('/magiclink/login', [MagicLinkLoginController::class, 'loginWithMagicLink'])->name('magiclink.login');

Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handle']);

Route::get('/', function () {
    return redirect('/app');
});
