<?php

namespace App\Http\Controllers\Api\Discipline;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceReportController extends Controller
{
    public function dashboardSummary()
    {
        $today = date('Y-m-d');
        $total = DB::table('student_attendance_records as ar')->join('attendance_sessions as ats', 'ar.attendance_session_id', '=', 'ats.id')->where('ats.attendance_date', $today)->count();
        $present = DB::table('student_attendance_records as ar')->join('attendance_sessions as ats', 'ar.attendance_session_id', '=', 'ats.id')->where('ats.attendance_date', $today)->whereIn('ar.status', ['present', 'late'])->count();
        return response()->json([
            'attendance_rate_today' => $total > 0 ? round(($present / $total) * 100, 1) : 0,
            'absent_today' => DB::table('student_attendance_records as ar')->join('attendance_sessions as ats', 'ar.attendance_session_id', '=', 'ats.id')->where('ats.attendance_date', $today)->where('ar.status', 'absent')->count(),
            'late_today' => DB::table('student_attendance_records as ar')->join('attendance_sessions as ats', 'ar.attendance_session_id', '=', 'ats.id')->where('ats.attendance_date', $today)->where('ar.status', 'late')->count(),
            'submitted_sessions_today' => DB::table('attendance_sessions')->where('attendance_date', $today)->where('status', 'submitted')->count(),
            'draft_sessions_today' => DB::table('attendance_sessions')->where('attendance_date', $today)->where('status', 'draft')->count(),
        ]);
    }

    public function dailyByStream(Request $request)
    {
        $date = $request->query('date', date('Y-m-d'));
        $q = DB::table('attendance_sessions as ats')
            ->join('student_attendance_records as ar', 'ats.id', '=', 'ar.attendance_session_id')
            ->join('forms as f', 'ats.form_id', '=', 'f.id')
            ->join('streams as st', 'ats.stream_id', '=', 'st.id')
            ->select('ats.id as session_id', 'f.name as form_name', 'st.name as stream_name', 'ats.session_type', DB::raw('COUNT(*) as total'), DB::raw("SUM(ar.status='present') as present"), DB::raw("SUM(ar.status='absent') as absent"), DB::raw("SUM(ar.status='late') as late"), DB::raw("SUM(ar.status='early_departure') as early_departure"))
            ->where('ats.attendance_date', $date)
            ->groupBy('ats.id', 'f.name', 'st.name', 'ats.session_type');
        if ($request->stream_id) $q->where('ats.stream_id', $request->stream_id);
        return response()->json($q->get());
    }

    public function absenteeList(Request $request) { return $this->statusList($request, 'absent'); }
    public function lateList(Request $request) { return $this->statusList($request, 'late'); }

    private function statusList(Request $request, string $status)
    {
        $date = $request->query('date', date('Y-m-d'));
        return response()->json(DB::table('student_attendance_records as ar')
            ->join('attendance_sessions as ats', 'ar.attendance_session_id', '=', 'ats.id')
            ->join('students as s', 'ar.student_id', '=', 's.id')
            ->join('forms as f', 'ats.form_id', '=', 'f.id')
            ->join('streams as st', 'ats.stream_id', '=', 'st.id')
            ->select('ar.*', 'ats.attendance_date', 'ats.session_type', DB::raw("CONCAT(s.first_name,' ',s.last_name) as student_name"), 's.student_number', 'f.name as form_name', 'st.name as stream_name')
            ->where('ats.attendance_date', $date)
            ->where('ar.status', $status)
            ->orderBy('s.last_name')
            ->get());
    }

    public function studentAttendancePercentage(Request $request)
    {
        $q = DB::table('student_attendance_records as ar')
            ->join('attendance_sessions as ats', 'ar.attendance_session_id', '=', 'ats.id')
            ->join('students as s', 'ar.student_id', '=', 's.id')
            ->select('s.id as student_id', DB::raw("CONCAT(s.first_name,' ',s.last_name) as student_name"), 's.student_number', DB::raw('COUNT(*) as total_sessions'), DB::raw("SUM(ar.status IN ('present','late')) as attended_sessions"), DB::raw("ROUND((SUM(ar.status IN ('present','late')) / COUNT(*)) * 100, 1) as attendance_percentage"))
            ->groupBy('s.id', 's.first_name', 's.last_name', 's.student_number');
        if ($request->student_id) $q->where('s.id', $request->student_id);
        if ($request->date_from) $q->where('ats.attendance_date', '>=', $request->date_from);
        if ($request->date_to) $q->where('ats.attendance_date', '<=', $request->date_to);
        return response()->json($q->get());
    }

    public function streamAttendancePercentage(Request $request)
    {
        $q = DB::table('student_attendance_records as ar')
            ->join('attendance_sessions as ats', 'ar.attendance_session_id', '=', 'ats.id')
            ->join('forms as f', 'ats.form_id', '=', 'f.id')
            ->join('streams as st', 'ats.stream_id', '=', 'st.id')
            ->select('ats.stream_id', 'f.name as form_name', 'st.name as stream_name', DB::raw('COUNT(*) as total_records'), DB::raw("SUM(ar.status IN ('present','late')) as attended_records"), DB::raw("ROUND((SUM(ar.status IN ('present','late')) / COUNT(*)) * 100, 1) as attendance_percentage"))
            ->groupBy('ats.stream_id', 'f.name', 'st.name');
        if ($request->date_from) $q->where('ats.attendance_date', '>=', $request->date_from);
        if ($request->date_to) $q->where('ats.attendance_date', '<=', $request->date_to);
        return response()->json($q->get());
    }

    public function monthlyReport(Request $request)
    {
        $month = $request->query('month', date('Y-m'));
        return response()->json(DB::table('student_attendance_records as ar')
            ->join('attendance_sessions as ats', 'ar.attendance_session_id', '=', 'ats.id')
            ->select(DB::raw('DATE(ats.attendance_date) as date'), DB::raw('COUNT(*) as total'), DB::raw("SUM(ar.status='present') as present"), DB::raw("SUM(ar.status='absent') as absent"), DB::raw("SUM(ar.status='late') as late"))
            ->where('ats.attendance_date', 'like', $month . '%')
            ->groupBy(DB::raw('DATE(ats.attendance_date)'))
            ->orderBy('date')
            ->get());
    }

    public function repeatedAbsenteeism(Request $request)
    {
        $termId = $request->query('term_id');
        $q = DB::table('student_attendance_records as ar')
            ->join('attendance_sessions as ats', 'ar.attendance_session_id', '=', 'ats.id')
            ->join('students as s', 'ar.student_id', '=', 's.id')
            ->select('s.id as student_id', DB::raw("CONCAT(s.first_name,' ',s.last_name) as student_name"), 's.student_number', DB::raw("SUM(ar.status='absent') as absences"), DB::raw("SUM(ar.status='late') as late_count"))
            ->whereIn('ar.status', ['absent', 'late'])
            ->groupBy('s.id', 's.first_name', 's.last_name', 's.student_number')
            ->havingRaw('absences >= 5 OR late_count >= 3');
        if ($termId) $q->where('ats.term_id', $termId);
        return response()->json($q->get());
    }
}
