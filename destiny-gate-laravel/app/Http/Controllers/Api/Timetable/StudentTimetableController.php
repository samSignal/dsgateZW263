<?php

namespace App\Http\Controllers\Api\Timetable;

use App\Http\Controllers\Controller;
use App\Support\StudentStreamResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class StudentTimetableController extends Controller
{
    public function myTimetable(Request $request)
    {
        $student = DB::table('students')
            ->where('students.user_id', Auth::id())->first();

        abort_if(!$student, 404, 'No student profile found.');
        $student = StudentStreamResolver::attachResolvedFields($student);
        abort_if(!$student->stream_id, 404, 'No stream assigned.');

        $q = DB::table('school_timetables as st')
            ->join('subjects', 'st.subject_id', '=', 'subjects.id')
            ->join('staff_members as sm', 'st.teacher_id', '=', 'sm.id')
            ->join('timetable_periods as tp', 'st.period_id', '=', 'tp.id')
            ->leftJoin('timetable_rooms as tr', 'st.room_id', '=', 'tr.id')
            ->select('st.day_of_week', 'st.start_time', 'st.end_time',
                'subjects.name as subject_name', 'subjects.code as subject_code',
                'sm.first_name as teacher_first_name',
                'sm.last_name as teacher_last_name',
                'tp.name as period_name', 'tp.period_number', 'tp.is_break',
                'tr.room_name')
            ->where('st.stream_id', $student->stream_id);

        if ($request->academic_year_id) $q->where('st.academic_year_id', $request->academic_year_id);
        if ($request->term_id)          $q->where('st.term_id', $request->term_id);

        $entries = $q
            ->orderByRaw("CASE st.day_of_week WHEN 'monday' THEN 1 WHEN 'tuesday' THEN 2 WHEN 'wednesday' THEN 3 WHEN 'thursday' THEN 4 WHEN 'friday' THEN 5 ELSE 6 END")
            ->orderBy('tp.period_number')
            ->get();
        foreach ($entries as $e) {
            $e->teacher_name = trim(($e->teacher_first_name ?? '') . ' ' . ($e->teacher_last_name ?? ''));
        }
        $periods = DB::table('timetable_periods')->where('is_active', true)->orderBy('period_number')->get();

        return response()->json(['student' => $student, 'periods' => $periods, 'entries' => $entries]);
    }
}
