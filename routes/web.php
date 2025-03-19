<?php

use App\Http\Controllers\MagicLinkLoginController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Route;

//Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
//    $panel = request('panel', 'app'); // Obtén el panel desde la URL, predeterminado a 'app'
//
//    $request->fulfill();
//
//    // Redirige al panel correspondiente
//    switch ($panel) {
//        case 'tienda':
//            return redirect('/tienda');
//        case 'admin':
//            return redirect('/admin');
//        default:
//            return redirect('/app');
//    }
//})->middleware(['auth', 'signed'])->name('verification.verify');

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $panel = request('panel', 'app'); // Si no se pasa, se asume que va a 'app'

    $request->fulfill();

    // Redirigir al panel correcto
    switch ($panel) {
        case 'tienda':
            return redirect('/tienda');
        case 'admin':
            return redirect('/admin');
        default:
            return redirect('/app'); // Redirige siempre a /app
    }
})->middleware(['auth', 'signed'])->name('verification.verify');


Route::get('/magiclink/send', [MagicLinkLoginController::class, 'sendMagicLink'])->name('magiclink.send');
Route::get('/magiclink/login', [MagicLinkLoginController::class, 'loginWithMagicLink'])->name('magiclink.login');

Route::get('/', function () {
    return redirect('/app');
});

Route::get('/horizon-check', function () {
    return auth()->check() ? auth()->user() : 'Not Authenticated';
});
