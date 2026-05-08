<?php

namespace Modules\Classes\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Classes\Models\SchoolClass;
use Modules\Classes\Models\Section;

class ClassesController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin,coordinator']);
    }

    public function index()
    {
        $classes  = SchoolClass::withCount('sections')->orderBy('class_name')->get();
        $sections = Section::with('schoolClass')->orderBy('class_id')->orderBy('section_name')->get();
        return view('classes::admin.index', compact('classes', 'sections'));
    }

    public function storeClass(Request $request)
    {
        $data = $request->validate([
            'class_name' => 'required|string|max:100|unique:classes,class_name',
        ]);

        SchoolClass::create($data);

        return redirect()->back()->with('success', "Class '{$data['class_name']}' created successfully.");
    }

    public function destroyClass($id)
    {
        $class = SchoolClass::withCount('sections')->findOrFail($id);

        if ($class->sections_count > 0) {
            return redirect()->back()->with('error', "Cannot delete '{$class->class_name}' — it has {$class->sections_count} section(s). Delete sections first.");
        }

        // Also check for timetable entries
        $hasTimetable = \Illuminate\Support\Facades\DB::table('timetables')
            ->where('class_id', $id)->exists();

        if ($hasTimetable) {
            return redirect()->back()->with('error', "Cannot delete '{$class->class_name}' — it has timetable entries.");
        }

        $name = $class->class_name;
        $class->delete();

        return redirect()->back()->with('success', "Class '{$name}' deleted.");
    }

    public function storeSection(Request $request)
    {
        $data = $request->validate([
            'class_id'     => 'required|exists:classes,id',
            'section_name' => 'required|string|max:10',
        ]);

        // Unique per class
        $exists = Section::where('class_id', $data['class_id'])
            ->where('section_name', $data['section_name'])
            ->exists();

        if ($exists) {
            return redirect()->back()->with('error', "Section '{$data['section_name']}' already exists for that class.");
        }

        Section::create($data);

        return redirect()->back()->with('success', "Section '{$data['section_name']}' added.");
    }

    public function destroySection($id)
    {
        $section = Section::with('schoolClass')->findOrFail($id);

        $hasTimetable = \Illuminate\Support\Facades\DB::table('timetables')
            ->where('section_id', $id)->exists();

        if ($hasTimetable) {
            return redirect()->back()->with('error', "Cannot delete section '{$section->section_name}' — it has timetable entries.");
        }

        $name = $section->section_name;
        $section->delete();

        return redirect()->back()->with('success', "Section '{$name}' deleted.");
    }
}
