<?php

namespace App\Http\Controllers\Api\Assessment;

use App\Http\Controllers\Controller;
use App\Support\StudentStreamResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssessmentReportController extends Controller
{
    /* ── Student Progress ─────────────────────────────────────────────────── */

    public function studentProgress(int $studentId, Request $request)
    {
        $student = DB::table('students')
            ->where('students.id', $studentId)->first();
        abort_if(!$student, 404, 'Student not found.');
        $student = StudentStreamResolver::attachResolvedFields($student);

        $q = DB::table('assessment_marks as am')
            ->join('assessments as a', 'am.assessment_id', '=', 'a.id')
            ->join('subjects', 'a.subject_id', '=', 'subjects.id')
            ->join('assessment_types', 'a.assessment_type_id', '=', 'assessment_types.id')
            ->join('terms', 'a.term_id', '=', 'terms.id')
            ->join('academic_years', 'a.academic_year_id', '=', 'academic_years.id')
            ->select(
                'subjects.name as subject_name', 'subjects.code as subject_code',
                'assessment_types.name as type_name',
                'terms.name as term_name', 'academic_years.name as academic_year_name',
                'a.title', 'a.total_marks', 'a.assessment_date',
                'am.mark_obtained', 'am.percentage', 'am.grade', 'am.status', 'am.teacher_comment'
            )
            ->where('am.student_id', $studentId)
            ->whereIn('a.status', ['approved', 'submitted'])
            ->orderBy('a.assessment_date');

        if ($request->academic_year_id) $q->where('a.academic_year_id', $request->academic_year_id);
        if ($request->term_id)          $q->where('a.term_id', $request->term_id);

        $marks = $q->get();

        // Summary by subject
        $bySubject = $marks->groupBy('subject_name')->map(function ($subjectMarks) {
            $entered = $subjectMarks->where('status', 'entered');
            return [
                'count'   => $entered->count(),
                'average' => $entered->avg('percentage') ? round($entered->avg('percentage'), 1) : null,
                'highest' => $entered->max('percentage'),
                'lowest'  => $entered->min('percentage'),
            ];
        });

        return response()->json(['student' => $student, 'marks' => $marks, 'by_subject' => $bySubject]);
    }

    /* ── Class Assessment Performance ────────────────────────────────────── */

    public function classAssessmentPerformance(int $assessmentId)
    {
        $assessment = DB::table('assessments as a')
            ->join('subjects', 'a.subject_id', '=', 'subjects.id')
            ->join('streams', 'a.stream_id', '=', 'streams.id')
            ->join('assessment_types', 'a.assessment_type_id', '=', 'assessment_types.id')
            ->select('a.*', 'subjects.name as subject_name', 'streams.name as stream_name', 'assessment_types.name as type_name')
            ->where('a.id', $assessmentId)->first();
        abort_if(!$assessment, 404);

        $marks = DB::table('assessment_marks as am')
            ->join('students', 'am.student_id', '=', 'students.id')
            ->select('am.*', 'students.first_name', 'students.last_name', 'students.student_number')
            ->where('am.assessment_id', $assessmentId)
            ->orderBy('am.percentage', 'desc')
            ->get();
        foreach ($marks as $m) {
            $m->student_name = trim(($m->first_name ?? '') . ' ' . ($m->last_name ?? ''));
        }

        $entered   = $marks->where('status', 'entered');
        $passCount = $entered->where('percentage', '>=', 50)->count();

        return response()->json([
            'assessment' => $assessment,
            'marks'      => $marks,
            'summary'    => [
                'total_students' => $marks->count(),
                'entered'        => $entered->count(),
                'absent'         => $marks->where('status', 'absent')->count(),
                'excused'        => $marks->where('status', 'excused')->count(),
                'average'        => $entered->avg('percentage') ? round($entered->avg('percentage'), 1) : null,
                'highest'        => $entered->max('percentage'),
                'lowest'         => $entered->min('percentage'),
                'pass_count'     => $passCount,
                'pass_rate'      => $entered->count() > 0 ? round(($passCount / $entered->count()) * 100, 1) : 0,
            ],
        ]);
    }

    /* ── Subject Performance ──────────────────────────────────────────────── */

    public function subjectPerformance(Request $request)
    {
        $request->validate(['academic_year_id' => 'required', 'term_id' => 'required', 'stream_id' => 'required']);

        $subjects = DB::table('assessments as a')
            ->join('subjects', 'a.subject_id', '=', 'subjects.id')
            ->join('assessment_marks as am', 'a.id', '=', 'am.assessment_id')
            ->select(
                'subjects.id as subject_id',
                'subjects.name as subject_name',
                'subjects.code as subject_code',
                DB::raw('AVG(am.percentage) as average_percentage'),
                DB::raw('MAX(am.percentage) as highest'),
                DB::raw('MIN(am.percentage) as lowest'),
                DB::raw('COUNT(CASE WHEN am.status = "entered" THEN 1 END) as entered_count'),
                DB::raw('COUNT(CASE WHEN am.percentage >= 50 THEN 1 END) as pass_count'),
                DB::raw('ROUND((COUNT(CASE WHEN am.percentage >= 50 THEN 1 END) / NULLIF(COUNT(CASE WHEN am.status = "entered" THEN 1 END), 0)) * 100, 1) as pass_rate')
            )
            ->where('a.academic_year_id', $request->academic_year_id)
            ->where('a.term_id', $request->term_id)
            ->where('a.stream_id', $request->stream_id)
            ->whereIn('a.status', ['approved', 'submitted'])
            ->where('am.status', 'entered')
            ->groupBy('subjects.id', 'subjects.name', 'subjects.code')
            ->orderBy('subjects.name')
            ->get();

        return response()->json($subjects);
    }

    /* ── Assessment Summary ───────────────────────────────────────────────── */

    public function assessmentSummary(Request $request)
    {
        $q = DB::table('assessments');
        if ($request->academic_year_id) $q->where('academic_year_id', $request->academic_year_id);
        if ($request->term_id)          $q->where('term_id', $request->term_id);

        return response()->json([
            'total'     => (clone $q)->count(),
            'open'      => (clone $q)->where('status', 'open')->count(),
            'submitted' => (clone $q)->where('status', 'submitted')->count(),
            'approved'  => (clone $q)->where('status', 'approved')->count(),
            'cancelled' => (clone $q)->where('status', 'cancelled')->count(),
        ]);
    }

    /* ── Monthly Trend ────────────────────────────────────────────────────── */

    public function monthlyTrend(int $studentId, Request $request)
    {
        $marks = DB::table('assessment_marks as am')
            ->join('assessments as a', 'am.assessment_id', '=', 'a.id')
            ->join('subjects', 'a.subject_id', '=', 'subjects.id')
            ->select(
                DB::raw('YEAR(a.assessment_date) as year'),
                DB::raw('MONTH(a.assessment_date) as month'),
                DB::raw('AVG(am.percentage) as average'),
                'subjects.name as subject_name'
            )
            ->where('am.student_id', $studentId)
            ->where('am.status', 'entered')
            ->whereIn('a.status', ['approved', 'submitted'])
            ->groupBy('year', 'month', 'subjects.name')
            ->orderBy('year')->orderBy('month')
            ->get();

        return response()->json($marks);
    }

    public function weeklyTrend(Request $request)
    {
        $q = DB::table('assessment_marks as am')
            ->join('assessments as a', 'am.assessment_id', '=', 'a.id')
            ->join('subjects as sub', 'a.subject_id', '=', 'sub.id')
            ->select(
                DB::raw('YEAR(a.assessment_date) as year'),
                DB::raw('WEEK(a.assessment_date, 1) as week'),
                'sub.id as subject_id',
                'sub.name as subject_name',
                DB::raw('AVG(am.percentage) as average'),
                DB::raw('MAX(am.percentage) as highest'),
                DB::raw('MIN(am.percentage) as lowest'),
                DB::raw('COUNT(*) as entered_count')
            )
            ->where('am.status', 'entered')
            ->whereIn('a.status', ['approved', 'submitted'])
            ->groupBy(DB::raw('YEAR(a.assessment_date)'), DB::raw('WEEK(a.assessment_date, 1)'), 'sub.id', 'sub.name')
            ->orderBy('year')
            ->orderBy('week');

        if ($request->academic_year_id) $q->where('a.academic_year_id', $request->academic_year_id);
        if ($request->term_id) $q->where('a.term_id', $request->term_id);
        if ($request->stream_id) $q->where('a.stream_id', $request->stream_id);
        if ($request->subject_id) $q->where('a.subject_id', $request->subject_id);

        return response()->json($q->get());
    }
}
