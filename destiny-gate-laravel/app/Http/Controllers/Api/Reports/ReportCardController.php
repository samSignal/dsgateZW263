<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use App\Support\FinancialClearance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportCardController extends Controller
{
    private array $skills = [
        'Critical Thinking', 'Problem Solving', 'Communication Skills', 'Practical Skills',
        'Time Management', 'Leadership', 'Team Participation', 'Discipline',
    ];

    private function nextNumber(): string
    {
        $year = date('Y');
        $last = DB::table('report_cards')
            ->where('report_number', 'like', "RPT-{$year}-%")
            ->orderByDesc('id')
            ->value('report_number');
        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;
        return "RPT-{$year}-" . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    private function gradeFor(?float $percentage): ?object
    {
        if ($percentage === null) return null;
        return DB::table('grading_scales')
            ->where('is_active', true)
            ->where('min_percentage', '<=', $percentage)
            ->where('max_percentage', '>=', $percentage)
            ->orderByDesc('min_percentage')
            ->first();
    }

    private function pointsFor(?string $grade): ?int
    {
        if (!$grade) return null;
        return ['A' => 1, 'B' => 2, 'C' => 3, 'D' => 4, 'E' => 5, 'U' => 9][$grade] ?? null;
    }

    private function studentInfo(int $studentId): object
    {
        $student = DB::table('students as s')
            ->leftJoin('classes as c', 's.class_id', '=', 'c.id')
            ->leftJoin('forms as f', 'c.class_name', '=', 'f.name')
            ->leftJoin('streams as st', function ($join) {
                $join->on('st.form_id', '=', 'f.id')->on('st.name', '=', 'c.stream');
            })
            ->where('s.id', $studentId)
            ->select(
                's.*',
                'c.class_name',
                'c.stream as class_stream',
                'f.id as form_id',
                'f.name as form_name',
                'st.id as stream_id',
                'st.name as stream_name'
            )
            ->first();

        abort_if(!$student || !$student->form_id || !$student->stream_id, 422, 'Student is not linked to a valid form and stream.');
        return $student;
    }

    private function streamStudentIds(int $formId, int $streamId): array
    {
        $stream = DB::table('streams as st')->join('forms as f', 'st.form_id', '=', 'f.id')
            ->where('st.id', $streamId)
            ->select('f.name as form_name', 'st.name as stream_name')
            ->first();

        if (!$stream) return [];

        return DB::table('students as s')
            ->join('classes as c', 's.class_id', '=', 'c.id')
            ->where('c.class_name', $stream->form_name)
            ->where('c.stream', $stream->stream_name)
            ->where('s.status', 'active')
            ->pluck('s.id')
            ->map(fn ($id) => (int) $id)
            ->toArray();
    }

    private function subjectAverage(int $studentId, int $subjectId, int $academicYearId, int $termId): ?float
    {
        $rows = DB::table('assessment_marks as am')
            ->join('assessments as a', 'am.assessment_id', '=', 'a.id')
            ->join('assessment_types as at', 'a.assessment_type_id', '=', 'at.id')
            ->where('am.student_id', $studentId)
            ->where('a.subject_id', $subjectId)
            ->where('a.academic_year_id', $academicYearId)
            ->where('a.term_id', $termId)
            ->where('a.status', 'approved')
            ->where('am.status', 'entered')
            ->select('am.percentage', 'at.weight_percentage')
            ->get();

        if ($rows->isEmpty()) return null;
        $weight = (float) $rows->sum('weight_percentage');
        if ($weight <= 0) return round((float) $rows->avg('percentage'), 2);

        $weighted = $rows->sum(fn ($row) => ((float) $row->percentage) * ((float) $row->weight_percentage));
        return round($weighted / $weight, 2);
    }

    private function classAverage(int $subjectId, int $academicYearId, int $termId, int $formId, int $streamId): ?float
    {
        $studentIds = $this->streamStudentIds($formId, $streamId);
        $averages = [];
        foreach ($studentIds as $studentId) {
            $avg = $this->subjectAverage($studentId, $subjectId, $academicYearId, $termId);
            if ($avg !== null) $averages[] = $avg;
        }
        return $averages ? round(array_sum($averages) / count($averages), 2) : null;
    }

    private function attendanceSummary(int $studentId, int $academicYearId, int $termId): array
    {
        $records = DB::table('student_attendance_records as ar')
            ->join('attendance_sessions as ats', 'ar.attendance_session_id', '=', 'ats.id')
            ->where('ar.student_id', $studentId)
            ->where('ats.academic_year_id', $academicYearId)
            ->where('ats.term_id', $termId)
            ->select('ar.status')
            ->get();

        $total = $records->count();
        $present = $records->whereIn('status', ['present', 'late'])->count();
        return [
            'percentage' => $total ? round(($present / $total) * 100, 2) : null,
            'absent_count' => $records->where('status', 'absent')->count(),
            'late_count' => $records->where('status', 'late')->count(),
            'total_sessions' => $total,
        ];
    }

    private function behaviourSummary(int $studentId, int $academicYearId, int $termId): array
    {
        $incidents = DB::table('behaviour_incidents')->where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)->where('term_id', $termId)->count();
        $warnings = DB::table('discipline_actions')->where('student_id', $studentId)
            ->whereIn('action_type', ['verbal_warning', 'written_warning'])->count();
        $conduct = $incidents === 0 && $warnings === 0 ? 'Excellent' : ($incidents <= 1 ? 'Good' : ($incidents <= 3 ? 'Fair' : 'Needs Attention'));
        return ['incidents' => $incidents, 'warnings' => $warnings, 'conduct_grade' => $conduct];
    }

    private function feesBalance(int $studentId, int $academicYearId, int $termId): float
    {
        return (float) DB::table('student_bills')
            ->where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->where('term_id', $termId)
            ->where('status', '!=', 'cancelled')
            ->sum('balance');
    }

    private function trendFor(int $studentId, int $academicYearId, int $termId, ?float $current): string
    {
        $previous = DB::table('report_cards')
            ->where('student_id', $studentId)
            ->where(function ($q) use ($academicYearId, $termId) {
                $q->where('academic_year_id', '<', $academicYearId)
                    ->orWhere(function ($x) use ($academicYearId, $termId) {
                        $x->where('academic_year_id', $academicYearId)->where('term_id', '<', $termId);
                    });
            })
            ->orderByDesc('academic_year_id')
            ->orderByDesc('term_id')
            ->value('overall_average');

        if ($previous === null || $current === null) return 'maintained';
        if ((float) $current > (float) $previous) return 'improved';
        if ((float) $current < (float) $previous) return 'declined';
        return 'maintained';
    }

    private function promotionStatus(?float $average): string
    {
        if ($average === null) return 'pending';
        if ($average >= 50) return 'promoted';
        if ($average >= 40) return 'probation';
        return 'repeat';
    }

    private function calculateRankings(int $academicYearId, int $termId, int $streamId): void
    {
        $reports = DB::table('report_cards')
            ->where('academic_year_id', $academicYearId)
            ->where('term_id', $termId)
            ->where('stream_id', $streamId)
            ->orderByDesc('overall_average')
            ->orderBy('student_id')
            ->get();

        $position = 0;
        $seen = 0;
        $lastAverage = null;
        foreach ($reports as $report) {
            $seen++;
            if ($lastAverage === null || (float) $report->overall_average !== (float) $lastAverage) {
                $position = $seen;
                $lastAverage = $report->overall_average;
            }
            DB::table('report_cards')->where('id', $report->id)->update([
                'class_position' => $position,
                'stream_total_students' => $reports->count(),
                'updated_at' => now(),
            ]);
        }

        $subjects = DB::table('report_card_subjects as rcs')
            ->join('report_cards as rc', 'rcs.report_card_id', '=', 'rc.id')
            ->where('rc.academic_year_id', $academicYearId)
            ->where('rc.term_id', $termId)
            ->where('rc.stream_id', $streamId)
            ->distinct()
            ->pluck('rcs.subject_id');

        foreach ($subjects as $subjectId) {
            $rows = DB::table('report_card_subjects as rcs')
                ->join('report_cards as rc', 'rcs.report_card_id', '=', 'rc.id')
                ->where('rc.academic_year_id', $academicYearId)
                ->where('rc.term_id', $termId)
                ->where('rc.stream_id', $streamId)
                ->where('rcs.subject_id', $subjectId)
                ->orderByDesc('rcs.subject_average')
                ->orderBy('rc.student_id')
                ->select('rcs.id', 'rcs.subject_average')
                ->get();

            $position = 0;
            $seen = 0;
            $lastAverage = null;
            foreach ($rows as $row) {
                $seen++;
                if ($lastAverage === null || (float) $row->subject_average !== (float) $lastAverage) {
                    $position = $seen;
                    $lastAverage = $row->subject_average;
                }
                DB::table('report_card_subjects')->where('id', $row->id)->update(['subject_position' => $position, 'updated_at' => now()]);
            }
        }
    }

    public function generateStudentReport(Request $request)
    {
        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id' => 'required|exists:terms,id',
        ]);

        $student = $this->studentInfo((int) $data['student_id']);

        $subjects = DB::table('student_subjects as ss')
            ->join('subjects as sub', 'ss.subject_id', '=', 'sub.id')
            ->where('ss.student_id', $student->id)
            ->where('ss.academic_year_id', $data['academic_year_id'])
            ->where('ss.term_id', $data['term_id'])
            ->where('ss.enrollment_status', 'active')
            ->where(function ($q) {
                $q->where('sub.is_active', true)->orWhereNull('sub.is_active');
            })
            ->select('sub.id', 'sub.name', 'sub.code')
            ->orderBy('sub.name')
            ->get();

        DB::beginTransaction();
        try {
            $subjectRows = [];
            $points = [];
            foreach ($subjects as $subject) {
                $average = $this->subjectAverage((int) $student->id, (int) $subject->id, (int) $data['academic_year_id'], (int) $data['term_id']);
                $grade = $this->gradeFor($average);
                $point = $this->pointsFor($grade?->grade);
                if ($point !== null) $points[] = $point;
                $subjectRows[] = [
                    'subject_id' => $subject->id,
                    'subject_average' => $average,
                    'subject_grade' => $grade?->grade,
                    'subject_points' => $point,
                    'class_average' => $this->classAverage((int) $subject->id, (int) $data['academic_year_id'], (int) $data['term_id'], (int) $student->form_id, (int) $student->stream_id),
                    'teacher_comment' => $average === null ? 'No approved marks yet.' : ($average >= 70 ? 'Strong performance.' : ($average >= 50 ? 'Satisfactory progress.' : 'Needs focused support.')),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            $validAverages = array_values(array_filter(array_column($subjectRows, 'subject_average'), fn ($v) => $v !== null));
            $overall = $validAverages ? round(array_sum($validAverages) / count($validAverages), 2) : null;
            $overallGrade = $this->gradeFor($overall);
            $attendance = $this->attendanceSummary((int) $student->id, (int) $data['academic_year_id'], (int) $data['term_id']);
            $behaviour = $this->behaviourSummary((int) $student->id, (int) $data['academic_year_id'], (int) $data['term_id']);
            $clearance = FinancialClearance::summary((int) $student->id, (int) $data['academic_year_id'], (int) $data['term_id']);

            $existingId = DB::table('report_cards')
                ->where('student_id', $student->id)
                ->where('academic_year_id', $data['academic_year_id'])
                ->where('term_id', $data['term_id'])
                ->value('id');

            $payload = [
                'student_id' => $student->id,
                'academic_year_id' => $data['academic_year_id'],
                'term_id' => $data['term_id'],
                'form_id' => $student->form_id,
                'stream_id' => $student->stream_id,
                'overall_average' => $overall,
                'overall_grade' => $overallGrade?->grade,
                'overall_points' => $points ? array_sum($points) : null,
                'attendance_percentage' => $attendance['percentage'],
                'conduct_grade' => $behaviour['conduct_grade'],
                'performance_trend' => $this->trendFor((int) $student->id, (int) $data['academic_year_id'], (int) $data['term_id'], $overall),
                'teacher_comment' => $overall === null ? 'Awaiting approved assessment results.' : ($overall >= 60 ? 'A pleasing academic term.' : 'Consistent revision and support are recommended.'),
                'headmaster_comment' => null,
                'recommendation' => $overall !== null && $overall < 50 ? 'Create a subject support plan and monitor weekly.' : 'Maintain steady study habits.',
                'promotion_status' => $this->promotionStatus($overall),
                'fees_balance' => $clearance['outstanding_balance'],
                'financial_clearance_status' => $clearance['status'],
                'generated_by' => Auth::id(),
                'generated_at' => now(),
                'status' => 'generated',
                'updated_at' => now(),
            ];

            if ($existingId) {
                DB::table('report_cards')->where('id', $existingId)->update($payload);
                $reportId = $existingId;
                DB::table('report_card_subjects')->where('report_card_id', $reportId)->delete();
                DB::table('report_card_skill_assessments')->where('report_card_id', $reportId)->delete();
            } else {
                $reportId = DB::table('report_cards')->insertGetId([
                    'report_number' => $this->nextNumber(),
                    'created_at' => now(),
                    ...$payload,
                ]);
            }

            foreach ($subjectRows as $row) {
                DB::table('report_card_subjects')->insert(['report_card_id' => $reportId, ...$row]);
            }

            foreach ($this->skills as $skill) {
                DB::table('report_card_skill_assessments')->insert([
                    'report_card_id' => $reportId,
                    'skill_name' => $skill,
                    'rating' => $skill === 'Discipline' ? $behaviour['conduct_grade'] : ($overall !== null && $overall >= 60 ? 'Good' : 'Developing'),
                    'remarks' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('report_card_signatures')->updateOrInsert(
                ['report_card_id' => $reportId],
                ['updated_at' => now(), 'created_at' => now()]
            );

            $this->calculateRankings((int) $data['academic_year_id'], (int) $data['term_id'], (int) $student->stream_id);

            DB::commit();
            return response()->json(['message' => 'Report generated.', 'report' => $this->reportPayload($reportId)], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    public function generateStreamReports(Request $request)
    {
        $data = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id' => 'required|exists:terms,id',
            'form_id' => 'required|exists:forms,id',
            'stream_id' => 'required|exists:streams,id',
        ]);

        $ids = $this->streamStudentIds((int) $data['form_id'], (int) $data['stream_id']);
        $generated = [];
        foreach ($ids as $studentId) {
            $request->merge(['student_id' => $studentId]);
            $response = $this->generateStudentReport($request);
            if ($response->getStatusCode() < 300) $generated[] = $studentId;
        }

        return response()->json(['message' => count($generated) . ' report(s) generated.', 'generated' => count($generated)]);
    }

    public function show(int $id)
    {
        return response()->json($this->reportPayload($id));
    }

    public function approve(int $id)
    {
        abort_if(!DB::table('report_cards')->where('id', $id)->exists(), 404, 'Report not found.');
        DB::table('report_cards')->where('id', $id)->update(['status' => 'approved', 'updated_at' => now()]);
        DB::table('report_card_signatures')->updateOrInsert(
            ['report_card_id' => $id],
            ['headmaster_signed' => true, 'headmaster_signed_at' => now(), 'updated_at' => now(), 'created_at' => now()]
        );
        return response()->json(['message' => 'Report approved.']);
    }

    public function publish(int $id)
    {
        $report = DB::table('report_cards')->where('id', $id)->first();
        abort_if(!$report, 404, 'Report not found.');
        if ($report->status !== 'approved') return response()->json(['message' => 'Only approved reports can be published.'], 422);

        DB::table('report_cards')->where('id', $id)->update(['status' => 'published', 'published_at' => now(), 'updated_at' => now()]);
        return response()->json(['message' => 'Report published.']);
    }

    public function studentReport(int $studentId, Request $request)
    {
        $q = DB::table('report_cards')->where('student_id', $studentId)->orderByDesc('generated_at');
        if ($request->academic_year_id) $q->where('academic_year_id', $request->academic_year_id);
        if ($request->term_id) $q->where('term_id', $request->term_id);
        $id = $q->value('id');
        abort_if(!$id, 404, 'Report not found.');
        return response()->json($this->reportPayload((int) $id));
    }

    public function downloadPdf(int $id)
    {
        $report = $this->reportPayload($id);
        $html = view('reports.report-card', ['report' => $report])->render();

        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a4', 'portrait')->download($report['report_number'] . '.pdf');
        }

        return response($html, 200, [
            'Content-Type' => 'text/html',
            'Content-Disposition' => 'attachment; filename="' . $report['report_number'] . '.html"',
        ]);
    }

    public function batchDownloadPdf(Request $request)
    {
        $data = $request->validate([
            'academic_year_id' => 'required',
            'term_id' => 'required',
            'stream_id' => 'required',
        ]);

        $reports = DB::table('report_cards')
            ->where('academic_year_id', $data['academic_year_id'])
            ->where('term_id', $data['term_id'])
            ->where('stream_id', $data['stream_id'])
            ->orderBy('class_position')
            ->pluck('id')
            ->map(fn ($id) => $this->reportPayload((int) $id))
            ->toArray();

        $html = view('reports.report-card-batch', ['reports' => $reports])->render();
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a4', 'portrait')->download('stream-report-cards.pdf');
        }
        return response($html, 200, ['Content-Type' => 'text/html']);
    }

    private function reportPayload(int $id): array
    {
        $report = DB::table('report_cards as rc')
            ->join('students as s', 'rc.student_id', '=', 's.id')
            ->join('academic_years as ay', 'rc.academic_year_id', '=', 'ay.id')
            ->join('terms as t', 'rc.term_id', '=', 't.id')
            ->join('forms as f', 'rc.form_id', '=', 'f.id')
            ->join('streams as st', 'rc.stream_id', '=', 'st.id')
            ->leftJoin('users as u', 'rc.generated_by', '=', 'u.id')
            ->where('rc.id', $id)
            ->select('rc.*', DB::raw("CONCAT(s.first_name,' ',s.last_name) as student_name"), 's.student_number', 's.admission_number', 's.email', 'ay.name as academic_year_name', 't.name as term_name', 'f.name as form_name', 'st.name as stream_name', 'u.name as generated_by_name')
            ->first();
        abort_if(!$report, 404, 'Report not found.');

        $subjects = DB::table('report_card_subjects as rcs')
            ->join('subjects as sub', 'rcs.subject_id', '=', 'sub.id')
            ->where('rcs.report_card_id', $id)
            ->select('rcs.*', 'sub.name as subject_name', 'sub.code as subject_code')
            ->orderBy('sub.name')
            ->get();

        $attendance = $this->attendanceSummary((int) $report->student_id, (int) $report->academic_year_id, (int) $report->term_id);
        $behaviour = $this->behaviourSummary((int) $report->student_id, (int) $report->academic_year_id, (int) $report->term_id);
        $clearance = FinancialClearance::summary((int) $report->student_id, (int) $report->academic_year_id, (int) $report->term_id);

        return [
            ...(array) $report,
            'subjects' => $subjects,
            'skills' => DB::table('report_card_skill_assessments')->where('report_card_id', $id)->orderBy('skill_name')->get(),
            'signatures' => DB::table('report_card_signatures')->where('report_card_id', $id)->first(),
            'attendance_summary' => $attendance,
            'behaviour_summary' => $behaviour,
            'financial_clearance' => $clearance,
        ];
    }
}
