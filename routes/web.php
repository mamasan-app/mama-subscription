<?php

use App\Http\Controllers\MagicLinkLoginController;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $panel = request('panel', 'app'); // Obtén el panel desde la URL, predeterminado a 'app'

    $request->fulfill();

    // Redirige al panel correspondiente
    switch ($panel) {
        case 'tienda':
            return redirect('/tienda');
        case 'admin':
            return redirect('/admin');
        default:
            return redirect('/app');
    }
})->middleware(['auth', 'signed'])->name('verification.verify');



Route::get('/magiclink/send', [MagicLinkLoginController::class, 'sendMagicLink'])->name('magiclink.send');
Route::get('/magiclink/login', [MagicLinkLoginController::class, 'loginWithMagicLink'])->name('magiclink.login');



Route::get('/', function () {
    return redirect('/app');
});
