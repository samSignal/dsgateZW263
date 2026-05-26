<?php

namespace App\Http\Controllers\Api\AdmissionsOffice\V2;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Admissions\AdmissionRequirements;
use App\Support\Admissions\AdmissionResponder;
use App\Support\AdmissionsOffice\AdmissionOfficeAccess;
use App\Support\AdmissionsOffice\AdmissionOfficeAssignmentService;
use App\Support\AdmissionsOffice\AdmissionOfficeDuplicateService;
use App\Support\AdmissionsOffice\AdmissionOfficeNotesService;
use App\Support\AdmissionsOffice\AdmissionOfficeStates;
use App\Support\AdmissionsOffice\AdmissionOfficeStateMachine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    public function reviewers(Request $request)
    {
        if ($deny = AdmissionOfficeAccess::requirePermission($request, 'admissions.office.review_assign')) return $deny;

        $users = User::permission('admissions.office.review_view')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);

        return AdmissionResponder::ok([
            'items' => $users->map(function ($u) {
                return [
                    'id' => (int) $u->id,
                    'name' => (string) $u->name,
                    'email' => (string) $u->email,
                    'role' => (string) $u->role,
                    'roles' => $u->getRoleNames()->values()->all(),
                ];
            })->all(),
        ]);
    }

    public function show(Request $request, int $id)
    {
        if ($deny = AdmissionOfficeAccess::requirePermission($request, 'admissions.office.review_view')) return $deny;

        $app = DB::table('admission_applications')->where('id', $id)->first();
        if (!$app) return AdmissionResponder::fail('NOT_FOUND', 'Application not found.', 404);

        $mergedRelatedIds = DB::table('admission_duplicate_links')
            ->where('canonical_application_id', $id)
            ->where('status', 'merged')
            ->pluck('related_application_id')
            ->map(fn ($v) => (int) $v)
            ->all();
        $decisionIds = array_values(array_unique(array_merge([$id], $mergedRelatedIds)));

        $docs = DB::table('application_documents as d')
            ->leftJoin('admission_document_reviews as r', 'r.document_id', '=', 'd.id')
            ->whereIn('d.application_id', $decisionIds)
            ->orderBy('d.document_type')
            ->orderByDesc('d.uploaded_at')
            ->orderByDesc('d.version')
            ->select(
                'd.id',
                'd.application_id',
                'd.document_type',
                'd.file_name',
                'd.file_path',
                'd.version',
                'd.uploaded_at',
                'd.superseded_at',
                'r.verification_status',
                'r.reviewed_by',
                'r.reviewed_at',
                'r.rejection_reason',
                'r.verification_notes'
            )
            ->get();

        $required = AdmissionRequirements::requiredDocuments((string) $app->application_type);
        $latestByType = [];
        foreach ($docs as $d) {
            if (!empty($d->superseded_at)) continue;
            if (!isset($latestByType[$d->document_type])) $latestByType[$d->document_type] = $d;
        }

        $docChecklist = array_map(function ($type) use ($latestByType) {
            $d = $latestByType[$type] ?? null;
            return [
                'document_type' => $type,
                'uploaded' => (bool) $d,
                'verification_status' => $d?->verification_status ?? null,
                'document_id' => $d?->id ? (int) $d->id : null,
                'source_application_id' => $d?->application_id ? (int) $d->application_id : null,
                'version' => $d?->version ? (int) $d->version : null,
            ];
        }, $required);

        $notes = DB::table('admission_office_notes as n')
            ->leftJoin('users as u', 'u.id', '=', 'n.created_by')
            ->where('n.application_id', $id)
            ->orderByDesc('n.created_at')
            ->limit(200)
            ->select('n.id', 'n.visibility', 'n.template_key', 'n.message', 'n.created_at', 'n.created_by', 'u.name as created_by_name')
            ->get();

        $audit = DB::table('admission_audit_events')
            ->whereIn('application_id', $decisionIds)
            ->orderByDesc('created_at')
            ->limit(250)
            ->get();

        $assignedReviewer = null;
        if (!empty($app->assigned_reviewer_id)) {
            $assignedReviewer = DB::table('users')->where('id', (int) $app->assigned_reviewer_id)->select('id', 'name', 'role')->first();
        }

        $duplicates = AdmissionOfficeDuplicateService::candidates($id);

        $duplicateLinks = DB::table('admission_duplicate_links')
            ->where(function ($w) use ($id) {
                $w->where('canonical_application_id', $id)->orWhere('related_application_id', $id);
            })
            ->orderByDesc('updated_at')
            ->limit(100)
            ->get();

        $user = $request->user();
        $capabilities = [
            'can_claim' => $user?->can('admissions.office.review_claim') ?? false,
            'can_assign' => $user?->can('admissions.office.review_assign') ?? false,
            'can_decide' => $user?->can('admissions.office.decision') ?? false,
            'can_manage_duplicates' => $user?->can('admissions.office.duplicates') ?? false,
            'can_message' => $user?->can('admissions.office.messages') ?? false,
            'can_override' => $user?->can('admissions.office.override') ?? false,
            'can_transition' => $user?->can('admissions.office.transition') ?? false,
            'can_review_docs' => $user?->can('admissions.office.docs_review') ?? false,
            'can_add_notes' => $user?->can('admissions.office.review_notes') ?? false,
        ];

        return AdmissionResponder::ok([
            'application' => [
                'id' => (int) $app->id,
                'application_number' => (string) $app->application_number,
                'application_type' => (string) $app->application_type,
                'academic_year_id' => $app->academic_year_id ? (int) $app->academic_year_id : null,
                'applying_form_id' => $app->applying_form_id ? (int) $app->applying_form_id : null,
                'preferred_category_id' => $app->preferred_category_id ? (int) $app->preferred_category_id : null,
                'student_first_name' => $app->student_first_name,
                'student_last_name' => $app->student_last_name,
                'gender' => $app->gender,
                'date_of_birth' => (string) $app->date_of_birth,
                'birth_certificate_number' => $app->birth_certificate_number,
                'student_national_id' => $app->student_national_id,
                'guardian_name' => $app->guardian_name,
                'guardian_phone' => $app->guardian_phone,
                'guardian_email' => $app->guardian_email,
                'status' => (string) $app->status,
                'lifecycle_state' => AdmissionOfficeStates::normalize($app->lifecycle_state, $app->status),
                'submitted_at' => $app->submitted_at,
                'status_updated_at' => $app->status_updated_at,
                'assigned_reviewer_id' => $app->assigned_reviewer_id ? (int) $app->assigned_reviewer_id : null,
                'assigned_at' => $app->assigned_at,
                'review_started_at' => $app->review_started_at,
                'review_completed_at' => $app->review_completed_at,
            ],
            'documents' => $docs,
            'required_documents' => $required,
            'document_checklist' => $docChecklist,
            'notes' => $notes,
            'audit_events' => $audit,
            'duplicate_links' => $duplicateLinks,
            'merged_related_application_ids' => $mergedRelatedIds,
            'assigned_reviewer' => $assignedReviewer,
            'duplicate_candidates' => $duplicates,
            'capabilities' => $capabilities,
        ]);
    }

    public function claim(Request $request, int $id)
    {
        if ($deny = AdmissionOfficeAccess::requirePermission($request, 'admissions.office.review_claim')) return $deny;
        $userId = (int) $request->user()->id;

        $res = AdmissionOfficeAssignmentService::claim($id, $userId, $request);
        if (!$res['ok']) return AdmissionResponder::fail($res['code'], $res['message'], $res['code'] === 'ALREADY_CLAIMED' ? 409 : 404, [], $res);
        return AdmissionResponder::ok($res);
    }

    public function assign(Request $request, int $id)
    {
        if ($deny = AdmissionOfficeAccess::requirePermission($request, 'admissions.office.review_assign')) return $deny;
        $data = $request->validate([
            'assignee_user_id' => 'required|integer|exists:users,id',
            'reason' => 'required|string|max:1000',
        ]);

        $res = AdmissionOfficeAssignmentService::assign($id, (int) $request->user()->id, (int) $data['assignee_user_id'], (string) $data['reason'], $request);
        if (!$res['ok']) return AdmissionResponder::fail($res['code'], $res['message'], 404);
        return AdmissionResponder::ok($res);
    }

    public function release(Request $request, int $id)
    {
        if ($deny = AdmissionOfficeAccess::requirePermission($request, 'admissions.office.review_claim')) return $deny;
        $data = $request->validate([
            'reason' => 'nullable|string|max:1000',
            'force' => 'nullable|boolean',
        ]);

        $force = (bool) ($data['force'] ?? false);
        if ($force && ($deny = AdmissionOfficeAccess::requirePermission($request, 'admissions.office.override'))) return $deny;

        $res = AdmissionOfficeAssignmentService::release($id, (int) $request->user()->id, (string) ($data['reason'] ?? ''), $force, $request);
        if (!$res['ok']) return AdmissionResponder::fail($res['code'], $res['message'], $res['code'] === 'FORBIDDEN' ? 403 : 404);
        return AdmissionResponder::ok($res);
    }

    public function transition(Request $request, int $id)
    {
        if ($deny = AdmissionOfficeAccess::requirePermission($request, 'admissions.office.transition')) return $deny;
        $data = $request->validate([
            'to_state' => 'required|string|max:40',
            'reason' => 'required|string|max:1000',
            'override' => 'nullable|boolean',
            'force' => 'nullable|boolean',
            'notify_applicant' => 'nullable|boolean',
            'notify_title' => 'nullable|string|max:140',
            'notify_message' => 'nullable|string|max:5000',
            'notify_template_key' => 'nullable|string|max:80',
        ]);

        $to = strtoupper(trim((string) $data['to_state']));
        $reason = (string) $data['reason'];
        $override = (bool) ($data['override'] ?? false);
        $force = (bool) ($data['force'] ?? false);
        if ($force) $override = true;

        if ($override && ($deny = AdmissionOfficeAccess::requirePermission($request, 'admissions.office.override'))) return $deny;

        if (in_array($to, [AdmissionOfficeStates::ACCEPTED, AdmissionOfficeStates::REJECTED, AdmissionOfficeStates::WAITLISTED], true)) {
            if ($deny = AdmissionOfficeAccess::requirePermission($request, 'admissions.office.decision')) return $deny;
        }

        $meta = AdmissionOfficeAccess::actorMeta($request);
        if ($override) $meta['override'] = true;
        if ($force) $meta['force'] = true;

        $res = AdmissionOfficeStateMachine::transition($id, $to, $reason, $request, $meta);
        if (!$res['ok']) return AdmissionResponder::fail($res['code'], $res['message'], 409, [], $res);

        if (!empty($data['notify_applicant']) && in_array($to, [AdmissionOfficeStates::ACCEPTED, AdmissionOfficeStates::REJECTED, AdmissionOfficeStates::WAITLISTED], true)) {
            $title = trim((string) ($data['notify_title'] ?? ''));
            $message = trim((string) ($data['notify_message'] ?? ''));
            $templateKey = trim((string) ($data['notify_template_key'] ?? ''));

            if ($message === '' && $templateKey !== '') {
                AdmissionOfficeNotesService::sendApplicantMessage(
                    $id,
                    (int) $request->user()->id,
                    $title,
                    '',
                    $templateKey,
                    $request
                );
            } elseif ($message !== '') {
                AdmissionOfficeNotesService::sendApplicantMessage(
                    $id,
                    (int) $request->user()->id,
                    $title !== '' ? $title : 'Admissions update',
                    $message,
                    $templateKey !== '' ? $templateKey : 'custom',
                    $request
                );
            }
        }

        return AdmissionResponder::ok($res);
    }
}
