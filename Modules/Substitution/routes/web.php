<?php

use Illuminate\Support\Facades\Route;
use Modules\Substitution\Http\Controllers\SubstitutionController;

Route::middleware(['auth', 'role:admin,coordinator'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/substitution',                    [SubstitutionController::class, 'index'])->name('substitution.index');
    Route::post('/substitution',                   [SubstitutionController::class, 'store'])->name('substitution.store');
    Route::delete('/substitution/{id}',            [SubstitutionController::class, 'destroy'])->name('substitution.destroy');
    Route::post('/substitution/mark-absent',       [SubstitutionController::class, 'markAbsent'])->name('substitution.markAbsent');
    Route::get('/substitution/available-teachers', [SubstitutionController::class, 'availableTeachers'])->name('substitution.availableTeachers');
});
