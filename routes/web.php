<?php

use App\Http\Controllers\PaymentController;

Route::get('/payment/success/{subscription}', [PaymentController::class, 'success'])->name('payment.success');
Route::get('/payment/cancel', [PaymentController::class, 'cancel'])->name('payment.cancel');



//use App\Mail\Confirmed;
//use Illuminate\Support\Facades\Mail;
//
//Route::get('/tienda/email-verification/verificar-email', function () {
//    $email = Auth::user()->email;
//    Mail::to($email)->send(new Confirmed());
//    return redirect(route('filament.store.pages.dashboard'));
//});

//use Illuminate\Support\Facades\Route;
//
//
//Route::get('/', function () {
//    return redirect(route('filament.store.pages.dashboard'));
//});
