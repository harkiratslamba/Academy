<?php

namespace Modules\Leave\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Leave\Models\Leave;

class LeaveController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin,coordinator']);
    }

    public function adminIndex(Request $request)
    {
        $status = $request->get('status', 'pending');

        $query = Leave::with(['teacher', 'approvedBy'])->orderByDesc('created_at');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $leaves = $query->get();

        $counts = [
            'pending'  => Leave::where('status', 'pending')->count(),
            'approved' => Leave::where('status', 'approved')->count(),
            'rejected' => Leave::where('status', 'rejected')->count(),
            'all'      => Leave::count(),
        ];

        return view('leave::admin.index', compact('leaves', 'status', 'counts'));
    }

    public function approve(Request $request, $id)
    {
        $leave = Leave::findOrFail($id);

        if ($leave->status !== 'pending') {
            return redirect()->back()->with('error', 'This leave request has already been processed.');
        }

        $data = $request->validate([
            'admin_remarks' => 'nullable|string|max:500',
        ]);

        $leave->update([
            'status'        => 'approved',
            'approved_by'   => auth()->id(),
            'approved_on'   => now(),
            'admin_remarks' => $data['admin_remarks'] ?? null,
        ]);

        return redirect()->back()->with('success', "Leave request approved for {$leave->teacher->name}.");
    }

    public function reject(Request $request, $id)
    {
        $leave = Leave::findOrFail($id);

        if ($leave->status !== 'pending') {
            return redirect()->back()->with('error', 'This leave request has already been processed.');
        }

        $data = $request->validate([
            'admin_remarks' => 'nullable|string|max:500',
        ]);

        $leave->update([
            'status'        => 'rejected',
            'approved_by'   => auth()->id(),
            'approved_on'   => now(),
            'admin_remarks' => $data['admin_remarks'] ?? null,
        ]);

        return redirect()->back()->with('success', "Leave request rejected for {$leave->teacher->name}.");
    }
}
