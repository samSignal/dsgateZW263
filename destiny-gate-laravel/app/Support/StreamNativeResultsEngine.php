<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class StreamNativeResultsEngine
{
    public static function recomputeStudentTerm(int $studentId, int $academicYearId, int $termId, ?int $updatedBy = null): array
    {
        $resolved = StudentStreamResolver::resolveByStudentId($studentId);
        abort_if(!$resolved || !$resolved->stream_id || !$resolved->form_id, 422, 'Student has no stream mapping.');

        $rule = StreamNativeRules::forContext($academicYearId, (int) $resolved->form_id, $resolved->category_id ? (int) $resolved->category_id : null);

        $withheld = false;
        if ($rule->withhold_results_on_balance) {
            $clearance = FinancialClearance::summary($studentId, $academicYearId, $termId);
            $withheld = $clearance['status'] !== 'cleared' && (float) $clearance['outstanding_balance'] > (float) $rule->withhold_balance_threshold;
        }

        $subjectRows = self::subjectAverages($studentId, $academicYearId, $termId);
        $subjectsTotal = count($subjectRows);
        $subjectsEntered = count(array_filter($subjectRows, fn ($r) => $r['subject_average'] !== null));
        $subjectsPassed = count(array_filter($subjectRows, fn ($r) => $r['subject_average'] !== null && (float) $r['subject_average'] >= (float) $rule->minimum_pass_mark));

        $termAverage = null;
        $gpa = null;

        $entered = array_values(array_filter($subjectRows, fn ($r) => $r['subject_average'] !== null));
        if (!empty($entered)) {
            $termAverage = round(array_sum(array_map(fn ($r) => (float) $r['subject_average'], $entered)) / count($entered), 2);
            $points = array_values(array_filter(array_map(fn ($r) => $r['gpa_points'], $entered), fn ($v) => $v !== null));
            $gpa = empty($points) ? null : round(array_sum($points) / count($points), 2);
        }

        DB::beginTransaction();
        try {
            foreach ($subjectRows as $row) {
                DB::table('transcript_subject_history')->updateOrInsert(
                    [
                        'student_id' => $studentId,
                        'academic_year_id' => $academicYearId,
                        'term_id' => $termId,
                        'subject_id' => $row['subject_id'],
                    ],
                    [
                        'form_id' => $resolved->form_id,
                        'stream_id' => $resolved->stream_id,
                        'category_id' => $resolved->category_id,
                        'subject_average' => $row['subject_average'],
                        'grade' => $row['grade'],
                        'gpa_points' => $row['gpa_points'],
                        'is_withheld' => $withheld,
                        'computed_at' => now(),
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }

            DB::table('results_aggregates')->updateOrInsert(
                [
                    'student_id' => $studentId,
                    'academic_year_id' => $academicYearId,
                    'term_id' => $termId,
                ],
                [
                    'form_id' => $resolved->form_id,
                    'stream_id' => $resolved->stream_id,
                    'category_id' => $resolved->category_id,
                    'term_average' => $termAverage,
                    'gpa' => $gpa,
                    'subjects_total' => $subjectsTotal,
                    'subjects_entered' => $subjectsEntered,
                    'subjects_passed' => $subjectsPassed,
                    'is_withheld' => $withheld,
                    'computed_at' => now(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            StreamNativeEnrollmentEngine::ensureEnrollment($studentId, $academicYearId, $termId, $updatedBy);
            self::recomputeStudentYear($studentId, $academicYearId);
            self::recomputeGraduationReadiness($studentId, $academicYearId);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return [
            'student_id' => $studentId,
            'academic_year_id' => $academicYearId,
            'term_id' => $termId,
            'stream_id' => (int) $resolved->stream_id,
            'form_id' => (int) $resolved->form_id,
            'category_id' => $resolved->category_id ? (int) $resolved->category_id : null,
            'term_average' => $termAverage,
            'gpa' => $gpa,
            'subjects_total' => $subjectsTotal,
            'subjects_entered' => $subjectsEntered,
            'subjects_passed' => $subjectsPassed,
            'is_withheld' => $withheld,
        ];
    }

    public static function recomputeStudentYear(int $studentId, int $academicYearId): void
    {
        $resolved = StudentStreamResolver::resolveByStudentId($studentId);
        if (!$resolved) return;

        $rows = DB::table('results_aggregates')
            ->where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->whereNotNull('term_average')
            ->get();

        $withheld = $rows->contains(fn ($r) => (bool) $r->is_withheld);
        $eligible = $rows->filter(fn ($r) => !(bool) $r->is_withheld)->values();

        $avg = null;
        $gpa = null;
        if ($eligible->count() > 0) {
            $avg = round($eligible->avg('term_average'), 2);
            $gpaVals = $eligible->pluck('gpa')->filter(fn ($v) => $v !== null)->values();
            $gpa = $gpaVals->count() ? round($gpaVals->avg(), 2) : null;
        }

        DB::table('transcript_year_aggregates')->updateOrInsert(
            ['student_id' => $studentId, 'academic_year_id' => $academicYearId],
            [
                'form_id' => $resolved->form_id,
                'stream_id' => $resolved->stream_id,
                'category_id' => $resolved->category_id,
                'year_average' => $avg,
                'gpa' => $gpa,
                'is_withheld' => $withheld,
                'computed_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public static function recomputeGraduationReadiness(int $studentId, int $academicYearId): void
    {
        $resolved = StudentStreamResolver::resolveByStudentId($studentId);
        if (!$resolved || !$resolved->form_id) return;

        $form = DB::table('forms')->where('id', $resolved->form_id)->first();
        $year = DB::table('transcript_year_aggregates')->where('student_id', $studentId)->where('academic_year_id', $academicYearId)->first();

        $status = 'unknown';
        $reason = null;

        if ($year?->is_withheld) {
            $status = 'not_ready';
            $reason = 'Results withheld';
        } elseif ($form && $form->level !== null && (int) $form->level >= 6) {
            $rule = StreamNativeRules::forContext($academicYearId, (int) $resolved->form_id, $resolved->category_id ? (int) $resolved->category_id : null);
            $minAvg = $rule->promotion_min_average !== null ? (float) $rule->promotion_min_average : 50.0;
            $avg = $year?->year_average !== null ? (float) $year->year_average : null;
            if ($avg !== null && $avg >= $minAvg) {
                $status = 'ready';
                $reason = null;
            } else {
                $status = 'not_ready';
                $reason = 'Insufficient average';
            }
        }

        DB::table('graduation_readiness')->updateOrInsert(
            ['student_id' => $studentId, 'academic_year_id' => $academicYearId],
            [
                'form_id' => $resolved->form_id,
                'stream_id' => $resolved->stream_id,
                'category_id' => $resolved->category_id,
                'status' => $status,
                'reason' => $reason,
                'computed_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public static function recomputeForStream(int $academicYearId, int $termId, int $streamId, ?int $updatedBy = null): array
    {
        $studentIds = StudentStreamResolver::studentIdsForStream($streamId, $academicYearId);
        $computed = 0;
        foreach ($studentIds as $studentId) {
            self::recomputeStudentTerm($studentId, $academicYearId, $termId, $updatedBy);
            $computed++;
        }

        self::recomputeGroupAggregates($academicYearId, $termId, 'stream', $streamId);
        self::recomputeRankings($academicYearId, $termId, 'stream', $streamId);

        return ['computed_students' => $computed];
    }

    public static function recomputeGroupAggregates(int $academicYearId, int $termId, string $scope, int $scopeId): void
    {
        $rankType = match ($scope) {
            'stream' => 'stream',
            'form' => 'form',
            'category' => 'category',
            default => abort(422, 'Invalid scope.'),
        };

        $base = DB::table('results_aggregates')
            ->where('academic_year_id', $academicYearId)
            ->where('term_id', $termId)
            ->whereNotNull('term_average')
            ->where('is_withheld', false);

        $base = match ($rankType) {
            'stream' => $base->where('stream_id', $scopeId),
            'form' => $base->where('form_id', $scopeId),
            'category' => $base->where('category_id', $scopeId),
        };

        $avg = $base->avg('term_average');
        $count = (clone $base)->count();

        DB::table('results_group_aggregates')->updateOrInsert(
            [
                'academic_year_id' => $academicYearId,
                'term_id' => $termId,
                'group_type' => $rankType,
                'group_id' => $scopeId,
                'subject_id' => null,
            ],
            [
                'average' => $avg ? round((float) $avg, 2) : null,
                'student_count' => (int) $count,
                'computed_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $subjects = DB::table('transcript_subject_history')
            ->where('academic_year_id', $academicYearId)
            ->where('term_id', $termId)
            ->whereNotNull('subject_average')
            ->where('is_withheld', false);

        $subjects = match ($rankType) {
            'stream' => $subjects->where('stream_id', $scopeId),
            'form' => $subjects->where('form_id', $scopeId),
            'category' => $subjects->where('category_id', $scopeId),
        };

        $subjectIds = $subjects->distinct()->pluck('subject_id')->map(fn ($id) => (int) $id)->toArray();

        foreach ($subjectIds as $subjectId) {
            $q = DB::table('transcript_subject_history')
                ->where('academic_year_id', $academicYearId)
                ->where('term_id', $termId)
                ->where('subject_id', $subjectId)
                ->whereNotNull('subject_average')
                ->where('is_withheld', false);

            $q = match ($rankType) {
                'stream' => $q->where('stream_id', $scopeId),
                'form' => $q->where('form_id', $scopeId),
                'category' => $q->where('category_id', $scopeId),
            };

            $avg = $q->avg('subject_average');
            $count = (clone $q)->count();

            $groupType = match ($rankType) {
                'stream' => 'subject_stream',
                'form' => 'subject_form',
                'category' => 'subject_category',
            };

            DB::table('results_group_aggregates')->updateOrInsert(
                [
                    'academic_year_id' => $academicYearId,
                    'term_id' => $termId,
                    'group_type' => $groupType,
                    'group_id' => $scopeId,
                    'subject_id' => $subjectId,
                ],
                [
                    'average' => $avg ? round((float) $avg, 2) : null,
                    'student_count' => (int) $count,
                    'computed_at' => now(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    public static function recomputeRankings(int $academicYearId, int $termId, string $scope, int $scopeId): void
    {
        $rankingType = match ($scope) {
            'stream' => 'stream',
            'form' => 'form',
            'category' => 'category',
            default => abort(422, 'Invalid scope.'),
        };

        $base = DB::table('results_aggregates')
            ->where('academic_year_id', $academicYearId)
            ->where('term_id', $termId)
            ->whereNotNull('term_average')
            ->where('is_withheld', false);

        $base = match ($rankingType) {
            'stream' => $base->where('stream_id', $scopeId),
            'form' => $base->where('form_id', $scopeId),
            'category' => $base->where('category_id', $scopeId),
        };

        $rows = $base->select('student_id', 'term_average as score')->orderByDesc('term_average')->orderBy('student_id')->get();

        self::persistRankingRows($academicYearId, $termId, $rankingType, $scopeId, null, $rows);

        $subjectQ = DB::table('transcript_subject_history')
            ->where('academic_year_id', $academicYearId)
            ->where('term_id', $termId)
            ->whereNotNull('subject_average')
            ->where('is_withheld', false);

        $subjectQ = match ($rankingType) {
            'stream' => $subjectQ->where('stream_id', $scopeId),
            'form' => $subjectQ->where('form_id', $scopeId),
            'category' => $subjectQ->where('category_id', $scopeId),
        };

        $subjectIds = $subjectQ->distinct()->pluck('subject_id')->map(fn ($id) => (int) $id)->toArray();
        foreach ($subjectIds as $subjectId) {
            $q = DB::table('transcript_subject_history')
                ->where('academic_year_id', $academicYearId)
                ->where('term_id', $termId)
                ->where('subject_id', $subjectId)
                ->whereNotNull('subject_average')
                ->where('is_withheld', false);

            $q = match ($rankingType) {
                'stream' => $q->where('stream_id', $scopeId),
                'form' => $q->where('form_id', $scopeId),
                'category' => $q->where('category_id', $scopeId),
            };

            $rows = $q->select('student_id', 'subject_average as score')->orderByDesc('subject_average')->orderBy('student_id')->get();

            $type = match ($rankingType) {
                'stream' => 'subject_stream',
                'form' => 'subject_form',
                'category' => 'subject_category',
            };

            self::persistRankingRows($academicYearId, $termId, $type, $scopeId, $subjectId, $rows);
        }
    }

    private static function persistRankingRows(int $academicYearId, int $termId, string $type, int $typeId, ?int $subjectId, $rows): void
    {
        DB::table('results_rankings')
            ->where('academic_year_id', $academicYearId)
            ->where('term_id', $termId)
            ->where('ranking_type', $type)
            ->where('ranking_id', $typeId)
            ->when($subjectId !== null, fn ($q) => $q->where('subject_id', $subjectId), fn ($q) => $q->whereNull('subject_id'))
            ->delete();

        $position = 0;
        $seen = 0;
        $lastScore = null;

        foreach ($rows as $row) {
            $seen++;
            $score = $row->score !== null ? (float) $row->score : null;
            if ($lastScore === null || $score !== (float) $lastScore) {
                $position = $seen;
                $lastScore = $score;
            }

            DB::table('results_rankings')->insert([
                'academic_year_id' => $academicYearId,
                'term_id' => $termId,
                'ranking_type' => $type,
                'ranking_id' => $typeId,
                'subject_id' => $subjectId,
                'student_id' => $row->student_id,
                'score' => $score,
                'rank' => $position,
                'is_withheld' => false,
                'computed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private static function subjectAverages(int $studentId, int $academicYearId, int $termId): array
    {
        $marks = DB::table('assessment_marks as am')
            ->join('assessments as a', 'am.assessment_id', '=', 'a.id')
            ->join('assessment_types as at', 'a.assessment_type_id', '=', 'at.id')
            ->where('am.student_id', $studentId)
            ->where('a.academic_year_id', $academicYearId)
            ->where('a.term_id', $termId)
            ->where('a.status', 'approved')
            ->where('am.status', 'entered')
            ->whereNotNull('am.percentage')
            ->select('a.subject_id', 'am.percentage', 'at.weight_percentage')
            ->get();

        $bySubject = [];
        foreach ($marks as $row) {
            $subjectId = (int) $row->subject_id;
            if (!isset($bySubject[$subjectId])) $bySubject[$subjectId] = [];
            $bySubject[$subjectId][] = ['percentage' => (float) $row->percentage, 'weight' => (float) ($row->weight_percentage ?? 0)];
        }

        $rows = [];
        foreach ($bySubject as $subjectId => $items) {
            $weight = array_sum(array_map(fn ($x) => (float) $x['weight'], $items));
            $avg = null;
            if ($weight > 0) {
                $sum = array_sum(array_map(fn ($x) => (float) $x['percentage'] * (float) $x['weight'], $items));
                $avg = round($sum / $weight, 2);
            } else {
                $avg = round(array_sum(array_map(fn ($x) => (float) $x['percentage'], $items)) / count($items), 2);
            }

            $grade = StreamNativeRules::gradeForPercentage($avg);
            $gpaPoints = StreamNativeRules::gpaPointsForGrade($grade);

            $rows[] = [
                'subject_id' => $subjectId,
                'subject_average' => $avg,
                'grade' => $grade,
                'gpa_points' => $gpaPoints,
            ];
        }

        $enrolledSubjectIds = DB::table('student_subjects')
            ->where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->where('term_id', $termId)
            ->where('enrollment_status', 'active')
            ->distinct()
            ->pluck('subject_id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        $existing = array_column($rows, 'subject_id');
        $missing = array_values(array_diff($enrolledSubjectIds, $existing));
        foreach ($missing as $subjectId) {
            $rows[] = ['subject_id' => (int) $subjectId, 'subject_average' => null, 'grade' => null, 'gpa_points' => null];
        }

        usort($rows, fn ($a, $b) => $a['subject_id'] <=> $b['subject_id']);
        return $rows;
    }
}

class StreamNativeRules
{
    public static function forContext(int $academicYearId, int $formId, ?int $categoryId): object
    {
        $candidates = DB::table('progression_rules')
            ->where('academic_year_id', $academicYearId)
            ->get();

        $best = null;
        $bestScore = -1;
        foreach ($candidates as $row) {
            $score = 0;
            if ($row->form_id !== null) {
                if ((int) $row->form_id !== (int) $formId) continue;
                $score += 2;
            }
            if ($row->category_id !== null) {
                if ($categoryId === null || (int) $row->category_id !== (int) $categoryId) continue;
                $score += 1;
            }
            if ($score > $bestScore) {
                $best = $row;
                $bestScore = $score;
            }
        }

        if ($best) return $best;

        return (object) [
            'minimum_pass_mark' => 50,
            'promotion_min_average' => null,
            'promotion_max_failed_subjects' => null,
            'repeat_below_average' => null,
            'supplementary_below_average' => null,
            'required_subject_ids_json' => null,
            'withhold_results_on_balance' => true,
            'withhold_balance_threshold' => 0,
            'gpa_scale_json' => null,
        ];
    }

    public static function gradeForPercentage(?float $percentage): ?string
    {
        if ($percentage === null) return null;
        $scale = DB::table('grading_scales')
            ->where('is_active', true)
            ->where('min_percentage', '<=', $percentage)
            ->where('max_percentage', '>=', $percentage)
            ->orderByDesc('min_percentage')
            ->first();
        return $scale?->grade;
    }

    public static function gpaPointsForGrade(?string $grade): ?float
    {
        if (!$grade) return null;
        $map = ['A' => 4.0, 'B' => 3.0, 'C' => 2.0, 'D' => 1.0, 'E' => 0.0, 'U' => 0.0];
        return $map[$grade] ?? null;
    }
}
