<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use App\Support\FinancialClearance;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ParentReportController extends Controller
{
    private function childIds(): array
    {
        return DB::table('guardians')->where('user_id', Auth::id())->pluck('student_id')->map(fn ($id) => (int) $id)->toArray();
    }

    private function assertChild(int $studentId): void
    {
        abort_if(!in_array($studentId, $this->childIds(), true), 403, 'You cannot view this report.');
    }

    public function myChildrenReports()
    {
        $ids = $this->childIds();
        if (!$ids) {
            return response()
                ->json([])
                ->header('Deprecation', 'true')
                ->header('Link', '</api/parent/stream-native/children>; rel="successor-version"');
        }

        $reports = DB::table('report_cards as rc')
            ->join('students as s', 'rc.student_id', '=', 's.id')
            ->join('academic_years as ay', 'rc.academic_year_id', '=', 'ay.id')
            ->join('terms as t', 'rc.term_id', '=', 't.id')
            ->whereIn('rc.student_id', $ids)
            ->whereIn('rc.status', ['published', 'approved'])
            ->select('rc.id', 'rc.report_number', 'rc.student_id', 'rc.academic_year_id', 'rc.term_id', DB::raw("CONCAT(s.first_name,' ',s.last_name) as student_name"), 's.student_number', 'ay.name as academic_year_name', 't.name as term_name', 'rc.overall_average', 'rc.overall_grade', 'rc.class_position', 'rc.stream_total_students', 'rc.status', 'rc.financial_clearance_status', 'rc.fees_balance')
            ->orderByDesc('rc.generated_at')
            ->get()
            ->map(function ($report) {
                $clearance = FinancialClearance::summary((int) $report->student_id, (int) $report->academic_year_id, (int) $report->term_id);
                $report->financial_clearance_status = $clearance['status'];
                $report->financial_clearance = $clearance;
                if ($clearance['status'] !== 'cleared') {
                    $report->overall_average = null;
                    $report->overall_grade = null;
                    $report->class_position = null;
                }
                return $report;
            });

        return response()
            ->json($reports)
            ->header('Deprecation', 'true')
            ->header('Link', '</api/parent/stream-native/child/{studentId}/year>; rel="successor-version"');
    }

    public function childReport(int $studentId)
    {
        $this->assertChild($studentId);
        $id = DB::table('report_cards')->where('student_id', $studentId)->whereIn('status', ['published', 'approved'])->orderByDesc('generated_at')->value('id');
        abort_if(!$id, 404, 'Report not found.');
        $report = DB::table('report_cards')->where('id', $id)->first();
        $clearance = FinancialClearance::summary($studentId, (int) $report->academic_year_id, (int) $report->term_id);
        if ($clearance['status'] !== 'cleared') {
            return response()->json(['message' => $clearance['parent_message'], 'financial_clearance' => $clearance], 403);
        }
        return app(ReportCardController::class)->show((int) $id);
    }

    public function downloadChildReport(int $reportId)
    {
        $studentId = (int) DB::table('report_cards')->where('id', $reportId)->value('student_id');
        $this->assertChild($studentId);
        $report = DB::table('report_cards')->where('id', $reportId)->first();
        abort_if(!$report, 404, 'Report not found.');
        $clearance = FinancialClearance::summary($studentId, (int) $report->academic_year_id, (int) $report->term_id);
        if ($clearance['status'] !== 'cleared') {
            return response()->json(['message' => $clearance['parent_message'], 'financial_clearance' => $clearance], 403);
        }
        return app(ReportCardController::class)->downloadPdf($reportId);
    }
}
