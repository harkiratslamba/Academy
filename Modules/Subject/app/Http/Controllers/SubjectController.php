<?php

namespace Modules\Subject\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Modules\Subject\Models\Subject;
use Modules\Classes\Models\SchoolClass;

class SubjectController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin,coordinator']);
    }

    public function index()
    {
        $subjects = Subject::with('schoolClass')->orderBy('subject_name')->get();
        $classes  = SchoolClass::orderBy('class_name')->get();
        return view('subject::admin.index', compact('subjects', 'classes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject_name' => 'required|string|max:255',
            'subject_code' => 'required|string|max:20|unique:subjects,subject_code',
            'class_id'     => 'nullable|exists:classes,id',
        ]);

        Subject::create($data);

        return redirect()->back()->with('success', "Subject '{$data['subject_name']}' created.");
    }

    public function update(Request $request, $id)
    {
        $subject = Subject::findOrFail($id);

        $data = $request->validate([
            'subject_name' => 'required|string|max:255',
            'subject_code' => ['required','string','max:20', Rule::unique('subjects','subject_code')->ignore($subject->id)],
            'class_id'     => 'nullable|exists:classes,id',
        ]);

        $subject->update($data);

        return redirect()->back()->with('success', "Subject '{$subject->subject_name}' updated.");
    }

    public function destroy($id)
    {
        $subject = Subject::findOrFail($id);

        // Prevent if timetable references exist
        $hasTimetable = \Illuminate\Support\Facades\DB::table('timetables')
            ->where('subject_id', $id)->exists();

        if ($hasTimetable) {
            return redirect()->back()->with('error', "Cannot delete '{$subject->subject_name}' — it's used in the timetable.");
        }

        $name = $subject->subject_name;
        $subject->delete();

        return redirect()->back()->with('success', "Subject '{$name}' deleted.");
    }
}
