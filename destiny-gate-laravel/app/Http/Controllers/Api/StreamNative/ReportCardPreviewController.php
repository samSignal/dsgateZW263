<?php

namespace App\Http\Controllers\Api\StreamNative;

use App\Http\Controllers\Controller;
use App\Support\StudentStreamResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportCardPreviewController extends Controller
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

    public function studentTerm(int $studentId, Request $request)
    {
        $data = $request->validate([
            'academic_year_id' => 'required|integer',
            'term_id' => 'required|integer',
        ]);

        $student = DB::table('students')->where('id', $studentId)->first();
        abort_if(!$student, 404, 'Student not found.');
        $student = StudentStreamResolver::attachResolvedFields($student);

        $agg = DB::table('results_aggregates')
            ->where('student_id', $studentId)
            ->where('academic_year_id', $data['academic_year_id'])
            ->where('term_id', $data['term_id'])
            ->first();

        if ($agg) {
            $scales = DB::table('grading_scales')
                ->where('is_active', true)
                ->orderByDesc('min_percentage')
                ->get()
                ->all();
            $agg->overall_grade = $this->gradeForPercentageFromScale($agg->term_average !== null ? (float) $agg->term_average : null, $scales);
        }

        $subjects = DB::table('transcript_subject_history as tsh')
            ->join('subjects as sub', 'tsh.subject_id', '=', 'sub.id')
            ->where('tsh.student_id', $studentId)
            ->where('tsh.academic_year_id', $data['academic_year_id'])
            ->where('tsh.term_id', $data['term_id'])
            ->select('tsh.subject_id', 'sub.name as subject_name', 'sub.code as subject_code', 'tsh.subject_average', 'tsh.grade', 'tsh.gpa_points')
            ->orderBy('sub.name')
            ->get();

        $rank = null;
        $streamTotal = null;
        if ($agg?->stream_id) {
            $rank = DB::table('results_rankings')
                ->where('academic_year_id', $data['academic_year_id'])
                ->where('term_id', $data['term_id'])
                ->where('ranking_type', 'stream')
                ->where('ranking_id', $agg->stream_id)
                ->whereNull('subject_id')
                ->where('student_id', $studentId)
                ->value('rank');

            $streamTotal = DB::table('results_rankings')
                ->where('academic_year_id', $data['academic_year_id'])
                ->where('term_id', $data['term_id'])
                ->where('ranking_type', 'stream')
                ->where('ranking_id', $agg->stream_id)
                ->whereNull('subject_id')
                ->count();
        }

        $withheld = (bool) ($agg?->is_withheld ?? false);
        if ($withheld) {
            $subjects = collect([]);
            $rank = null;
            $streamTotal = null;
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
            'ranking' => [
                'stream_rank' => $rank,
                'stream_total' => $streamTotal,
            ],
            'is_withheld' => $withheld,
        ]);
    }
}
