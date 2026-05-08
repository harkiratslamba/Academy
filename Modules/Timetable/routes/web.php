<?php

use Illuminate\Support\Facades\Route;
use Modules\Timetable\Http\Controllers\TimetableController;

Route::middleware(['auth', 'role:admin,coordinator'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/timetable',          [TimetableController::class, 'index'])->name('timetable.index');
    Route::post('/timetable',         [TimetableController::class, 'store'])->name('timetable.store');
    Route::delete('/timetable/{id}',  [TimetableController::class, 'destroy'])->name('timetable.destroy');
    Route::post('/timetable/{id}/toggle', [TimetableController::class, 'toggle'])->name('timetable.toggle');
});
