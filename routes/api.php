<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MBConsultaController;
use App\Http\Controllers\MBNotificaController;

Route::post('/MBnotifica', [MBNotificaController::class, 'notificarTransaccion'])
    ->withoutMiddleware([\Illuminate\Auth\Middleware\Authenticate::class]);


Route::post('/MBconsulta', [MBConsultaController::class, 'validarUsuario'])
    ->withoutMiddleware([\Illuminate\Auth\Middleware\Authenticate::class]);
