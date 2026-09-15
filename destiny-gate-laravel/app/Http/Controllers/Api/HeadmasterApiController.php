<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Application;
use App\Models\BehaviourRecord;
use App\Models\Student;
use App\Models\Staff;
use App\Models\SchoolClass;
use App\Services\OpenAiSchoolAssistant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class HeadmasterApiController extends Controller
{
    public function dashboard()
    {
        return response()->json($this->dashboardSnapshot());
    }

    public function aiInsights(Request $request, OpenAiSchoolAssistant $assistant)
    {
        $validated = $request->validate([
            'focus' => 'nullable|string|max:160',
        ]);

        try {
            $insights = $assistant->generateHeadmasterInsights([
                'focus' => $validated['focus'] ?? 'overall school operations',
                'dashboard' => $this->dashboardSnapshot(),
            ]);
        } catch (Throwable $exception) {
            $status = $assistant->isConfigured() ? 502 : 503;

            return response()->json([
                'message' => $exception->getMessage(),
            ], $status);
        }

        return response()->json($insights);
    }

    public function draftAnnouncement(Request $request, OpenAiSchoolAssistant $assistant)
    {
        $validated = $request->validate([
            'topic' => 'required|string|max:255',
            'audience' => 'required|in:all,parents,students,staff,specific_class',
            'tone' => 'nullable|string|max:80',
            'objective' => 'nullable|string|max:500',
        ]);

        try {
            $draft = $assistant->draftAnnouncement([
                'topic' => $validated['topic'],
                'audience' => $validated['audience'],
                'tone' => $validated['tone'] ?? 'professional and encouraging',
                'objective' => $validated['objective'] ?? 'Share a clear and actionable school update.',
                'school_snapshot' => [
                    'total_students' => Student::where('status', 'active')->count(),
                    'total_staff' => Staff::where('is_active', true)->count(),
                ],
            ]);
        } catch (Throwable $exception) {
            $status = $assistant->isConfigured() ? 502 : 503;

            return response()->json([
                'message' => $exception->getMessage(),
            ], $status);
        }

        return response()->json($draft);
    }

    private function dashboardSnapshot(): array
    {
        return [
            'total_students'      => Student::where('status', 'active')->count(),
            'total_staff'         => Staff::where('is_active', true)->count(),
            // Real Finance module data (finance_payments), not the legacy/abandoned
            // Payment model — that table has a couple of leftover demo rows and nothing
            // to do with actual billing, which all runs through student_bills now.
            'fees_collected'      => (float) DB::table('finance_payments')->where('status', 'active')->whereYear('payment_date', date('Y'))->sum('amount'),
            'behaviour_cases'     => BehaviourRecord::whereYear('issue_date', date('Y'))->count(),
            'pending_apps'        => Application::where('status', 'pending')->count(),
            'classes_count'       => SchoolClass::count(),
            'recent_applications' => Application::latest()->take(5)->get(),
            'recent_behaviour'    => BehaviourRecord::with('student')->latest()->take(5)->get(),
            'announcements'       => Announcement::where('is_active', true)->latest()->take(5)->get(),
        ];
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
