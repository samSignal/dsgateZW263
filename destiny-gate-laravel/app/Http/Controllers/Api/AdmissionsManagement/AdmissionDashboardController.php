<?php

namespace App\Http\Controllers\Api\AdmissionsManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdmissionDashboardController extends Controller
{
    public function dashboardStats(Request $request)
    {
        $applications = DB::table('admission_applications')->get();
        
        $total = $applications->count();
        $submitted = $applications->where('status', 'submitted')->count();
        $underReview = $applications->where('status', 'under_review')->count();
        $accepted = $applications->where('status', 'accepted')->count();
        $rejected = $applications->where('status', 'rejected')->count();
        $waitlisted = $applications->where('status', 'waitlisted')->count();
        $enrollmentPending = $applications->where('status', 'enrollment_pending')->count();

        $recentApplications = DB::table('admission_applications')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $upcomingInterviews = DB::table('admission_interviews')
            ->join('admission_applications', 'admission_interviews.application_id', '=', 'admission_applications.id')
            ->select('admission_interviews.*', 'admission_applications.student_first_name', 'admission_applications.student_last_name', 'admission_applications.application_number')
            ->where('admission_interviews.status', 'scheduled')
            ->where('admission_interviews.interview_date', '>=', now()->toDateString())
            ->orderBy('admission_interviews.interview_date', 'asc')
            ->orderBy('admission_interviews.interview_time', 'asc')
            ->limit(5)
            ->get();

        $applicationsRequiringDocuments = DB::table('admission_applications')
            ->where('status', 'documents_required')
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'kpis' => [
                    'total' => $total,
                    'submitted' => $submitted,
                    'under_review' => $underReview,
                    'accepted' => $accepted,
                    'rejected' => $rejected,
                    'waitlisted' => $waitlisted,
                    'enrollment_pending' => $enrollmentPending
                ],
                'recent_applications' => $recentApplications,
                'upcoming_interviews' => $upcomingInterviews,
                'applications_requiring_documents' => $applicationsRequiringDocuments
            ]
        ]);
    }
}
