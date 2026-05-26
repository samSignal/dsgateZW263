<?php

namespace App\Http\Controllers\Api\StreamNative;

use App\Http\Controllers\Controller;
use App\Support\StudentStreamResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RankingsController extends Controller
{
    private function gradeForPercentageFromScale(?float $percentage, array $scales): ?string
    {
        if ($percentage === null) return null;
        foreach ($scales as $row) {
            $min = (float) ($row->min_percentage ?? 0);
            $max = (float) ($row->max_percentage ?? 0);
            if ($percentage >= $min && $percentage <= $max) {
                return $row->grade ?? null;
            }
        }
        return null;
    }

    public function rankings(Request $request)
    {
        $data = $request->validate([
            'academic_year_id' => 'required|integer',
            'term_id' => 'required|integer',
            'type' => 'required|in:stream,form,category,subject_stream,subject_form,subject_category',
            'id' => 'nullable|integer',
            'subject_id' => 'nullable|integer',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:200',
        ]);

        $page = (int) ($data['page'] ?? 1);
        $perPage = (int) ($data['per_page'] ?? 50);
        $scopeId = $data['id'] ?? null;
        $subjectId = $data['subject_id'] ?? null;
        $isSubjectType = str_starts_with($data['type'], 'subject_');

        $q = DB::table('results_rankings as rr')
            ->join('students as s', 'rr.student_id', '=', 's.id')
            ->leftJoin('subjects as sub', 'rr.subject_id', '=', 'sub.id')
            ->where('rr.academic_year_id', $data['academic_year_id'])
            ->where('rr.term_id', $data['term_id'])
            ->where('rr.ranking_type', $data['type'])
            ->select(
                'rr.student_id',
                's.student_number',
                's.admission_number',
                's.first_name',
                's.last_name',
                'rr.score',
                'rr.rank',
                'rr.is_withheld',
                'rr.computed_at',
                'rr.ranking_id',
                'rr.subject_id',
                'sub.name as subject_name',
                'sub.code as subject_code'
            )
            ->orderBy('rr.rank')
            ->orderByDesc('rr.score')
            ->orderBy('rr.student_id');

        if (!empty($scopeId)) $q->where('rr.ranking_id', $scopeId);
        if ($isSubjectType) {
            if (!empty($subjectId)) {
                $q->where('rr.subject_id', $subjectId);
            } else {
                $q->whereNotNull('rr.subject_id');
            }
        } else {
            $q->whereNull('rr.subject_id');
        }

        if ($isSubjectType) {
            $q->leftJoin('transcript_subject_history as tsh', function ($join) {
                $join->on('tsh.student_id', '=', 'rr.student_id')
                    ->on('tsh.academic_year_id', '=', 'rr.academic_year_id')
                    ->on('tsh.term_id', '=', 'rr.term_id')
                    ->on('tsh.subject_id', '=', 'rr.subject_id');
            });

            $q->addSelect(
                'tsh.subject_average as subject_average',
                'tsh.grade as subject_grade',
                'tsh.gpa_points as subject_gpa_points'
            );
        } else {
            $q->leftJoin('results_aggregates as ra', function ($join) {
                $join->on('ra.student_id', '=', 'rr.student_id')
                    ->on('ra.academic_year_id', '=', 'rr.academic_year_id')
                    ->on('ra.term_id', '=', 'rr.term_id');
            });

            $q->addSelect(
                'ra.term_average',
                'ra.gpa',
                'ra.subjects_total',
                'ra.subjects_entered',
                'ra.subjects_passed'
            );
        }

        $total = (clone $q)->count();
        $rows = $q->offset(($page - 1) * $perPage)->limit($perPage)->get();

        $scales = [];
        if (!$isSubjectType) {
            $scales = DB::table('grading_scales')
                ->where('is_active', true)
                ->orderByDesc('min_percentage')
                ->get()
                ->all();
        }

        foreach ($rows as $row) {
            StudentStreamResolver::attachResolvedFields($row);
            $row->student_name = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
            if (!$isSubjectType) {
                $row->overall_grade = $this->gradeForPercentageFromScale($row->term_average !== null ? (float) $row->term_average : null, $scales);
            }
        }

        $scope = null;
        if (!empty($scopeId)) {
            $scope = match ($data['type']) {
                'stream', 'subject_stream' => DB::table('streams')->where('id', $scopeId)->select('id', 'name', 'form_id', 'category_id')->first(),
                'form', 'subject_form' => DB::table('forms')->where('id', $scopeId)->select('id', 'name', 'level')->first(),
                'category', 'subject_category' => DB::table('categories')->where('id', $scopeId)->select('id', 'name', 'code')->first(),
            };
        }

        return response()->json([
            'meta' => [
                'academic_year_id' => (int) $data['academic_year_id'],
                'term_id' => (int) $data['term_id'],
                'type' => $data['type'],
                'id' => $scopeId !== null ? (int) $scopeId : null,
                'subject_id' => $subjectId !== null ? (int) $subjectId : null,
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) ceil($total / $perPage),
                'scope' => $scope,
            ],
            'data' => $rows,
        ]);
    }
}
