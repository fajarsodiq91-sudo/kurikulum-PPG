<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'permission:view-dashboard'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');
});

Route::middleware(['auth', 'permission:view-master-data'])->group(function () {
    Route::get('/master-data/regions', [\App\Http\Controllers\MasterData\RegionController::class, 'index'])
        ->name('master-data.regions.index');
    Route::post('/master-data/regions', [\App\Http\Controllers\MasterData\RegionController::class, 'store'])
        ->name('master-data.regions.store');
});
