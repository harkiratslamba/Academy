<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\AuthController;

Route::middleware('guest')->group(function () {
    Route::get('/',        [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login',  [AuthController::class, 'login'])->name('login.submit');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');
