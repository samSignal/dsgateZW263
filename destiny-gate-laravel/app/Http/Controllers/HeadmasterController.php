<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Staff;
use App\Models\SchoolClass;
use App\Models\Payment;
use App\Models\Attendance;
use App\Models\BehaviourRecord;
use App\Models\Announcement;
use App\Models\Application;
use Illuminate\Http\Request;

class HeadmasterController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'total_students'    => Student::where('status', 'active')->count(),
            'total_staff'       => Staff::where('is_active', true)->count(),
            'fees_collected'    => Payment::whereYear('payment_date', date('Y'))->sum('amount'),
            'behaviour_cases'   => BehaviourRecord::whereYear('issue_date', date('Y'))->count(),
            'pending_apps'      => Application::where('status', 'pending')->count(),
            'classes_count'     => SchoolClass::count(),
        ];

        $recentApplications = Application::latest()->take(5)->get();
        $recentBehaviour    = BehaviourRecord::with('student')->latest()->take(5)->get();
        $announcements      = Announcement::where('is_active', true)->latest()->take(5)->get();

        return view('headmaster.dashboard', compact('stats', 'recentApplications', 'recentBehaviour', 'announcements'));
    }

    public function reports()
    {
        return view('headmaster.reports');
    }

    public function createAnnouncement(Request $request)
    {
        $data = $request->validate([
            'title'           => 'required|string|max:255',
            'content'         => 'required|string',
            'audience'        => 'required|in:all,parents,students,staff,specific_class',
            'target_class_id' => 'nullable|exists:classes,id',
        ]);

        $data['created_by'] = auth()->id();
        Announcement::create($data);

        return back()->with('success', 'Announcement created.');
    }

    public function announcements()
    {
        $announcements = Announcement::with('createdBy')->latest()->paginate(20);
        return view('headmaster.announcements', compact('announcements'));
    }

    public function behaviourCases()
    {
        $cases = BehaviourRecord::with(['student', 'schoolClass', 'recordedBy'])
            ->latest()
            ->paginate(20);

        return view('headmaster.behaviour', compact('cases'));
    }

    public function reviewBehaviour(Request $request, BehaviourRecord $record)
    {
        $request->validate(['headmaster_review' => 'required|string']);

        $record->update([
            'headmaster_review' => $request->headmaster_review,
            'reviewed_at'       => now(),
        ]);

        return back()->with('success', 'Review saved.');
    }
}
