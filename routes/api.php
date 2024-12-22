<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MBConsultaController;


Route::post('/MBConsulta', [MBConsultaController::class, 'validarUsuario'])
    ->withoutMiddleware([\Illuminate\Auth\Middleware\Authenticate::class]);


Route::get('/MBConsulta', function () {
    return response()->json(['status' => 'Ruta funcionando']);
});