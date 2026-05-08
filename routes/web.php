<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

// API route for sections (used in timetable form)
Route::get('/api/sections/{classId}', function ($classId) {
    $sections = \Modules\Classes\Models\Section::where('class_id', $classId)->get(['id', 'section_name']);
    return response()->json($sections);
})->middleware('auth');

// API route for available substitute teachers
Route::get('/api/available-teachers', function (\Illuminate\Http\Request $request) {
    return app(\Modules\Substitution\Http\Controllers\SubstitutionController::class)->availableTeachers($request);
})->middleware('auth');
