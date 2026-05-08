<?php

use Illuminate\Support\Facades\Route;
use Modules\Substitution\Http\Controllers\SubstitutionController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('substitutions', SubstitutionController::class)->names('substitution');
});
