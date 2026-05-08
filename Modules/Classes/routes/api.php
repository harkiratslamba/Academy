<?php

use Illuminate\Support\Facades\Route;
use Modules\Classes\Http\Controllers\ClassesController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('classes', ClassesController::class)->names('classes');
});
