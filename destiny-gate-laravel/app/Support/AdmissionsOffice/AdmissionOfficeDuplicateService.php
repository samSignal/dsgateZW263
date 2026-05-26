<?php

namespace App\Support\AdmissionsOffice;

use App\Support\Admissions\AdmissionAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdmissionOfficeDuplicateService
{
    private static function snapshot(int $applicationId): ?array
    {
        $app = DB::table('admission_applications')->where('id', $applicationId)->first();
        if (!$app) return null;

        $docs = DB::table('application_documents as d')
            ->leftJoin('admission_document_reviews as r', 'r.document_id', '=', 'd.id')
            ->where('d.application_id', $applicationId)
            ->orderBy('d.document_type')
            ->orderByDesc('d.version')
            ->select(
                'd.id',
                'd.document_type',
                'd.version',
                'd.file_name',
                'd.file_path',
                'd.uploaded_at',
                'd.superseded_at',
                'r.verification_status',
                'r.reviewed_by',
                'r.reviewed_at',
                'r.rejection_reason'
            )
            ->get();

        $latestByType = [];
        foreach ($docs as $d) {
            if (!empty($d->superseded_at)) continue;
            if (!isset($latestByType[$d->document_type])) $latestByType[$d->document_type] = $d;
        }

        return [
            'application' => [
                'id' => (int) $app->id,
                'application_number' => (string) $app->application_number,
                'application_type' => (string) $app->application_type,
                'academic_year_id' => $app->academic_year_id ? (int) $app->academic_year_id : null,
                'applying_form_id' => $app->applying_form_id ? (int) $app->applying_form_id : null,
                'preferred_category_id' => $app->preferred_category_id ? (int) $app->preferred_category_id : null,
                'lifecycle_state' => AdmissionOfficeStates::normalize($app->lifecycle_state ?? null, $app->status ?? null),
                'status' => (string) $app->status,
                'submitted_at' => $app->submitted_at,
                'student_first_name' => $app->student_first_name,
                'student_last_name' => $app->student_last_name,
                'date_of_birth' => (string) $app->date_of_birth,
                'birth_certificate_number' => $app->birth_certificate_number,
                'student_national_id' => $app->student_national_id,
                'guardian_name' => $app->guardian_name,
                'guardian_phone' => $app->guardian_phone,
                'guardian_email' => $app->guardian_email,
            ],
            'documents_latest' => array_values(array_map(function ($d) {
                return [
                    'id' => (int) $d->id,
                    'document_type' => (string) $d->document_type,
                    'version' => (int) ($d->version ?? 1),
                    'file_name' => (string) $d->file_name,
                    'file_path' => (string) $d->file_path,
                    'uploaded_at' => $d->uploaded_at,
                    'verification_status' => $d->verification_status,
                    'reviewed_at' => $d->reviewed_at,
                    'rejection_reason' => $d->rejection_reason,
                ];
            }, $latestByType)),
        ];
    }

    public static function candidates(int $applicationId): array
    {
        $app = DB::table('admission_applications')->where('id', $applicationId)->first();
        if (!$app) return [];

        $birthNorm = (string) ($app->birth_certificate_number_normalized ?? '');
        $dob = (string) ($app->date_of_birth ?? '');
        $fn = strtolower(trim((string) ($app->student_first_name ?? '')));
        $ln = strtolower(trim((string) ($app->student_last_name ?? '')));

        $q = DB::table('admission_applications')
            ->where('id', '!=', $applicationId)
            ->where('status', '!=', 'draft')
            ->where(function ($w) use ($birthNorm, $dob, $fn, $ln) {
                if ($birthNorm !== '') {
                    $w->where('birth_certificate_number_normalized', $birthNorm);
                }
                if ($dob !== '' && ($fn !== '' || $ln !== '')) {
                    $w->orWhere(function ($w2) use ($dob, $fn, $ln) {
                        $w2->where('date_of_birth', $dob);
                        if ($fn !== '') $w2->whereRaw('LOWER(student_first_name) = ?', [$fn]);
                        if ($ln !== '') $w2->whereRaw('LOWER(student_last_name) = ?', [$ln]);
                    });
                }
            })
            ->orderByDesc('submitted_at')
            ->limit(20)
            ->get();

        return $q->map(function ($row) use ($birthNorm, $dob, $fn, $ln) {
            $score = 0;
            if ($birthNorm !== '' && (string) ($row->birth_certificate_number_normalized ?? '') === $birthNorm) $score += 70;
            if ($dob !== '' && (string) ($row->date_of_birth ?? '') === $dob) $score += 15;
            if ($fn !== '' && strtolower((string) ($row->student_first_name ?? '')) === $fn) $score += 10;
            if ($ln !== '' && strtolower((string) ($row->student_last_name ?? '')) === $ln) $score += 10;
            return [
                'id' => (int) $row->id,
                'application_number' => (string) $row->application_number,
                'lifecycle_state' => $row->lifecycle_state,
                'status' => (string) $row->status,
                'academic_year_id' => (int) $row->academic_year_id,
                'student_first_name' => $row->student_first_name,
                'student_last_name' => $row->student_last_name,
                'date_of_birth' => (string) $row->date_of_birth,
                'guardian_email' => $row->guardian_email,
                'similarity_score' => $score,
            ];
        })->all();
    }

    public static function link(int $canonicalId, int $relatedId, string $status, string $reason, int $actorUserId, Request $request): array
    {
        $status = strtolower(trim($status));
        if (!in_array($status, ['flagged', 'merged', 'invalid'], true)) {
            return ['ok' => false, 'code' => 'INVALID_STATUS', 'message' => 'Invalid duplicate status.'];
        }

        if ($canonicalId === $relatedId) {
            return ['ok' => false, 'code' => 'INVALID', 'message' => 'Cannot link application to itself.'];
        }

        $now = now();
        return DB::transaction(function () use ($canonicalId, $relatedId, $status, $reason, $actorUserId, $request, $now) {
            $canon = DB::table('admission_applications')->where('id', $canonicalId)->lockForUpdate()->first();
            $rel = DB::table('admission_applications')->where('id', $relatedId)->lockForUpdate()->first();
            if (!$canon || !$rel) return ['ok' => false, 'code' => 'NOT_FOUND', 'message' => 'Application not found.'];

            DB::table('admission_duplicate_links')->updateOrInsert(
                ['canonical_application_id' => $canonicalId, 'related_application_id' => $relatedId],
                [
                    'status' => $status,
                    'reason' => $reason !== '' ? $reason : null,
                    'created_by' => $actorUserId,
                    'resolved_at' => in_array($status, ['merged', 'invalid'], true) ? $now : null,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );

            if ($status === 'flagged') {
                DB::table('admission_applications')->where('id', $relatedId)->update([
                    'lifecycle_state' => AdmissionOfficeStates::DUPLICATE_FLAGGED,
                    'status_updated_at' => $now,
                    'updated_at' => $now,
                ]);
                AdmissionAudit::log($relatedId, 'admissions', $actorUserId, 'admissions.office.duplicate_flagged', [
                    'canonical_application_id' => $canonicalId,
                    'reason' => $reason,
                ], 'info', $request);
            }

            if ($status === 'invalid') {
                DB::table('admission_applications')->where('id', $relatedId)->update([
                    'lifecycle_state' => AdmissionOfficeStates::DUPLICATE_INVALID,
                    'status' => 'rejected',
                    'status_updated_at' => $now,
                    'updated_at' => $now,
                ]);
                AdmissionAudit::log($relatedId, 'admissions', $actorUserId, 'admissions.office.duplicate_invalidated', [
                    'canonical_application_id' => $canonicalId,
                    'reason' => $reason,
                ], 'info', $request);
            }

            if ($status === 'merged') {
                DB::table('admission_applications')->where('id', $relatedId)->update([
                    'lifecycle_state' => AdmissionOfficeStates::ARCHIVED,
                    'archived_at' => $now,
                    'archived_reason' => 'duplicate_merged',
                    'status_updated_at' => $now,
                    'updated_at' => $now,
                ]);
                AdmissionAudit::log($relatedId, 'admissions', $actorUserId, 'admissions.office.duplicate_merged', [
                    'canonical_application_id' => $canonicalId,
                    'reason' => $reason,
                ], 'info', $request);
            }

            return ['ok' => true];
        });
    }

    public static function compare(int $canonicalId, int $candidateId): array
    {
        $canon = self::snapshot($canonicalId);
        $cand = self::snapshot($candidateId);
        if (!$canon || !$cand) return ['ok' => false, 'code' => 'NOT_FOUND', 'message' => 'Application not found.'];

        $link = DB::table('admission_duplicate_links')
            ->where('canonical_application_id', $canonicalId)
            ->where('related_application_id', $candidateId)
            ->first();

        $candidates = self::candidates($canonicalId);
        $candScore = null;
        foreach ($candidates as $c) {
            if ((int) $c['id'] === $candidateId) {
                $candScore = $c['similarity_score'] ?? null;
                break;
            }
        }

        return [
            'ok' => true,
            'canonical' => $canon,
            'candidate' => $cand,
            'similarity' => [
                'score' => $candScore,
            ],
            'link' => $link ? [
                'status' => (string) $link->status,
                'reason' => $link->reason,
                'resolved_at' => $link->resolved_at,
            ] : null,
        ];
    }

    public static function reopen(int $canonicalId, int $relatedId, string $reason, int $actorUserId, Request $request): array
    {
        $now = now();
        return DB::transaction(function () use ($canonicalId, $relatedId, $reason, $actorUserId, $request, $now) {
            $canon = DB::table('admission_applications')->where('id', $canonicalId)->lockForUpdate()->first();
            $rel = DB::table('admission_applications')->where('id', $relatedId)->lockForUpdate()->first();
            if (!$canon || !$rel) return ['ok' => false, 'code' => 'NOT_FOUND', 'message' => 'Application not found.'];

            $existing = DB::table('admission_duplicate_links')
                ->where('canonical_application_id', $canonicalId)
                ->where('related_application_id', $relatedId)
                ->lockForUpdate()
                ->first();

            if (!$existing) return ['ok' => false, 'code' => 'NOT_FOUND', 'message' => 'Duplicate link not found.'];

            DB::table('admission_duplicate_links')
                ->where('id', $existing->id)
                ->update([
                    'status' => 'flagged',
                    'reason' => $reason !== '' ? $reason : null,
                    'resolved_at' => null,
                    'updated_at' => $now,
                ]);

            DB::table('admission_applications')->where('id', $relatedId)->update([
                'lifecycle_state' => AdmissionOfficeStates::DUPLICATE_FLAGGED,
                'archived_at' => null,
                'archived_reason' => null,
                'status_updated_at' => $now,
                'updated_at' => $now,
            ]);

            AdmissionAudit::log($relatedId, 'admissions', $actorUserId, 'admissions.office.duplicate_reopened', [
                'canonical_application_id' => $canonicalId,
                'from_status' => (string) ($existing->status ?? ''),
                'to_status' => 'flagged',
                'reason' => $reason,
            ], 'warning', $request);

            return ['ok' => true];
        });
    }
}
