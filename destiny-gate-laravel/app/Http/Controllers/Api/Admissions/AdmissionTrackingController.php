<?php

namespace App\Http\Controllers\Api\Admissions;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdmissionTrackingController extends Controller
{
    private array $progress = [
        'draft' => 5,
        'submitted' => 10,
        'under_review' => 30,
        'interview_scheduled' => 50,
        'documents_required' => 55,
        'waitlisted' => 65,
        'accepted' => 80,
        'enrollment_pending' => 90,
        'enrolled' => 100,
        'rejected' => 100,
    ];

    public function track(Request $request)
    {
        $request->validate([
            'tracking_token' => 'nullable|string|max:20',
            'application_number' => 'nullable|string|max:30',
            'date_of_birth' => 'nullable|date',
        ]);

        $app = $this->resolveApplication($request);
        if (!$app) {
            return response()->json(['message' => 'Application not found or verification failed.'], 404);
        }

        $documents = DB::table('application_documents')
            ->where('application_id', $app->id)
            ->orderBy('document_type')
            ->get();

        $notifications = DB::table('application_notifications')
            ->where('application_id', $app->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'application' => $app,
            'progress_percentage' => $this->progress[$app->status] ?? (int) $app->progress_percentage,
            'timeline' => $this->buildTimeline($app->status),
            'documents' => $documents,
            'document_checklist' => $this->documentChecklist($app->application_type, $documents),
            'notifications' => $notifications,
        ]);
    }

    public function timeline(Request $request)
    {
        $app = $this->resolveApplication($request);
        if (!$app) {
            return response()->json(['message' => 'Application not found or verification failed.'], 404);
        }

        return response()->json($this->buildTimeline($app->status));
    }

    public function notifications(Request $request)
    {
        $app = $this->resolveApplication($request);
        if (!$app) {
            return response()->json(['message' => 'Application not found or verification failed.'], 404);
        }

        return response()->json(
            DB::table('application_notifications')
                ->where('application_id', $app->id)
                ->orderByDesc('created_at')
                ->get()
        );
    }

    private function resolveApplication(Request $request): ?object
    {
        $query = DB::table('admission_applications as aa')
            ->leftJoin('forms', 'aa.applying_form_id', '=', 'forms.id')
            ->leftJoin('academic_years', 'aa.academic_year_id', '=', 'academic_years.id')
            ->leftJoin('terms', 'aa.term_id', '=', 'terms.id')
            ->select('aa.*', 'forms.name as applying_form_name', 'academic_years.name as academic_year_name', 'terms.name as term_name');

        if ($request->filled('tracking_token')) {
            $token = strtoupper($request->input('tracking_token'));
            if (!preg_match('/^DGI-[A-Z0-9]{8}$/', $token)) {
                return null;
            }
            return $query->where('aa.tracking_token', $token)->first();
        }

        if ($request->filled('application_number') && $request->filled('date_of_birth')) {
            return $query->where('aa.application_number', $request->input('application_number'))
                ->where('aa.date_of_birth', $request->input('date_of_birth'))
                ->first();
        }

        return null;
    }

    private function buildTimeline(string $status): array
    {
        $order = ['submitted', 'under_review', 'interview_scheduled', 'accepted', 'enrolled'];
        $labels = [
            'submitted' => 'Submitted',
            'under_review' => 'Under Review',
            'interview_scheduled' => 'Interview Scheduled',
            'accepted' => 'Decision',
            'enrolled' => 'Enrollment',
        ];
        $currentIndex = array_search($status, $order, true);
        if ($status === 'documents_required' || $status === 'waitlisted') {
            $currentIndex = 1;
        }
        if ($status === 'enrollment_pending') {
            $currentIndex = 3;
        }
        if ($status === 'rejected') {
            $currentIndex = 3;
        }

        return array_map(function ($key, $index) use ($labels, $currentIndex, $status) {
            $state = $currentIndex !== false && $index <= $currentIndex ? 'complete' : 'pending';
            if ($currentIndex !== false && $index === $currentIndex && !in_array($status, ['accepted', 'enrolled', 'rejected'], true)) {
                $state = 'current';
            }
            return ['key' => $key, 'label' => $labels[$key], 'state' => $state];
        }, $order, array_keys($order));
    }

    private function documentChecklist(string $type, $documents): array
    {
        $required = $type === 'transfer'
            ? ['birth_certificate', 'passport_photo', 'latest_report', 'transfer_letter', 'discipline_record']
            : ['birth_certificate', 'passport_photo', 'grade7_report'];
        $uploaded = $documents->pluck('document_type')->all();

        return array_map(fn ($item) => [
            'document_type' => $item,
            'uploaded' => in_array($item, $uploaded, true),
        ], $required);
    }
}
