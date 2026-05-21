<?php

namespace App\Http\Controllers\Api\Discipline;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StudentBehaviourAttendanceController extends Controller
{
    private function studentId(): int
    {
        $id = DB::table('students')->where('user_id', Auth::id())->value('id');
        abort_if(!$id, 404, 'Student profile not found.');
        return (int)$id;
    }

    public function myAttendance()
    {
        $id = $this->studentId();
        return response()->json(DB::table('student_attendance_records as ar')->join('attendance_sessions as ats', 'ar.attendance_session_id', '=', 'ats.id')->select('ar.*', 'ats.attendance_date', 'ats.session_type')->where('ar.student_id', $id)->orderByDesc('ats.attendance_date')->get());
    }

    public function myBehaviour()
    {
        $id = $this->studentId();
        return response()->json(DB::table('behaviour_incidents as bi')->join('behaviour_categories as bc', 'bi.behaviour_category_id', '=', 'bc.id')->select('bi.*', 'bc.name as category_name', 'bc.type as category_type')->where('bi.student_id', $id)->orderByDesc('bi.incident_date')->get());
    }

    public function myDiscipline()
    {
        $id = $this->studentId();
        return response()->json(DB::table('discipline_actions as da')->join('behaviour_incidents as bi', 'da.behaviour_incident_id', '=', 'bi.id')->select('da.*', 'bi.incident_number', 'bi.title as incident_title')->where('da.student_id', $id)->orderByDesc('da.action_date')->get());
    }

    public function myNotifications()
    {
        $id = $this->studentId();
        return response()->json(DB::table('student_notifications')->where('student_id', $id)->latest()->get());
    }
}
