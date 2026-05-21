<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Staff;
use App\Models\SchoolClass;
use App\Models\Payment;
use App\Models\BehaviourRecord;
use App\Models\Announcement;
use App\Models\Application;
use Illuminate\Http\Request;

class HeadmasterApiController extends Controller
{
    public function dashboard()
    {
        return response()->json([
            'total_students'      => Student::where('status', 'active')->count(),
            'total_staff'         => Staff::where('is_active', true)->count(),
            'fees_collected'      => Payment::whereYear('payment_date', date('Y'))->sum('amount'),
            'behaviour_cases'     => BehaviourRecord::whereYear('issue_date', date('Y'))->count(),
            'pending_apps'        => Application::where('status', 'pending')->count(),
            'classes_count'       => SchoolClass::count(),
            'recent_applications' => Application::latest()->take(5)->get(),
            'recent_behaviour'    => BehaviourRecord::with('student')->latest()->take(5)->get(),
            'announcements'       => Announcement::where('is_active', true)->latest()->take(5)->get(),
        ]);
    }

    public function announcements()
    {
        return response()->json(Announcement::with('createdBy')->latest()->paginate(20));
    }

    public function storeAnnouncement(Request $request)
    {
        $data = $request->validate([
            'title'           => 'required|string|max:255',
            'content'         => 'required|string',
            'audience'        => 'required|in:all,parents,students,staff,specific_class',
            'target_class_id' => 'nullable|exists:classes,id',
        ]);
        $data['created_by'] = auth()->id();
        $ann = Announcement::create($data);
        return response()->json(['message' => 'Announcement created.', 'announcement' => $ann], 201);
    }

    public function behaviourCases()
    {
        $cases = BehaviourRecord::with(['student', 'schoolClass', 'recordedBy'])->latest()->paginate(20);
        return response()->json($cases);
    }

    public function reviewBehaviour(Request $request, BehaviourRecord $record)
    {
        $request->validate(['headmaster_review' => 'required|string']);
        $record->update(['headmaster_review' => $request->headmaster_review, 'reviewed_at' => now()]);
        return response()->json(['message' => 'Review saved.', 'record' => $record]);
    }
}
