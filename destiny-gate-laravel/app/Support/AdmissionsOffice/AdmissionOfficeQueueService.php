<?php

namespace App\Support\AdmissionsOffice;

use Illuminate\Support\Facades\DB;

class AdmissionOfficeQueueService
{
    public static function list(string $queue, array $filters): array
    {
        $queue = strtoupper(trim($queue));

        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 25)));
        $page = max(1, (int) ($filters['page'] ?? 1));
        $offset = ($page - 1) * $perPage;

        $pendingDocSub = DB::table('admission_document_reviews')
            ->selectRaw("application_id, SUM(CASE WHEN verification_status = 'reupload_requested' THEN 1 ELSE 0 END) as pending_doc_requests")
            ->groupBy('application_id');

        $duplicateRelatedSub = DB::table('admission_duplicate_links')
            ->selectRaw("related_application_id as application_id, MAX(CASE WHEN status = 'flagged' THEN 1 ELSE 0 END) as has_flagged_link, MAX(CASE WHEN status = 'merged' THEN 1 ELSE 0 END) as has_merged_link, MAX(CASE WHEN status = 'invalid' THEN 1 ELSE 0 END) as has_invalid_link")
            ->groupBy('related_application_id');

        $duplicateCanonicalSub = DB::table('admission_duplicate_links')
            ->selectRaw("canonical_application_id as application_id, SUM(CASE WHEN status = 'merged' THEN 1 ELSE 0 END) as merged_related_count")
            ->groupBy('canonical_application_id');

        $q = DB::table('admission_applications as aa')
            ->leftJoin('users as u', 'u.id', '=', 'aa.assigned_reviewer_id')
            ->leftJoinSub($pendingDocSub, 'pdr', function ($join) {
                $join->on('pdr.application_id', '=', 'aa.id');
            })
            ->leftJoinSub($duplicateRelatedSub, 'dlr', function ($join) {
                $join->on('dlr.application_id', '=', 'aa.id');
            })
            ->leftJoinSub($duplicateCanonicalSub, 'dlc', function ($join) {
                $join->on('dlc.application_id', '=', 'aa.id');
            })
            ->select([
                'aa.id',
                'aa.application_number',
                'aa.application_type',
                'aa.academic_year_id',
                'aa.applying_form_id',
                'aa.preferred_category_id',
                'aa.student_first_name',
                'aa.student_last_name',
                'aa.guardian_name',
                'aa.guardian_email',
                'aa.status',
                'aa.lifecycle_state',
                'aa.submitted_at',
                'aa.status_updated_at',
                'aa.assigned_reviewer_id',
                'aa.assigned_at',
                'u.name as assigned_reviewer_name',
                DB::raw('COALESCE(pdr.pending_doc_requests, 0) as pending_doc_requests'),
                DB::raw('COALESCE(dlr.has_flagged_link, 0) as has_flagged_link'),
                DB::raw('COALESCE(dlr.has_merged_link, 0) as has_merged_link'),
                DB::raw('COALESCE(dlr.has_invalid_link, 0) as has_invalid_link'),
                DB::raw('COALESCE(dlc.merged_related_count, 0) as merged_related_count'),
            ])
            ->where('aa.status', '!=', 'draft');

        $state = match ($queue) {
            'NEW_SUBMISSIONS' => AdmissionOfficeStates::SUBMITTED,
            'UNDER_REVIEW' => AdmissionOfficeStates::UNDER_REVIEW,
            'DOCUMENTS_REQUIRED' => AdmissionOfficeStates::DOCUMENTS_REQUIRED,
            'READY_FOR_DECISION' => AdmissionOfficeStates::READY_FOR_DECISION,
            'ACCEPTED' => AdmissionOfficeStates::ACCEPTED,
            'REJECTED' => AdmissionOfficeStates::REJECTED,
            'WAITLISTED' => AdmissionOfficeStates::WAITLISTED,
            'DUPLICATE_FLAGGED' => AdmissionOfficeStates::DUPLICATE_FLAGGED,
            'DUPLICATE_INVALID' => AdmissionOfficeStates::DUPLICATE_INVALID,
            'ARCHIVED' => AdmissionOfficeStates::ARCHIVED,
            default => null,
        };

        if ($state) {
            $q->where(function ($w) use ($state) {
                $w->where('aa.lifecycle_state', $state)->orWhere(function ($w2) use ($state) {
                    $w2->whereNull('aa.lifecycle_state')->where('aa.status', AdmissionOfficeStates::mapToStatus($state, 'under_review'));
                });
            });
        }

        if (!empty($filters['academic_year_id'])) $q->where('aa.academic_year_id', (int) $filters['academic_year_id']);
        if (!empty($filters['applying_form_id'])) $q->where('aa.applying_form_id', (int) $filters['applying_form_id']);
        if (!empty($filters['preferred_category_id'])) $q->where('aa.preferred_category_id', (int) $filters['preferred_category_id']);
        if (!empty($filters['assigned_reviewer_id'])) $q->where('aa.assigned_reviewer_id', (int) $filters['assigned_reviewer_id']);

        if (!empty($filters['from_date'])) $q->whereDate('aa.submitted_at', '>=', (string) $filters['from_date']);
        if (!empty($filters['to_date'])) $q->whereDate('aa.submitted_at', '<=', (string) $filters['to_date']);

        if (!empty($filters['duplicate_only'])) {
            $q->where(function ($w) {
                $w->where('aa.lifecycle_state', AdmissionOfficeStates::DUPLICATE_FLAGGED)
                    ->orWhereRaw('COALESCE(dlr.has_flagged_link, 0) = 1')
                    ->orWhereRaw('COALESCE(dlc.merged_related_count, 0) > 0');
            });
        }

        if (!empty($filters['stale'])) {
            $staleDays = max(1, (int) config('admissions.office.stale_days', 7));
            $driver = DB::getDriverName();
            if ($driver === 'sqlite') {
                $q->whereRaw("aa.submitted_at <= datetime('now', ?)", ['-' . $staleDays . ' days']);
            } else {
                $q->whereRaw('aa.submitted_at <= DATE_SUB(NOW(), INTERVAL ? DAY)', [$staleDays]);
            }
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $search) . '%';
            $q->where(function ($w) use ($like) {
                $w->where('aa.application_number', 'like', $like)
                    ->orWhere('aa.student_first_name', 'like', $like)
                    ->orWhere('aa.student_last_name', 'like', $like)
                    ->orWhere('aa.guardian_name', 'like', $like)
                    ->orWhere('aa.guardian_email', 'like', $like);
            });
        }

        $order = strtolower((string) ($filters['order'] ?? 'newest'));
        if ($order === 'oldest') $q->orderBy('aa.submitted_at', 'asc');
        else $q->orderByDesc('aa.submitted_at');

        $total = (clone $q)->count();
        $items = $q->offset($offset)->limit($perPage)->get()->map(function ($row) {
            $submittedAt = $row->submitted_at ? \Illuminate\Support\Carbon::parse($row->submitted_at) : null;
            $ageDays = $submittedAt ? $submittedAt->diffInDays(now()) : null;
            $staleDays = max(1, (int) config('admissions.office.stale_days', 7));
            $isStale = $ageDays !== null ? ($ageDays >= $staleDays) : false;
            return [
                'id' => (int) $row->id,
                'application_number' => (string) $row->application_number,
                'application_type' => (string) $row->application_type,
                'academic_year_id' => $row->academic_year_id ? (int) $row->academic_year_id : null,
                'applying_form_id' => $row->applying_form_id ? (int) $row->applying_form_id : null,
                'preferred_category_id' => $row->preferred_category_id ? (int) $row->preferred_category_id : null,
                'student_first_name' => $row->student_first_name,
                'student_last_name' => $row->student_last_name,
                'guardian_name' => $row->guardian_name,
                'guardian_email' => $row->guardian_email,
                'status' => (string) $row->status,
                'lifecycle_state' => $row->lifecycle_state,
                'submitted_at' => $row->submitted_at,
                'assigned_reviewer_id' => $row->assigned_reviewer_id ? (int) $row->assigned_reviewer_id : null,
                'assigned_reviewer_name' => $row->assigned_reviewer_name,
                'age_days' => $ageDays,
                'is_stale' => $isStale,
                'pending_doc_requests' => (int) ($row->pending_doc_requests ?? 0),
                'duplicate_flags' => [
                    'has_flagged_link' => (bool) ($row->has_flagged_link ?? 0),
                    'has_merged_link' => (bool) ($row->has_merged_link ?? 0),
                    'has_invalid_link' => (bool) ($row->has_invalid_link ?? 0),
                    'merged_related_count' => (int) ($row->merged_related_count ?? 0),
                ],
            ];
        })->all();

        return [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'items' => $items,
        ];
    }

    public static function metrics(array $filters): array
    {
        $base = DB::table('admission_applications')->where('status', '!=', 'draft');
        if (!empty($filters['academic_year_id'])) $base->where('academic_year_id', (int) $filters['academic_year_id']);

        $counts = (clone $base)
            ->selectRaw("COALESCE(lifecycle_state, UPPER(status)) as st, COUNT(*) as c")
            ->groupBy('st')
            ->pluck('c', 'st')
            ->all();

        $driver = DB::getDriverName();
        $avgExpr = match ($driver) {
            'sqlite' => "CAST((julianday('now') - julianday(submitted_at)) AS REAL)",
            default => "DATEDIFF(NOW(), submitted_at)",
        };
        $avgAge = (clone $base)
            ->whereNotNull('submitted_at')
            ->avg(DB::raw($avgExpr));

        $pendingDocRequests = DB::table('admission_document_reviews as r')
            ->join('admission_applications as aa', 'aa.id', '=', 'r.application_id')
            ->where('aa.status', '!=', 'draft')
            ->where('r.verification_status', 'reupload_requested');
        if (!empty($filters['academic_year_id'])) $pendingDocRequests->where('aa.academic_year_id', (int) $filters['academic_year_id']);

        $duplicateBacklog = DB::table('admission_applications as aa')
            ->where('aa.status', '!=', 'draft')
            ->where('aa.lifecycle_state', AdmissionOfficeStates::DUPLICATE_FLAGGED);
        if (!empty($filters['academic_year_id'])) $duplicateBacklog->where('aa.academic_year_id', (int) $filters['academic_year_id']);

        $duplicateLinksUnresolved = DB::table('admission_duplicate_links as l')
            ->join('admission_applications as aa', 'aa.id', '=', 'l.related_application_id')
            ->where('aa.status', '!=', 'draft')
            ->where('l.status', 'flagged')
            ->whereNull('l.resolved_at');
        if (!empty($filters['academic_year_id'])) $duplicateLinksUnresolved->where('aa.academic_year_id', (int) $filters['academic_year_id']);

        return [
            'counts' => $counts,
            'average_review_age_days' => $avgAge ? (float) $avgAge : 0.0,
            'pending_document_requests' => (int) $pendingDocRequests->count(),
            'duplicate_review_backlog' => (int) $duplicateBacklog->count(),
            'duplicate_link_backlog' => (int) $duplicateLinksUnresolved->count(),
        ];
    }
}
