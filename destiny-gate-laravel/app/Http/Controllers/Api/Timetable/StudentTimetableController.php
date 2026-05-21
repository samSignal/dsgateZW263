<?php

namespace App\Http\Controllers\Api\Timetable;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class StudentTimetableController extends Controller
{
    public function myTimetable(Request $request)
    {
        $student = DB::table('students')
            ->leftJoin('classes', 'students.class_id', '=', 'classes.id')
            ->select('students.*', 'classes.stream_id', 'classes.class_name')
            ->where('students.user_id', Auth::id())->first();

        abort_if(!$student, 404, 'No student profile found.');
        abort_if(!$student->stream_id, 404, 'No stream assigned.');

        $q = DB::table('school_timetables as st')
            ->join('subjects', 'st.subject_id', '=', 'subjects.id')
            ->join('staff_members as sm', 'st.teacher_id', '=', 'sm.id')
            ->join('timetable_periods as tp', 'st.period_id', '=', 'tp.id')
            ->leftJoin('timetable_rooms as tr', 'st.room_id', '=', 'tr.id')
            ->select('st.day_of_week', 'st.start_time', 'st.end_time',
                'subjects.name as subject_name', 'subjects.code as subject_code',
                DB::raw("CONCAT(sm.first_name,' ',sm.last_name) as teacher_name"),
                'tp.name as period_name', 'tp.period_number', 'tp.is_break',
                'tr.room_name')
            ->where('st.stream_id', $student->stream_id);

        if ($request->academic_year_id) $q->where('st.academic_year_id', $request->academic_year_id);
        if ($request->term_id)          $q->where('st.term_id', $request->term_id);

        $entries = $q->orderByRaw("FIELD(st.day_of_week,'monday','tuesday','wednesday','thursday','friday')")->orderBy('tp.period_number')->get();
        $periods = DB::table('timetable_periods')->where('is_active', true)->orderBy('period_number')->get();

        return response()->json(['student' => $student, 'periods' => $periods, 'entries' => $entries]);
    }
}
