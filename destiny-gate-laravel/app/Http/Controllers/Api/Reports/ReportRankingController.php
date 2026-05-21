<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportRankingController extends Controller
{
    public function streamRankings(Request $request)
    {
        $data = $request->validate([
            'academic_year_id' => 'required',
            'term_id' => 'required',
            'stream_id' => 'required',
        ]);

        return response()->json(DB::table('report_cards as rc')
            ->join('students as s', 'rc.student_id', '=', 's.id')
            ->join('forms as f', 'rc.form_id', '=', 'f.id')
            ->join('streams as st', 'rc.stream_id', '=', 'st.id')
            ->where('rc.academic_year_id', $data['academic_year_id'])
            ->where('rc.term_id', $data['term_id'])
            ->where('rc.stream_id', $data['stream_id'])
            ->select('rc.id', 'rc.report_number', 'rc.student_id', DB::raw("CONCAT(s.first_name,' ',s.last_name) as student_name"), 's.student_number', 'f.name as form_name', 'st.name as stream_name', 'rc.overall_average', 'rc.overall_grade', 'rc.class_position', 'rc.stream_total_students', 'rc.performance_trend', 'rc.status')
            ->orderBy('rc.class_position')
            ->orderByDesc('rc.overall_average')
            ->get());
    }

    public function subjectRankings(Request $request)
    {
        $data = $request->validate([
            'academic_year_id' => 'required',
            'term_id' => 'required',
            'stream_id' => 'required',
            'subject_id' => 'nullable',
        ]);

        $q = DB::table('report_card_subjects as rcs')
            ->join('report_cards as rc', 'rcs.report_card_id', '=', 'rc.id')
            ->join('students as s', 'rc.student_id', '=', 's.id')
            ->join('subjects as sub', 'rcs.subject_id', '=', 'sub.id')
            ->where('rc.academic_year_id', $data['academic_year_id'])
            ->where('rc.term_id', $data['term_id'])
            ->where('rc.stream_id', $data['stream_id'])
            ->select('rcs.subject_id', 'sub.name as subject_name', 'sub.code as subject_code', 'rc.student_id', DB::raw("CONCAT(s.first_name,' ',s.last_name) as student_name"), 's.student_number', 'rcs.subject_average', 'rcs.subject_grade', 'rcs.subject_position');

        if (!empty($data['subject_id'])) $q->where('rcs.subject_id', $data['subject_id']);

        return response()->json($q->orderBy('sub.name')->orderBy('rcs.subject_position')->orderByDesc('rcs.subject_average')->get());
    }

    public function topPerformers(Request $request)
    {
        $q = DB::table('report_cards as rc')
            ->join('students as s', 'rc.student_id', '=', 's.id')
            ->join('forms as f', 'rc.form_id', '=', 'f.id')
            ->join('streams as st', 'rc.stream_id', '=', 'st.id')
            ->select('rc.id', 'rc.report_number', DB::raw("CONCAT(s.first_name,' ',s.last_name) as student_name"), 's.student_number', 'f.name as form_name', 'st.name as stream_name', 'rc.overall_average', 'rc.overall_grade', 'rc.class_position')
            ->whereNotNull('rc.overall_average')
            ->orderByDesc('rc.overall_average')
            ->limit((int) ($request->limit ?? 10));

        if ($request->academic_year_id) $q->where('rc.academic_year_id', $request->academic_year_id);
        if ($request->term_id) $q->where('rc.term_id', $request->term_id);
        if ($request->stream_id) $q->where('rc.stream_id', $request->stream_id);

        return response()->json($q->get());
    }

    public function performanceTrends(Request $request)
    {
        $q = DB::table('report_cards as rc')
            ->join('forms as f', 'rc.form_id', '=', 'f.id')
            ->join('streams as st', 'rc.stream_id', '=', 'st.id')
            ->select('rc.performance_trend', 'f.name as form_name', 'st.name as stream_name', DB::raw('COUNT(*) as total'))
            ->groupBy('rc.performance_trend', 'f.name', 'st.name')
            ->orderBy('f.name')
            ->orderBy('st.name');

        if ($request->academic_year_id) $q->where('rc.academic_year_id', $request->academic_year_id);
        if ($request->term_id) $q->where('rc.term_id', $request->term_id);
        if ($request->stream_id) $q->where('rc.stream_id', $request->stream_id);

        return response()->json($q->get());
    }
}
