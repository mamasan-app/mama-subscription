<?php

use App\Http\Controllers\MagicLinkLoginController;
use Illuminate\Support\Facades\Route;


Route::get('/magiclink/send', [MagicLinkLoginController::class, 'sendMagicLink'])->name('magiclink.send');
Route::get('/magiclink/login', [MagicLinkLoginController::class, 'loginWithMagicLink'])->name('magiclink.login');



Route::get('/', function () {
    return redirect('/app');
});
