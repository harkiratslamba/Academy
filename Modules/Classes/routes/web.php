<?php

use Illuminate\Support\Facades\Route;
use Modules\Classes\Http\Controllers\ClassesController;

Route::middleware(['auth', 'role:admin,coordinator'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/classes',          [ClassesController::class, 'index'])->name('classes.index');
    Route::post('/classes',         [ClassesController::class, 'storeClass'])->name('classes.store');
    Route::delete('/classes/{id}',  [ClassesController::class, 'destroyClass'])->name('classes.destroy');
    Route::post('/sections',        [ClassesController::class, 'storeSection'])->name('sections.store');
    Route::delete('/sections/{id}', [ClassesController::class, 'destroySection'])->name('sections.destroy');
});
