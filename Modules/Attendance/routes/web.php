<?php

use Illuminate\Support\Facades\Route;
use Modules\Attendance\Http\Controllers\AttendanceController;

Route::middleware(['auth', 'role:admin,coordinator'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/attendance',        [AttendanceController::class, 'adminIndex'])->name('attendance.index');
    Route::post('/attendance/bulk',  [AttendanceController::class, 'markBulk'])->name('attendance.bulk');
});
