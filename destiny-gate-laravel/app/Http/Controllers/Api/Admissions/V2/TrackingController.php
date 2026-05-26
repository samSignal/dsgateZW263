<?php

namespace App\Http\Controllers\Api\Admissions\V2;

use App\Http\Controllers\Controller;
use App\Support\Admissions\AdmissionAccessService;
use App\Support\Admissions\AdmissionRequirements;
use App\Support\Admissions\AdmissionResponder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TrackingController extends Controller
{
    private array $progress = [
        'DRAFT_ACTIVE' => 5,
        'DRAFT_REACTIVATED' => 5,
        'SUBMITTED' => 15,
        'UNDER_REVIEW' => 40,
        'DOCUMENTS_REQUIRED' => 55,
        'OFFER_PENDING' => 70,
        'OFFERED' => 80,
    ];

    public function track(Request $request)
    {
        [$app, $session] = AdmissionAccessService::resolveFromRequest($request);
        if (!$app) {
            return AdmissionResponder::fail('UNAUTHORIZED', 'Token verification failed.', 403);
        }

        $documents = DB::table('application_documents')
            ->where('application_id', $app->id)
            ->whereNull('superseded_at')
            ->orderBy('document_type')
            ->orderByDesc('version')
            ->select('id', 'document_type', 'file_name', 'file_path', 'version', 'uploaded_at')
            ->get();

        $required = AdmissionRequirements::requiredDocuments((string) $app->application_type);
        $uploadedTypes = $documents->pluck('document_type')->all();
        $checklist = array_map(fn ($t) => ['document_type' => $t, 'uploaded' => in_array($t, $uploadedTypes, true)], $required);

        $notifications = DB::table('application_notifications')
            ->where('application_id', $app->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $lifecycle = (string) ($app->lifecycle_state ?? '');
        $progress = $this->progress[$lifecycle] ?? (int) ($app->progress_percentage ?? 0);

        return AdmissionResponder::ok([
            'application' => [
                'id' => (int) $app->id,
                'application_number' => (string) $app->application_number,
                'application_type' => (string) $app->application_type,
                'lifecycle_state' => $lifecycle ?: null,
                'status' => (string) $app->status,
                'student_first_name' => $app->student_first_name,
                'student_last_name' => $app->student_last_name,
                'date_of_birth' => (string) $app->date_of_birth,
                'academic_year_id' => (int) $app->academic_year_id,
                'applying_form_id' => (int) $app->applying_form_id,
                'preferred_category_id' => (int) ($app->preferred_category_id ?? 0),
                'submitted_at' => $app->submitted_at,
                'expires_at' => $app->expires_at,
                'archived_at' => $app->archived_at,
                'archived_reason' => $app->archived_reason,
            ],
            'progress_percentage' => $progress,
            'documents' => $documents,
            'required_documents' => $required,
            'document_checklist' => $checklist,
            'notifications' => $notifications,
        ]);
    }
}
