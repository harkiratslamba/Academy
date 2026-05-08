<?php

use Illuminate\Support\Facades\Route;
use Modules\Teacher\Http\Controllers\TeacherController;
use Modules\Teacher\Http\Controllers\TeacherDashboardController;
use Modules\Teacher\Http\Controllers\TeacherTimetableController;
use Modules\Teacher\Http\Controllers\TeacherAvailabilityController;
use Modules\Teacher\Http\Controllers\TeacherLeaveController;
use Modules\Teacher\Http\Controllers\TeacherAttendanceController;

Route::middleware(['auth', 'role:admin,coordinator'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('teachers', TeacherController::class)->except(['show', 'create', 'edit']);
    Route::post('teachers/{teacher}/reset-password', [TeacherController::class, 'resetPassword'])->name('teachers.resetPassword');
});

Route::middleware(['auth', 'role:teacher'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/dashboard',     [TeacherDashboardController::class,  'index'])->name('dashboard');
    Route::get('/timetable',     [TeacherTimetableController::class,  'index'])->name('timetable');
    Route::get('/availability',  [TeacherAvailabilityController::class, 'index'])->name('availability');
    Route::post('/availability', [TeacherAvailabilityController::class, 'store'])->name('availability.store');
    Route::get('/leaves',        [TeacherLeaveController::class,       'index'])->name('leaves.index');
    Route::post('/leaves',       [TeacherLeaveController::class,       'store'])->name('leaves.store');
    Route::delete('/leaves/{id}',[TeacherLeaveController::class,       'cancel'])->name('leaves.cancel');
    Route::get('/attendance',    [TeacherAttendanceController::class,  'index'])->name('attendance');
});
