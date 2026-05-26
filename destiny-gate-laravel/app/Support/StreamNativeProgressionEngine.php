<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class StreamNativeProgressionEngine
{
    public static function decide(int $studentId, int $academicYearId, int $termId): array
    {
        $resolved = StudentStreamResolver::resolveByStudentId($studentId);
        abort_if(!$resolved || !$resolved->stream_id || !$resolved->form_id, 422, 'Student has no stream mapping.');

        $rule = StreamNativeRules::forContext($academicYearId, (int) $resolved->form_id, $resolved->category_id ? (int) $resolved->category_id : null);

        $agg = DB::table('results_aggregates')
            ->where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->where('term_id', $termId)
            ->first();

        $avg = $agg?->term_average !== null ? (float) $agg->term_average : null;
        $withheld = (bool) ($agg?->is_withheld ?? false);

        $requiredSubjectIds = self::jsonIds($rule->required_subject_ids_json);
        $minPass = (float) $rule->minimum_pass_mark;

        $subjectRows = DB::table('transcript_subject_history')
            ->where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->where('term_id', $termId)
            ->get();

        $bySubject = [];
        foreach ($subjectRows as $row) {
            $bySubject[(int) $row->subject_id] = $row->subject_average !== null ? (float) $row->subject_average : null;
        }

        $failed = 0;
        foreach ($bySubject as $subjectId => $subAvg) {
            if ($subAvg === null) continue;
            if ($subAvg < $minPass) $failed++;
        }

        $missingRequired = [];
        foreach ($requiredSubjectIds as $subjectId) {
            if (!array_key_exists((int) $subjectId, $bySubject) || $bySubject[(int) $subjectId] === null) $missingRequired[] = (int) $subjectId;
        }

        $decision = 'pending';
        if ($withheld) {
            $decision = 'withheld';
        } else {
            if (!empty($missingRequired)) {
                $decision = 'incomplete';
            } else {
                $promotionMin = $rule->promotion_min_average !== null ? (float) $rule->promotion_min_average : null;
                $promotionMaxFailed = $rule->promotion_max_failed_subjects !== null ? (int) $rule->promotion_max_failed_subjects : null;
                $repeatBelow = $rule->repeat_below_average !== null ? (float) $rule->repeat_below_average : null;
                $suppBelow = $rule->supplementary_below_average !== null ? (float) $rule->supplementary_below_average : null;

                if ($avg === null) {
                    $decision = 'incomplete';
                } else {
                    $repeat = $repeatBelow !== null && $avg < $repeatBelow;
                    $supp = $suppBelow !== null && $avg < $suppBelow;

                    if ($repeat) {
                        $decision = 'repeat';
                    } elseif ($supp) {
                        $decision = 'supplementary';
                    } else {
                        $okAvg = $promotionMin === null || $avg >= $promotionMin;
                        $okFailed = $promotionMaxFailed === null || $failed <= $promotionMaxFailed;
                        $decision = ($okAvg && $okFailed) ? 'promote' : 'repeat';
                    }
                }
            }
        }

        return [
            'student_id' => $studentId,
            'academic_year_id' => $academicYearId,
            'term_id' => $termId,
            'stream_id' => (int) $resolved->stream_id,
            'form_id' => (int) $resolved->form_id,
            'category_id' => $resolved->category_id ? (int) $resolved->category_id : null,
            'term_average' => $avg,
            'failed_subjects' => $failed,
            'missing_required_subject_ids' => $missingRequired,
            'decision' => $decision,
        ];
    }

    public static function applyDecision(
        int $studentId,
        int $fromAcademicYearId,
        int $fromTermId,
        int $toAcademicYearId,
        int $toTermId,
        int $toStreamId,
        string $decision,
        ?int $updatedBy = null,
        bool $manualOverride = false
    ): void {
        $fromResolved = StudentStreamResolver::resolveByStudentId($studentId);
        abort_if(!$fromResolved || !$fromResolved->stream_id || !$fromResolved->form_id, 422, 'Student has no stream mapping.');

        $toStream = DB::table('streams')->where('id', $toStreamId)->first();
        abort_if(!$toStream, 422, 'Target stream not found.');

        $toCategoryId = $toStream->category_id !== null ? (int) $toStream->category_id : null;
        $toFormId = (int) $toStream->form_id;

        $status = match ($decision) {
            'promote' => 'enrolled',
            'repeat' => 'repeated',
            'supplementary' => 'supplementary',
            'graduate' => 'graduated',
            default => abort(422, 'Invalid decision.'),
        };

        DB::beginTransaction();
        try {
            DB::table('student_enrollments')->updateOrInsert(
                [
                    'student_id' => $studentId,
                    'academic_year_id' => $toAcademicYearId,
                    'term_id' => $toTermId,
                ],
                [
                    'form_id' => $toFormId,
                    'stream_id' => $toStreamId,
                    'category_id' => $toCategoryId,
                    'enrollment_status' => $status,
                    'promoted_from_stream_id' => (int) $fromResolved->stream_id,
                    'promoted_to_stream_id' => $toStreamId,
                    'repeated' => $decision === 'repeat',
                    'graduated' => $decision === 'graduate',
                    'updated_by' => $updatedBy,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            if ($manualOverride) {
                DB::table('student_enrollments')
                    ->where('student_id', $studentId)
                    ->where('academic_year_id', $toAcademicYearId)
                    ->where('term_id', $toTermId)
                    ->update(['updated_by' => $updatedBy, 'updated_at' => now()]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private static function jsonIds(?string $json): array
    {
        if (!$json) return [];
        $arr = json_decode($json, true);
        if (!is_array($arr)) return [];
        $ids = [];
        foreach ($arr as $v) {
            if (is_int($v) || ctype_digit((string) $v)) $ids[] = (int) $v;
        }
        return array_values(array_unique($ids));
    }
}

