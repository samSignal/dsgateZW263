<?php

namespace App\Http\Controllers\Api\Discipline;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ParentBehaviourAttendanceController extends Controller
{
    private function childIds() { return DB::table('guardians')->where('user_id', Auth::id())->pluck('student_id')->toArray(); }
    private function assertChild(int $id): void { abort_if(!in_array($id, $this->childIds()), 403, 'You cannot view this child.'); }

    public function myChildrenSummary()
    {
        $ids = $this->childIds();
        $children = DB::table('students as s')->leftJoin('classes as c', 's.class_id', '=', 'c.id')->select('s.id', 's.first_name', 's.last_name', 's.student_number', 'c.class_name', 'c.stream')->whereIn('s.id', $ids)->get();
        return response()->json([
            'children' => $children,
            'notifications' => DB::table('student_notifications')->whereIn('student_id', $ids)->latest()->limit(20)->get(),
            'open_actions' => DB::table('discipline_actions')->whereIn('student_id', $ids)->where('status', 'open')->count(),
            'absences' => DB::table('student_attendance_records')->whereIn('student_id', $ids)->where('status', 'absent')->count(),
            'late' => DB::table('student_attendance_records')->whereIn('student_id', $ids)->where('status', 'late')->count(),
        ]);
    }

    public function childAttendance(int $id) { $this->assertChild($id); return response()->json($this->attendance($id)); }
    public function childBehaviour(int $id) { $this->assertChild($id); return response()->json($this->behaviour($id)); }
    public function childDiscipline(int $id) { $this->assertChild($id); return response()->json($this->discipline($id)); }
    public function childNotifications(int $id) { $this->assertChild($id); return response()->json(DB::table('student_notifications')->where('student_id', $id)->latest()->get()); }

    private function attendance(int $id)
    {
        return DB::table('student_attendance_records as ar')->join('attendance_sessions as ats', 'ar.attendance_session_id', '=', 'ats.id')->select('ar.*', 'ats.attendance_date', 'ats.session_type')->where('ar.student_id', $id)->orderByDesc('ats.attendance_date')->get();
    }

    private function behaviour(int $id)
    {
        return DB::table('behaviour_incidents as bi')->join('behaviour_categories as bc', 'bi.behaviour_category_id', '=', 'bc.id')->select('bi.*', 'bc.name as category_name', 'bc.type as category_type')->where('bi.student_id', $id)->orderByDesc('bi.incident_date')->get();
    }

    private function discipline(int $id)
    {
        return DB::table('discipline_actions as da')->join('behaviour_incidents as bi', 'da.behaviour_incident_id', '=', 'bi.id')->select('da.*', 'bi.incident_number', 'bi.title as incident_title')->where('da.student_id', $id)->orderByDesc('da.action_date')->get();
    }
}
