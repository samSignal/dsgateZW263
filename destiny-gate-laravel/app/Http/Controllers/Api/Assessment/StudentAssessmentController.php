<?php

namespace App\Http\Controllers\Api\Assessment;

use App\Http\Controllers\Controller;
use App\Support\FinancialClearance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class StudentAssessmentController extends Controller
{
    private function getStudentId(): int
    {
        $student = DB::table('students')->where('user_id', Auth::id())->first();
        abort_if(!$student, 404, 'No student profile linked to your account.');
        return $student->id;
    }

    public function myMarks(Request $request)
    {
        $studentId = $this->getStudentId();
        $clearance = FinancialClearance::summary($studentId, $request->academic_year_id ? (int) $request->academic_year_id : null, $request->term_id ? (int) $request->term_id : null);
        if ($clearance['status'] !== 'cleared') {
            return response()->json(['message' => $clearance['message'], 'financial_clearance' => $clearance], 403);
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
        if ($request->subject_id)       $q->where('a.subject_id', $request->subject_id);

        return response()->json($q->get());
    }

    public function myProgress()
    {
        $studentId = $this->getStudentId();
        $clearance = FinancialClearance::summary($studentId);
        if ($clearance['status'] !== 'cleared') {
            return response()->json(['message' => $clearance['message'], 'financial_clearance' => $clearance], 403);
        }

        $bySubject = DB::table('assessment_marks as am')
            ->join('assessments as a', 'am.assessment_id', '=', 'a.id')
            ->join('subjects', 'a.subject_id', '=', 'subjects.id')
            ->select(
                'subjects.name as subject_name', 'subjects.code as subject_code',
                DB::raw('AVG(am.percentage) as average'),
                DB::raw('MAX(am.percentage) as highest'),
                DB::raw('MIN(am.percentage) as lowest'),
                DB::raw('COUNT(*) as count')
            )
            ->where('am.student_id', $studentId)
            ->where('am.status', 'entered')
            ->whereIn('a.status', ['approved', 'submitted'])
            ->groupBy('subjects.name', 'subjects.code')
            ->orderBy('subjects.name')
            ->get();

        return response()->json($bySubject);
    }
}
