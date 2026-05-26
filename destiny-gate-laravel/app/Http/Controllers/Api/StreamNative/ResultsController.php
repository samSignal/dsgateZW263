<?php

namespace App\Http\Controllers\Api\StreamNative;

use App\Http\Controllers\Controller;
use App\Support\FinancialClearance;
use App\Support\StudentStreamResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ResultsController extends Controller
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

    public function myTerm(Request $request)
    {
        $studentId = (int) DB::table('students')->where('user_id', Auth::id())->value('id');
        abort_if(!$studentId, 404, 'Student profile not found.');
        $data = $request->validate([
            'academic_year_id' => 'nullable|integer',
            'term_id' => 'nullable|integer',
        ]);

        if (empty($data['academic_year_id']) || empty($data['term_id'])) {
            $current = FinancialClearance::currentPeriod();
            $request->merge([
                'academic_year_id' => $data['academic_year_id'] ?? $current?->academic_year_id,
                'term_id' => $data['term_id'] ?? $current?->term_id,
            ]);
        }

        return $this->studentTerm($studentId, $request);
    }

    public function myYear(Request $request)
    {
        $studentId = (int) DB::table('students')->where('user_id', Auth::id())->value('id');
        abort_if(!$studentId, 404, 'Student profile not found.');
        $data = $request->validate([
            'academic_year_id' => 'nullable|integer',
        ]);

        if (empty($data['academic_year_id'])) {
            $current = FinancialClearance::currentPeriod();
            $request->merge(['academic_year_id' => $current?->academic_year_id]);
        }

        return $this->studentYear($studentId, $request);
    }

    public function studentTerm(int $studentId, Request $request)
    {
        $data = $request->validate([
            'academic_year_id' => 'required|integer',
            'term_id' => 'required|integer',
        ]);

        $student = DB::table('students')->where('id', $studentId)->first();
        abort_if(!$student, 404, 'Student not found.');
        $student = StudentStreamResolver::attachResolvedFields($student);

        $agg = DB::table('results_aggregates as ra')
            ->leftJoin('academic_years as ay', 'ra.academic_year_id', '=', 'ay.id')
            ->leftJoin('terms as t', 'ra.term_id', '=', 't.id')
            ->leftJoin('forms as f', 'ra.form_id', '=', 'f.id')
            ->leftJoin('streams as st', 'ra.stream_id', '=', 'st.id')
            ->leftJoin('categories as cat', 'ra.category_id', '=', 'cat.id')
            ->where('ra.student_id', $studentId)
            ->where('ra.academic_year_id', $data['academic_year_id'])
            ->where('ra.term_id', $data['term_id'])
            ->select(
                'ra.*',
                'ay.name as academic_year_name',
                't.name as term_name',
                'f.name as form_name',
                'st.name as stream_name',
                'cat.name as category_name'
            )
            ->first();

        $subjects = DB::table('transcript_subject_history as tsh')
            ->join('subjects as sub', 'tsh.subject_id', '=', 'sub.id')
            ->where('tsh.student_id', $studentId)
            ->where('tsh.academic_year_id', $data['academic_year_id'])
            ->where('tsh.term_id', $data['term_id'])
            ->select(
                'tsh.subject_id',
                'sub.name as subject_name',
                'sub.code as subject_code',
                'tsh.subject_average',
                'tsh.grade',
                'tsh.gpa_points',
                'tsh.is_withheld',
                'tsh.computed_at'
            )
            ->orderBy('sub.name')
            ->get();

        $streamRanking = null;
        if ($agg?->stream_id) {
            $streamRanking = DB::table('results_rankings')
                ->where('academic_year_id', $data['academic_year_id'])
                ->where('term_id', $data['term_id'])
                ->where('ranking_type', 'stream')
                ->where('ranking_id', $agg->stream_id)
                ->whereNull('subject_id')
                ->where('student_id', $studentId)
                ->select('rank', 'score', 'computed_at')
                ->first();
        }

        $withheld = (bool) ($agg?->is_withheld ?? false);
        if ($withheld) {
            $subjects = collect([]);
            $streamRanking = null;
        }

        return response()->json([
            'student' => [
                'id' => $student->id,
                'student_number' => $student->student_number ?? null,
                'admission_number' => $student->admission_number ?? null,
                'first_name' => $student->first_name ?? null,
                'last_name' => $student->last_name ?? null,
                'stream_id' => $student->stream_id,
                'stream_name' => $student->stream_name,
                'form_id' => $student->form_id,
                'form_name' => $student->form_name,
                'category_id' => $student->category_id,
                'category_name' => $student->category_name,
                'class_id' => $student->class_id,
                'class_name' => $student->class_name,
            ],
            'aggregate' => $agg,
            'subjects' => $subjects,
            'rankings' => [
                'stream' => $streamRanking,
            ],
            'is_withheld' => $withheld,
        ]);
    }

    public function studentYear(int $studentId, Request $request)
    {
        $data = $request->validate([
            'academic_year_id' => 'required|integer',
        ]);

        $student = DB::table('students')->where('id', $studentId)->first();
        abort_if(!$student, 404, 'Student not found.');
        $student = StudentStreamResolver::attachResolvedFields($student);

        $year = DB::table('transcript_year_aggregates as tya')
            ->leftJoin('academic_years as ay', 'tya.academic_year_id', '=', 'ay.id')
            ->leftJoin('forms as f', 'tya.form_id', '=', 'f.id')
            ->leftJoin('streams as st', 'tya.stream_id', '=', 'st.id')
            ->leftJoin('categories as cat', 'tya.category_id', '=', 'cat.id')
            ->where('tya.student_id', $studentId)
            ->where('tya.academic_year_id', $data['academic_year_id'])
            ->select(
                'tya.*',
                'ay.name as academic_year_name',
                'f.name as form_name',
                'st.name as stream_name',
                'cat.name as category_name'
            )
            ->first();

        $terms = DB::table('results_aggregates as ra')
            ->leftJoin('terms as t', 'ra.term_id', '=', 't.id')
            ->where('ra.student_id', $studentId)
            ->where('ra.academic_year_id', $data['academic_year_id'])
            ->select(
                'ra.term_id',
                't.name as term_name',
                'ra.term_average',
                'ra.gpa',
                'ra.subjects_total',
                'ra.subjects_entered',
                'ra.subjects_passed',
                'ra.is_withheld',
                'ra.computed_at',
                DB::raw("(select rr.rank from results_rankings rr where rr.academic_year_id = ra.academic_year_id and rr.term_id = ra.term_id and rr.ranking_type = 'stream' and rr.ranking_id = ra.stream_id and rr.subject_id is null and rr.student_id = ra.student_id limit 1) as stream_rank"),
                DB::raw("(select count(*) from results_rankings rr2 where rr2.academic_year_id = ra.academic_year_id and rr2.term_id = ra.term_id and rr2.ranking_type = 'stream' and rr2.ranking_id = ra.stream_id and rr2.subject_id is null) as stream_total")
            )
            ->orderBy('ra.term_id')
            ->get();

        $scales = DB::table('grading_scales')
            ->where('is_active', true)
            ->orderByDesc('min_percentage')
            ->get()
            ->all();
        foreach ($terms as $row) {
            $row->overall_grade = $this->gradeForPercentageFromScale($row->term_average !== null ? (float) $row->term_average : null, $scales);
        }

        return response()->json([
            'student' => [
                'id' => $student->id,
                'student_number' => $student->student_number ?? null,
                'admission_number' => $student->admission_number ?? null,
                'first_name' => $student->first_name ?? null,
                'last_name' => $student->last_name ?? null,
                'stream_id' => $student->stream_id,
                'stream_name' => $student->stream_name,
                'form_id' => $student->form_id,
                'form_name' => $student->form_name,
                'category_id' => $student->category_id,
                'category_name' => $student->category_name,
                'class_id' => $student->class_id,
                'class_name' => $student->class_name,
            ],
            'year' => $year,
            'terms' => $terms,
        ]);
    }

    public function topPerformers(Request $request)
    {
        $data = $request->validate([
            'academic_year_id' => 'required|integer',
            'term_id' => 'required|integer',
            'stream_id' => 'nullable|integer',
            'form_id' => 'nullable|integer',
            'category_id' => 'nullable|integer',
            'limit' => 'nullable|integer|min:1|max:200',
        ]);

        $limit = (int) ($data['limit'] ?? 10);

        $q = DB::table('results_aggregates as ra')
            ->join('students as s', 'ra.student_id', '=', 's.id')
            ->leftJoin('forms as f', 'ra.form_id', '=', 'f.id')
            ->leftJoin('streams as st', 'ra.stream_id', '=', 'st.id')
            ->leftJoin('categories as cat', 'ra.category_id', '=', 'cat.id')
            ->where('ra.academic_year_id', $data['academic_year_id'])
            ->where('ra.term_id', $data['term_id'])
            ->whereNotNull('ra.term_average')
            ->where('ra.is_withheld', false)
            ->select(
                'ra.student_id',
                's.student_number',
                's.admission_number',
                's.first_name',
                's.last_name',
                'ra.term_average',
                'ra.gpa',
                'ra.subjects_passed',
                'ra.subjects_total',
                'ra.stream_id',
                'st.name as stream_name',
                'ra.form_id',
                'f.name as form_name',
                'ra.category_id',
                'cat.name as category_name'
            )
            ->orderByDesc('ra.term_average')
            ->orderBy('ra.student_id')
            ->limit($limit);

        if (!empty($data['stream_id'])) $q->where('ra.stream_id', $data['stream_id']);
        if (!empty($data['form_id'])) $q->where('ra.form_id', $data['form_id']);
        if (!empty($data['category_id'])) $q->where('ra.category_id', $data['category_id']);

        $rows = $q->get();
        $scales = DB::table('grading_scales')
            ->where('is_active', true)
            ->orderByDesc('min_percentage')
            ->get()
            ->all();
        foreach ($rows as $row) {
            StudentStreamResolver::attachResolvedFields($row);
            $row->student_name = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
            $row->overall_grade = $this->gradeForPercentageFromScale($row->term_average !== null ? (float) $row->term_average : null, $scales);
        }

        return response()->json(['data' => $rows]);
    }

    public function atRiskStudents(Request $request)
    {
        $data = $request->validate([
            'academic_year_id' => 'required|integer',
            'term_id' => 'required|integer',
            'stream_id' => 'nullable|integer',
            'form_id' => 'nullable|integer',
            'category_id' => 'nullable|integer',
            'max_average' => 'nullable|numeric',
            'limit' => 'nullable|integer|min:1|max:200',
        ]);

        $limit = (int) ($data['limit'] ?? 20);
        $maxAverage = $data['max_average'] !== null ? (float) $data['max_average'] : 50.0;

        $q = DB::table('results_aggregates as ra')
            ->join('students as s', 'ra.student_id', '=', 's.id')
            ->leftJoin('forms as f', 'ra.form_id', '=', 'f.id')
            ->leftJoin('streams as st', 'ra.stream_id', '=', 'st.id')
            ->leftJoin('categories as cat', 'ra.category_id', '=', 'cat.id')
            ->where('ra.academic_year_id', $data['academic_year_id'])
            ->where('ra.term_id', $data['term_id'])
            ->whereNotNull('ra.term_average')
            ->where('ra.is_withheld', false)
            ->where('ra.term_average', '<', $maxAverage)
            ->select(
                'ra.student_id',
                's.student_number',
                's.admission_number',
                's.first_name',
                's.last_name',
                'ra.term_average',
                'ra.gpa',
                'ra.subjects_passed',
                'ra.subjects_total',
                'ra.stream_id',
                'st.name as stream_name',
                'ra.form_id',
                'f.name as form_name',
                'ra.category_id',
                'cat.name as category_name'
            )
            ->orderBy('ra.term_average')
            ->orderBy('ra.student_id')
            ->limit($limit);

        if (!empty($data['stream_id'])) $q->where('ra.stream_id', $data['stream_id']);
        if (!empty($data['form_id'])) $q->where('ra.form_id', $data['form_id']);
        if (!empty($data['category_id'])) $q->where('ra.category_id', $data['category_id']);

        $rows = $q->get();
        foreach ($rows as $row) {
            StudentStreamResolver::attachResolvedFields($row);
            $row->student_name = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
        }

        return response()->json(['data' => $rows, 'max_average' => $maxAverage]);
    }
}
