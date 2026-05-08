<?php

use Illuminate\Support\Facades\Route;
use Modules\Leave\Http\Controllers\LeaveController;

Route::middleware(['auth', 'role:admin,coordinator'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/leaves',               [LeaveController::class, 'adminIndex'])->name('leaves.admin');
    Route::post('/leaves/{id}/approve', [LeaveController::class, 'approve'])->name('leaves.approve');
    Route::post('/leaves/{id}/reject',  [LeaveController::class, 'reject'])->name('leaves.reject');
});
