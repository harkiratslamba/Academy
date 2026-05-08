<?php

namespace Modules\Teacher\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Models\User;
use Modules\Teacher\Models\Teacher;
use Modules\Timetable\Models\Timetable;

class TeacherController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin,coordinator']);
    }

    public function index()
    {
        $teachers = Teacher::with('user')->orderBy('name')->get();
        return view('teacher::admin.index', compact('teachers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                   => 'required|string|max:255',
            'employee_id'            => 'required|string|max:50|unique:teachers,employee_id',
            'username'               => 'required|string|max:50|unique:users,username',
            'password'               => 'required|string|min:6',
            'email'                  => 'nullable|email|max:255|unique:teachers,email',
            'phone'                  => 'nullable|string|max:20',
            'subject_specialization' => 'nullable|string|max:255',
            'qualification'          => 'nullable|string|max:255',
            'joining_date'           => 'nullable|date',
        ]);

        // Create user account
        $user = User::create([
            'username' => $data['username'],
            'name'     => $data['name'],
            'email'    => $data['email'] ?? null,
            'password' => Hash::make($data['password']),
            'role'     => 'teacher',
        ]);

        // Create teacher record
        $teacher = Teacher::create([
            'user_id'                => $user->id,
            'name'                   => $data['name'],
            'email'                  => $data['email'] ?? null,
            'phone'                  => $data['phone'] ?? null,
            'employee_id'            => $data['employee_id'],
            'subject_specialization' => $data['subject_specialization'] ?? null,
            'qualification'          => $data['qualification'] ?? null,
            'joining_date'           => $data['joining_date'] ?? null,
            'status'                 => 'active',
        ]);

        // Link teacher_id back on user
        $user->update(['teacher_id' => $teacher->id]);

        return redirect()->back()->with('success', "Teacher '{$teacher->name}' created successfully.");
    }

    public function update(Request $request, $id)
    {
        $teacher = Teacher::findOrFail($id);

        $data = $request->validate([
            'name'                   => 'required|string|max:255',
            'employee_id'            => ['required','string','max:50', Rule::unique('teachers','employee_id')->ignore($teacher->id)],
            'email'                  => ['nullable','email','max:255', Rule::unique('teachers','email')->ignore($teacher->id)],
            'phone'                  => 'nullable|string|max:20',
            'subject_specialization' => 'nullable|string|max:255',
            'qualification'          => 'nullable|string|max:255',
            'joining_date'           => 'nullable|date',
            'status'                 => 'required|in:active,inactive',
        ]);

        $teacher->update($data);

        // Sync name/email on the linked user
        if ($teacher->user) {
            $teacher->user->update([
                'name'  => $data['name'],
                'email' => $data['email'] ?? null,
            ]);
        }

        return redirect()->back()->with('success', "Teacher '{$teacher->name}' updated successfully.");
    }

    public function destroy($id)
    {
        $teacher = Teacher::findOrFail($id);

        // Prevent deletion if teacher has timetable entries
        if (Timetable::where('teacher_id', $id)->exists()) {
            return redirect()->back()->with('error', 'Cannot delete teacher — they have timetable entries. Remove those first.');
        }

        $userName = $teacher->name;

        // Delete associated user account
        if ($teacher->user) {
            $teacher->user->delete();
        }

        $teacher->delete();

        return redirect()->back()->with('success', "Teacher '{$userName}' deleted successfully.");
    }

    public function resetPassword(Request $request, $id)
    {
        $teacher = Teacher::with('user')->findOrFail($id);

        $data = $request->validate([
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        if (!$teacher->user) {
            return redirect()->back()->with('error', 'No user account linked to this teacher.');
        }

        $teacher->user->update(['password' => Hash::make($data['new_password'])]);

        return redirect()->back()->with('success', "Password reset for '{$teacher->name}' successfully.");
    }
}
