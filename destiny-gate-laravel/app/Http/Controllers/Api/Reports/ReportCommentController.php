<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportCommentController extends Controller
{
    public function saveTeacherComment(Request $request, int $reportId)
    {
        abort_if(!DB::table('report_cards')->where('id', $reportId)->exists(), 404, 'Report not found.');
        $data = $request->validate([
            'subject_id' => 'nullable|exists:subjects,id',
            'teacher_comment' => 'required|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            if (!empty($data['subject_id'])) {
                DB::table('report_card_subjects')
                    ->where('report_card_id', $reportId)
                    ->where('subject_id', $data['subject_id'])
                    ->update(['teacher_comment' => $data['teacher_comment'], 'updated_at' => now()]);
            } else {
                DB::table('report_cards')->where('id', $reportId)->update(['teacher_comment' => $data['teacher_comment'], 'updated_at' => now()]);
            }
            DB::commit();
            return response()->json(['message' => 'Teacher comment saved.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    public function saveHeadmasterComment(Request $request, int $reportId)
    {
        abort_if(!DB::table('report_cards')->where('id', $reportId)->exists(), 404, 'Report not found.');
        $data = $request->validate(['headmaster_comment' => 'required|string|max:1000']);
        DB::table('report_cards')->where('id', $reportId)->update(['headmaster_comment' => $data['headmaster_comment'], 'updated_at' => now()]);
        return response()->json(['message' => 'Headmaster comment saved.']);
    }

    public function saveRecommendation(Request $request, int $reportId)
    {
        abort_if(!DB::table('report_cards')->where('id', $reportId)->exists(), 404, 'Report not found.');
        $data = $request->validate(['recommendation' => 'required|string|max:1000']);
        DB::table('report_cards')->where('id', $reportId)->update(['recommendation' => $data['recommendation'], 'updated_at' => now()]);
        return response()->json(['message' => 'Recommendation saved.']);
    }
}
