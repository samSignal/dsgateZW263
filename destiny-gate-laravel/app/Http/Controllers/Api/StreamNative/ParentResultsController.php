<?php

namespace App\Http\Controllers\Api\StreamNative;

use App\Http\Controllers\Controller;
use App\Support\FinancialClearance;
use App\Support\StudentStreamResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ParentResultsController extends Controller
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

    private function getLinkedStudentIds(): array
    {
        return DB::table('guardians')
            ->where('user_id', Auth::id())
            ->pluck('student_id')
            ->toArray();
    }

    private function authorizeChild(int $studentId): void
    {
        $linked = $this->getLinkedStudentIds();
        abort_if(!in_array($studentId, $linked), 403, 'You are not authorized to view this student\'s records.');
    }

    public function children()
    {
        $ids = $this->getLinkedStudentIds();
        if (empty($ids)) {
            return response()->json(['children' => []]);
        }

        $rows = DB::table('students')
            ->whereIn('id', $ids)
            ->select('id', 'first_name', 'last_name', 'student_number', 'admission_number', 'class_id')
            ->orderBy('first_name')
            ->get();

        foreach ($rows as $row) {
            $row->name = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
            StudentStreamResolver::attachResolvedFields($row);
        }

        return response()->json(['children' => $rows]);
    }

    public function childTerm(int $studentId, Request $request)
    {
        $this->authorizeChild($studentId);

        $data = $request->validate([
            'academic_year_id' => 'nullable|integer',
            'term_id' => 'nullable|integer',
        ]);

        if (empty($data['academic_year_id']) || empty($data['term_id'])) {
            $current = FinancialClearance::currentPeriod();
            $data['academic_year_id'] ??= $current?->academic_year_id;
            $data['term_id'] ??= $current?->term_id;
        }

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

        $withheld = (bool) ($agg?->is_withheld ?? false);

        $subjects = collect([]);
        $rank = null;
        if (!$withheld) {
            $subjects = DB::table('transcript_subject_history as tsh')
                ->join('subjects as sub', 'tsh.subject_id', '=', 'sub.id')
                ->where('tsh.student_id', $studentId)
                ->where('tsh.academic_year_id', $data['academic_year_id'])
                ->where('tsh.term_id', $data['term_id'])
                ->select('tsh.subject_id', 'sub.name as subject_name', 'sub.code as subject_code', 'tsh.subject_average', 'tsh.grade', 'tsh.gpa_points')
                ->orderBy('sub.name')
                ->get();

            if ($agg?->stream_id) {
                $rank = DB::table('results_rankings')
                    ->where('academic_year_id', $data['academic_year_id'])
                    ->where('term_id', $data['term_id'])
                    ->where('ranking_type', 'stream')
                    ->where('ranking_id', $agg->stream_id)
                    ->whereNull('subject_id')
                    ->where('student_id', $studentId)
                    ->select('rank', 'score', 'computed_at')
                    ->first();
            }
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
                'stream' => $rank,
            ],
            'is_withheld' => $withheld,
        ]);
    }

    public function childTranscript(int $studentId)
    {
        $this->authorizeChild($studentId);

        $student = DB::table('students')->where('id', $studentId)->first();
        abort_if(!$student, 404, 'Student not found.');
        $student = StudentStreamResolver::attachResolvedFields($student);

        $enrollments = DB::table('student_enrollments as se')
            ->leftJoin('academic_years as ay', 'se.academic_year_id', '=', 'ay.id')
            ->leftJoin('terms as t', 'se.term_id', '=', 't.id')
            ->leftJoin('forms as f', 'se.form_id', '=', 'f.id')
            ->leftJoin('streams as st', 'se.stream_id', '=', 'st.id')
            ->leftJoin('categories as cat', 'se.category_id', '=', 'cat.id')
            ->where('se.student_id', $studentId)
            ->select('se.*', 'ay.name as academic_year_name', 't.name as term_name', 'f.name as form_name', 'st.name as stream_name', 'cat.name as category_name')
            ->orderBy('se.academic_year_id')
            ->orderBy('se.term_id')
            ->get();

        $years = DB::table('transcript_year_aggregates as tya')
            ->leftJoin('academic_years as ay', 'tya.academic_year_id', '=', 'ay.id')
            ->where('tya.student_id', $studentId)
            ->select('tya.*', 'ay.name as academic_year_name')
            ->orderBy('tya.academic_year_id')
            ->get();

        $subjects = DB::table('transcript_subject_history as tsh')
            ->join('subjects as sub', 'tsh.subject_id', '=', 'sub.id')
            ->leftJoin('academic_years as ay', 'tsh.academic_year_id', '=', 'ay.id')
            ->leftJoin('terms as t', 'tsh.term_id', '=', 't.id')
            ->where('tsh.student_id', $studentId)
            ->where('tsh.is_withheld', false)
            ->select('tsh.*', 'sub.name as subject_name', 'sub.code as subject_code', 'ay.name as academic_year_name', 't.name as term_name')
            ->orderBy('tsh.academic_year_id')
            ->orderBy('tsh.term_id')
            ->orderBy('sub.name')
            ->get();

        $graduation = DB::table('graduation_readiness as gr')
            ->leftJoin('academic_years as ay', 'gr.academic_year_id', '=', 'ay.id')
            ->where('gr.student_id', $studentId)
            ->select('gr.*', 'ay.name as academic_year_name')
            ->orderBy('gr.academic_year_id')
            ->get();

        $cumulativeGpa = null;
        $gpaRows = $years->pluck('gpa')->filter(fn ($v) => $v !== null)->values();
        if ($gpaRows->count()) {
            $cumulativeGpa = round($gpaRows->avg(), 2);
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
            'cumulative_gpa' => $cumulativeGpa,
            'enrollments' => $enrollments,
            'year_aggregates' => $years,
            'subject_history' => $subjects,
            'graduation_readiness' => $graduation,
        ]);
    }

    public function childYear(int $studentId, Request $request)
    {
        $this->authorizeChild($studentId);

        $data = $request->validate([
            'academic_year_id' => 'nullable|integer',
        ]);

        if (empty($data['academic_year_id'])) {
            $current = FinancialClearance::currentPeriod();
            $data['academic_year_id'] = $current?->academic_year_id;
        }

        $student = DB::table('students')->where('id', $studentId)->first();
        abort_if(!$student, 404, 'Student not found.');
        $student = StudentStreamResolver::attachResolvedFields($student);

        $year = DB::table('transcript_year_aggregates as tya')
            ->leftJoin('academic_years as ay', 'tya.academic_year_id', '=', 'ay.id')
            ->where('tya.student_id', $studentId)
            ->where('tya.academic_year_id', $data['academic_year_id'])
            ->select('tya.*', 'ay.name as academic_year_name')
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
}
