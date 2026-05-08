<?php

use Illuminate\Support\Facades\Route;
use Modules\Subject\Http\Controllers\SubjectController;

Route::middleware(['auth', 'role:admin,coordinator'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('subjects', SubjectController::class)->except(['show', 'create', 'edit']);
});
