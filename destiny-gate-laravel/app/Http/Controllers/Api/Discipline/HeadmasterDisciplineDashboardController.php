<?php

namespace App\Http\Controllers\Api\Discipline;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class HeadmasterDisciplineDashboardController extends Controller
{
    public function summary()
    {
        $today = date('Y-m-d');
        $total = DB::table('student_attendance_records as ar')->join('attendance_sessions as ats', 'ar.attendance_session_id', '=', 'ats.id')->where('ats.attendance_date', $today)->count();
        $attended = DB::table('student_attendance_records as ar')->join('attendance_sessions as ats', 'ar.attendance_session_id', '=', 'ats.id')->where('ats.attendance_date', $today)->whereIn('ar.status', ['present', 'late'])->count();
        return response()->json([
            'attendance_rate' => $total ? round(($attended / $total) * 100, 1) : 0,
            'absent_today' => DB::table('student_attendance_records as ar')->join('attendance_sessions as ats', 'ar.attendance_session_id', '=', 'ats.id')->where('ats.attendance_date', $today)->where('ar.status', 'absent')->count(),
            'late_today' => DB::table('student_attendance_records as ar')->join('attendance_sessions as ats', 'ar.attendance_session_id', '=', 'ats.id')->where('ats.attendance_date', $today)->where('ar.status', 'late')->count(),
            'open_discipline_cases' => DB::table('discipline_actions')->where('status', 'open')->count(),
            'high_severity_incidents' => DB::table('behaviour_incidents')->whereIn('severity', ['high', 'critical'])->where('review_status', '!=', 'closed')->count(),
            'parent_notifications' => DB::table('student_notifications')->whereDate('created_at', $today)->count(),
        ]);
    }

    public function highSeverityIncidents()
    {
        return response()->json(DB::table('behaviour_incidents as bi')->join('students as s', 'bi.student_id', '=', 's.id')->join('behaviour_categories as bc', 'bi.behaviour_category_id', '=', 'bc.id')->select('bi.*', DB::raw("CONCAT(s.first_name,' ',s.last_name) as student_name"), 's.student_number', 'bc.name as category_name')->whereIn('bi.severity', ['high', 'critical'])->orderByDesc('bi.incident_date')->get());
    }

    public function repeatedOffenders()
    {
        return response()->json(DB::table('behaviour_incidents as bi')->join('behaviour_categories as bc', 'bi.behaviour_category_id', '=', 'bc.id')->join('students as s', 'bi.student_id', '=', 's.id')->select('s.id as student_id', DB::raw("CONCAT(s.first_name,' ',s.last_name) as student_name"), 's.student_number', DB::raw('COUNT(*) as negative_incidents'))->where('bc.type', 'negative')->groupBy('s.id', 's.first_name', 's.last_name', 's.student_number')->havingRaw('COUNT(*) >= 3')->orderByDesc('negative_incidents')->get());
    }

    public function openCases()
    {
        return response()->json(DB::table('discipline_actions as da')->join('students as s', 'da.student_id', '=', 's.id')->select('da.*', DB::raw("CONCAT(s.first_name,' ',s.last_name) as student_name"), 's.student_number')->where('da.status', 'open')->orderByDesc('da.action_date')->get());
    }

    public function attendanceTrends()
    {
        return response()->json(DB::table('student_attendance_records as ar')->join('attendance_sessions as ats', 'ar.attendance_session_id', '=', 'ats.id')->join('forms as f', 'ats.form_id', '=', 'f.id')->join('streams as st', 'ats.stream_id', '=', 'st.id')->select('f.name as form_name', 'st.name as stream_name', DB::raw('COUNT(*) as total'), DB::raw("SUM(ar.status='absent') as absent"), DB::raw("SUM(ar.status='late') as late"), DB::raw("ROUND((SUM(ar.status IN ('present','late')) / COUNT(*)) * 100, 1) as attendance_rate"))->groupBy('f.name', 'st.name')->get());
    }
}
