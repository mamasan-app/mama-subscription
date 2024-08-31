<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect(route('filament.app.pages.dashboard'));
});

Route::middleware(['auth', 'panel.access'])->prefix('admin')->group(function () {
    Route::get('/dashboard', function () {
        return redirect()->route('filament.admin.pages.dashboard', ['panelId' => 'admin']);
    })->name('admin.dashboard');
});

Route::middleware(['auth', 'panel.access'])->prefix('store')->group(function () {
    Route::get('/dashboard', function () {
        return redirect()->route('filament.store.pages.dashboard', ['panelId' => 'store']);
    })->name('store.dashboard');
});
