<?php

namespace Modules\Teacher\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\Teacher\Models\Teacher;
use Modules\Leave\Models\Leave;
use App\Models\User;
use App\Models\SchoolNotification;

class TeacherLeaveController extends Controller
{
    // Leave entitlements per year
    const LEAVE_LIMITS = [
        'sick'    => 10,
        'casual'  => 12,
        'earned'  => 15,
    ];

    public function index()
    {
        $teacher = Teacher::where('user_id', auth()->id())->firstOrFail();

        $year = now()->year;

        $leaves = Leave::where('teacher_id', $teacher->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate used days per type for current year
        $used = [];
        foreach (array_keys(self::LEAVE_LIMITS) as $type) {
            $used[$type] = Leave::where('teacher_id', $teacher->id)
                ->where('leave_type', $type)
                ->where('status', 'approved')
                ->whereYear('from_date', $year)
                ->get()
                ->sum('days');
        }

        $limits   = self::LEAVE_LIMITS;
        $pending  = Leave::where('teacher_id', $teacher->id)->where('status', 'pending')->count();

        return view('teacher::portal.leaves', compact(
            'teacher', 'leaves', 'used', 'limits', 'pending'
        ));
    }

    public function store(Request $request)
    {
        $teacher = Teacher::where('user_id', auth()->id())->firstOrFail();

        $data = $request->validate([
            'leave_type' => ['required', 'in:sick,casual,earned,other'],
            'from_date'  => ['required', 'date', 'after_or_equal:today'],
            'to_date'    => ['required', 'date', 'after_or_equal:from_date'],
            'reason'     => ['required', 'string', 'max:1000'],
        ]);

        $data['teacher_id'] = $teacher->id;
        $data['status']     = 'pending';

        $leave = Leave::create($data);

        // Notify admin users
        $admins = User::whereIn('role', ['admin', 'coordinator'])->get();
        foreach ($admins as $admin) {
            try {
                SchoolNotification::create([
                    'user_id' => $admin->id,
                    'title'   => 'New Leave Request',
                    'message' => "{$teacher->name} applied for {$data['leave_type']} leave from {$data['from_date']} to {$data['to_date']}.",
                    'type'    => 'info',
                    'is_read' => false,
                ]);
            } catch (\Exception $e) {
                // Notifications table may not exist yet; silently continue
            }
        }

        return redirect()->back()->with('success', 'Leave request submitted successfully.');
    }

    public function cancel($id)
    {
        $teacher = Teacher::where('user_id', auth()->id())->firstOrFail();

        $leave = Leave::where('id', $id)
            ->where('teacher_id', $teacher->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $leave->update(['status' => 'rejected']);

        return redirect()->back()->with('success', 'Leave request cancelled.');
    }
}
