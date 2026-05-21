<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use App\Support\FinancialClearance;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StudentReportController extends Controller
{
    private function studentId(): int
    {
        $id = DB::table('students')->where('user_id', Auth::id())->value('id');
        abort_if(!$id, 404, 'Student profile not found.');
        return (int) $id;
    }

    public function myReports()
    {
        $studentId = $this->studentId();
        $reports = DB::table('report_cards as rc')
            ->join('academic_years as ay', 'rc.academic_year_id', '=', 'ay.id')
            ->join('terms as t', 'rc.term_id', '=', 't.id')
            ->where('rc.student_id', $studentId)
            ->whereIn('rc.status', ['published', 'approved'])
            ->select('rc.id', 'rc.report_number', 'rc.academic_year_id', 'rc.term_id', 'ay.name as academic_year_name', 't.name as term_name', 'rc.overall_average', 'rc.overall_grade', 'rc.class_position', 'rc.stream_total_students', 'rc.performance_trend', 'rc.status', 'rc.financial_clearance_status', 'rc.fees_balance')
            ->orderByDesc('rc.generated_at')
            ->get()
            ->map(function ($report) use ($studentId) {
                $clearance = FinancialClearance::summary($studentId, (int) $report->academic_year_id, (int) $report->term_id);
                $report->financial_clearance_status = $clearance['status'];
                $report->financial_clearance = $clearance;
                if ($clearance['status'] !== 'cleared') {
                    $report->overall_average = null;
                    $report->overall_grade = null;
                    $report->class_position = null;
                    $report->performance_trend = null;
                }
                return $report;
            });

        return response()->json($reports);
    }

    public function downloadMyReport(int $reportId)
    {
        $studentId = $this->studentId();
        $report = DB::table('report_cards')->where('id', $reportId)->where('student_id', $studentId)->whereIn('status', ['published', 'approved'])->first();
        abort_if(!$report, 403, 'You cannot download this report.');
        $clearance = FinancialClearance::summary($studentId, (int) $report->academic_year_id, (int) $report->term_id);
        if ($clearance['status'] !== 'cleared') {
            return response()->json(['message' => $clearance['message'], 'financial_clearance' => $clearance], 403);
        }
        return app(ReportCardController::class)->downloadPdf($reportId);
    }
}
