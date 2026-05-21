<?php

namespace App\Http\Controllers\Api\Assessment;

use App\Http\Controllers\Controller;
use App\Support\FinancialClearance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ParentAssessmentController extends Controller
{
    private function linkedStudentIds(): array
    {
        return DB::table('guardians')->where('user_id', Auth::id())->pluck('student_id')->toArray();
    }

    private function authorizeChild(int $studentId): void
    {
        abort_if(!in_array($studentId, $this->linkedStudentIds()), 403, 'Not authorized to view this student.');
    }

    public function myChildrenAssessmentSummary()
    {
        $ids = $this->linkedStudentIds();
        if (empty($ids)) return response()->json([]);

        $children = DB::table('students')
            ->leftJoin('classes', 'students.class_id', '=', 'classes.id')
            ->whereIn('students.id', $ids)
            ->select('students.id', DB::raw("CONCAT(students.first_name,' ',students.last_name) as name"), 'students.student_number', 'classes.class_name')
            ->get()
            ->map(function ($s) {
                $clearance = FinancialClearance::summary((int) $s->id);
                $s->financial_clearance = $clearance;
                $s->financial_clearance_status = $clearance['status'];
                if ($clearance['status'] !== 'cleared') {
                    $s->assessments_count = 0;
                    $s->average = null;
                    return $s;
                }
                $s->assessments_count = DB::table('assessment_marks as am')
                    ->join('assessments', 'am.assessment_id', '=', 'assessments.id')
                    ->where('am.student_id', $s->id)
                    ->whereIn('assessments.status', ['approved', 'submitted'])
                    ->count();
                $s->average = DB::table('assessment_marks as am')
                    ->join('assessments', 'am.assessment_id', '=', 'assessments.id')
                    ->where('am.student_id', $s->id)
                    ->where('am.status', 'entered')
                    ->whereIn('assessments.status', ['approved', 'submitted'])
                    ->avg('am.percentage');
                $s->average = $s->average ? round($s->average, 1) : null;
                return $s;
            });

        return response()->json($children);
    }

    public function childMarks(int $studentId, Request $request)
    {
        $this->authorizeChild($studentId);
        $clearance = FinancialClearance::summary($studentId, $request->academic_year_id ? (int) $request->academic_year_id : null, $request->term_id ? (int) $request->term_id : null);
        if ($clearance['status'] !== 'cleared') {
            return response()->json(['message' => $clearance['parent_message'], 'financial_clearance' => $clearance], 403);
        }

        $q = DB::table('assessment_marks as am')
            ->join('assessments as a', 'am.assessment_id', '=', 'a.id')
            ->join('subjects', 'a.subject_id', '=', 'subjects.id')
            ->join('assessment_types', 'a.assessment_type_id', '=', 'assessment_types.id')
            ->join('terms', 'a.term_id', '=', 'terms.id')
            ->join('academic_years', 'a.academic_year_id', '=', 'academic_years.id')
            ->select(
                'am.*',
                'a.title', 'a.total_marks', 'a.assessment_date', 'a.assessment_number',
                'subjects.name as subject_name', 'subjects.code as subject_code',
                'assessment_types.name as type_name',
                'terms.name as term_name', 'academic_years.name as academic_year_name'
            )
            ->where('am.student_id', $studentId)
            ->whereIn('a.status', ['approved', 'submitted'])
            ->orderByDesc('a.assessment_date');

        if ($request->academic_year_id) $q->where('a.academic_year_id', $request->academic_year_id);
        if ($request->term_id)          $q->where('a.term_id', $request->term_id);

        return response()->json($q->get());
    }

    public function childProgress(int $studentId)
    {
        $this->authorizeChild($studentId);
        $clearance = FinancialClearance::summary($studentId);
        if ($clearance['status'] !== 'cleared') {
            return response()->json(['message' => $clearance['parent_message'], 'financial_clearance' => $clearance], 403);
        }

        $bySubject = DB::table('assessment_marks as am')
            ->join('assessments as a', 'am.assessment_id', '=', 'a.id')
            ->join('subjects', 'a.subject_id', '=', 'subjects.id')
            ->select(
                'subjects.name as subject_name',
                DB::raw('AVG(am.percentage) as average'),
                DB::raw('MAX(am.percentage) as highest'),
                DB::raw('MIN(am.percentage) as lowest'),
                DB::raw('COUNT(*) as count')
            )
            ->where('am.student_id', $studentId)
            ->where('am.status', 'entered')
            ->whereIn('a.status', ['approved', 'submitted'])
            ->groupBy('subjects.name')
            ->orderBy('subjects.name')
            ->get();

        return response()->json($bySubject);
    }
}
