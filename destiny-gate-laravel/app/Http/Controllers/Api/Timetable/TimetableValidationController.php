<?php

namespace App\Http\Controllers\Api\Timetable;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TimetableValidationController extends Controller
{
    private function overlaps(Request $request)
    {
        $data = $request->validate([
            'day_of_week' => 'required',
            'period_id' => 'required|exists:timetable_periods,id',
            'academic_year_id' => 'required',
            'term_id' => 'required',
            'ignore_id' => 'nullable|integer',
        ]);
        $period = DB::table('timetable_periods')->where('id', $data['period_id'])->first();
        $q = DB::table('timetables')
            ->where('academic_year_id', $data['academic_year_id'])
            ->where('term_id', $data['term_id'])
            ->where('day_of_week', $data['day_of_week'])
            ->where('start_time', '<', $period->end_time)
            ->where('end_time', '>', $period->start_time);
        if (!empty($data['ignore_id'])) $q->where('id', '!=', $data['ignore_id']);
        return $q;
    }

    public function validateTeacherClash(Request $request)
    {
        $request->validate(['teacher_id' => 'required']);
        $exists = (clone $this->overlaps($request))->where('teacher_id', $request->teacher_id)->exists();
        return response()->json(['has_clash' => $exists, 'message' => $exists ? 'Teacher clash detected.' : null]);
    }

    public function validateRoomClash(Request $request)
    {
        $request->validate(['room_id' => 'required']);
        $exists = (clone $this->overlaps($request))->where('room_id', $request->room_id)->exists();
        return response()->json(['has_clash' => $exists, 'message' => $exists ? 'Room clash detected.' : null]);
    }

    public function validateStreamClash(Request $request)
    {
        $request->validate(['stream_id' => 'required']);
        $exists = (clone $this->overlaps($request))->where('stream_id', $request->stream_id)->exists();
        return response()->json(['has_clash' => $exists, 'message' => $exists ? 'Stream clash detected.' : null]);
    }
}
