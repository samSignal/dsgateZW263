<?php

namespace App\Http\Controllers\Api\Assessment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AssessmentMarkController extends Controller
{
    private function getGrade(float $percentage): string
    {
        $scale = DB::table('grading_scales')
            ->where('is_active', true)
            ->where('min_percentage', '<=', $percentage)
            ->where('max_percentage', '>=', $percentage)
            ->orderByDesc('min_percentage')
            ->first();

        return $scale ? $scale->grade : 'U';
    }

    private function canEditAssessment(object $assessment): bool
    {
        $user = Auth::user();
        if (in_array($user->role, ['admin', 'headmaster'])) return true;
        if ($user->role !== 'teacher') return false;

        $staffId = DB::table('staff')->where('user_id', $user->id)->value('id')
            ?: DB::table('staff_members')->where('user_id', $user->id)->value('id');

        if (!$staffId) return false;

        return DB::table('teacher_subject_allocations')
            ->where('teacher_id', $staffId)
            ->where('subject_id', $assessment->subject_id)
            ->where('stream_id', $assessment->stream_id)
            ->where('academic_year_id', $assessment->academic_year_id)
            ->where('term_id', $assessment->term_id)
            ->exists();
    }

    public function bulkSaveMarks(Request $request, int $assessmentId)
    {
        $assessment = DB::table('assessments')->find($assessmentId);
        abort_if(!$assessment, 404, 'Assessment not found.');

        if (!$this->canEditAssessment($assessment)) {
            return response()->json(['message' => 'You cannot enter marks for this assessment.'], 403);
        }

        if (in_array($assessment->status, ['submitted', 'approved', 'cancelled'])) {
            return response()->json(['message' => 'Cannot edit marks for a ' . $assessment->status . ' assessment.'], 422);
        }

        $data = $request->validate([
            'marks' => 'required|array',
            'marks.*.student_id' => 'required|integer',
            'marks.*.status' => 'required|in:pending,entered,absent,excused',
            'marks.*.mark_obtained' => 'nullable|numeric|min:0',
            'marks.*.teacher_comment' => 'nullable|string|max:500',
        ]);

        $allowedStudents = DB::table('assessment_marks')
            ->where('assessment_id', $assessmentId)
            ->pluck('student_id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        DB::beginTransaction();
        try {
            foreach ($data['marks'] as $mark) {
                $studentId = (int) $mark['student_id'];
                if (!in_array($studentId, $allowedStudents, true)) {
                    DB::rollBack();
                    return response()->json(['message' => 'One or more students are not enrolled for this assessment.'], 422);
                }

                $status = $mark['status'];
                $markObtained = null;
                $percentage = null;
                $grade = null;

                if ($status === 'entered') {
                    if ($mark['mark_obtained'] === null || $mark['mark_obtained'] === '') {
                        DB::rollBack();
                        return response()->json(['message' => 'Entered marks require a mark value.'], 422);
                    }

                    $markObtained = (float) $mark['mark_obtained'];
                    if ($markObtained > (float) $assessment->total_marks) {
                        DB::rollBack();
                        return response()->json(['message' => "Mark {$markObtained} exceeds total marks {$assessment->total_marks}."], 422);
                    }

                    $percentage = round(($markObtained / (float) $assessment->total_marks) * 100, 2);
                    $grade = $this->getGrade($percentage);
                }

                DB::table('assessment_marks')
                    ->where('assessment_id', $assessmentId)
                    ->where('student_id', $studentId)
                    ->update([
                        'mark_obtained' => $markObtained,
                        'percentage' => $percentage,
                        'grade' => $grade,
                        'teacher_comment' => $mark['teacher_comment'] ?? null,
                        'status' => $status,
                        'entered_by' => Auth::id(),
                        'entered_at' => now(),
                        'updated_at' => now(),
                    ]);
            }

            DB::commit();
            return response()->json(['message' => 'Marks saved successfully.', 'count' => count($data['marks'])]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    public function submitMarks(int $assessmentId)
    {
        $assessment = DB::table('assessments')->find($assessmentId);
        abort_if(!$assessment, 404, 'Assessment not found.');

        if (!$this->canEditAssessment($assessment)) {
            return response()->json(['message' => 'You cannot submit marks for this assessment.'], 403);
        }

        if (!in_array($assessment->status, ['open', 'draft'])) {
            return response()->json(['message' => 'Assessment cannot be submitted in its current state.'], 422);
        }

        DB::table('assessments')->where('id', $assessmentId)->update(['status' => 'submitted', 'updated_at' => now()]);
        return response()->json(['message' => 'Marks submitted for approval.']);
    }

    public function studentMarks(int $studentId, Request $request)
    {
        $q = DB::table('assessment_marks as am')
            ->join('assessments as a', 'am.assessment_id', '=', 'a.id')
            ->join('subjects as sub', 'a.subject_id', '=', 'sub.id')
            ->join('assessment_types as at', 'a.assessment_type_id', '=', 'at.id')
            ->join('terms as t', 'a.term_id', '=', 't.id')
            ->join('academic_years as ay', 'a.academic_year_id', '=', 'ay.id')
            ->select(
                'am.*',
                'a.title',
                'a.total_marks',
                'a.assessment_date',
                'a.assessment_number',
                'a.status as assessment_status',
                'sub.name as subject_name',
                'sub.code as subject_code',
                'at.name as type_name',
                't.name as term_name',
                'ay.name as academic_year_name'
            )
            ->where('am.student_id', $studentId)
            ->whereIn('a.status', ['approved', 'submitted'])
            ->orderByDesc('a.assessment_date');

        if ($request->academic_year_id) $q->where('a.academic_year_id', $request->academic_year_id);
        if ($request->term_id) $q->where('a.term_id', $request->term_id);
        if ($request->subject_id) $q->where('a.subject_id', $request->subject_id);

        return response()->json($q->get());
    }
}
