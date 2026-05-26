<?php

namespace App\Http\Controllers\Api\Timetable;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class TimetableController extends Controller
{
    private function decorateEntry(?object $entry): ?object
    {
        if (!$entry) return null;
        $first = $entry->teacher_first_name ?? $entry->first_name ?? null;
        $last = $entry->teacher_last_name ?? $entry->last_name ?? null;
        $entry->teacher_name = trim(($first ?? '') . ' ' . ($last ?? ''));
        return $entry;
    }

    private function decorateEntries(iterable $entries): array
    {
        $rows = [];
        foreach ($entries as $entry) {
            $rows[] = $this->decorateEntry($entry);
        }
        return $rows;
    }

    private function withJoins()
    {
        return DB::table('school_timetables as st')
            ->join('academic_years', 'st.academic_year_id', '=', 'academic_years.id')
            ->join('terms', 'st.term_id', '=', 'terms.id')
            ->join('forms', 'st.form_id', '=', 'forms.id')
            ->join('streams', 'st.stream_id', '=', 'streams.id')
            ->join('subjects', 'st.subject_id', '=', 'subjects.id')
            ->join('staff_members as sm', 'st.teacher_id', '=', 'sm.id')
            ->join('timetable_periods as tp', 'st.period_id', '=', 'tp.id')
            ->leftJoin('timetable_rooms as tr', 'st.room_id', '=', 'tr.id')
            ->select(
                'st.*',
                'academic_years.name as academic_year_name',
                'terms.name as term_name',
                'forms.name as form_name',
                'streams.name as stream_name',
                'subjects.name as subject_name',
                'subjects.code as subject_code',
                'sm.first_name as teacher_first_name',
                'sm.last_name as teacher_last_name',
                'tp.name as period_name',
                'tp.period_number',
                'tr.room_name',
                'tr.room_code'
            );
    }

    /* ── index ────────────────────────────────────────────────────────────── */
    public function index(Request $request)
    {
        $q = $this->withJoins()
            ->orderByRaw("CASE st.day_of_week WHEN 'monday' THEN 1 WHEN 'tuesday' THEN 2 WHEN 'wednesday' THEN 3 WHEN 'thursday' THEN 4 WHEN 'friday' THEN 5 WHEN 'saturday' THEN 6 ELSE 7 END")
            ->orderBy('tp.period_number');

        if ($request->academic_year_id) $q->where('st.academic_year_id', $request->academic_year_id);
        if ($request->term_id)          $q->where('st.term_id', $request->term_id);
        if ($request->stream_id)        $q->where('st.stream_id', $request->stream_id);
        if ($request->teacher_id)       $q->where('st.teacher_id', $request->teacher_id);
        if ($request->form_id)          $q->where('st.form_id', $request->form_id);

        return response()->json($this->decorateEntries($q->get()));
    }

    /* ── store ────────────────────────────────────────────────────────────── */
    public function store(Request $request)
    {
        $data = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id'          => 'required|exists:terms,id',
            'form_id'          => 'required|exists:forms,id',
            'stream_id'        => 'required|exists:streams,id',
            'subject_id'       => 'required|exists:subjects,id',
            'teacher_id'       => 'required|exists:staff_members,id',
            'room_id'          => 'nullable|exists:timetable_rooms,id',
            'period_id'        => 'required|exists:timetable_periods,id',
            'day_of_week'      => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday',
            'timetable_type'   => 'in:class,exam,remedial',
            'remarks'          => 'nullable|string|max:500',
        ]);

        // Get period times
        $period = DB::table('timetable_periods')->find($data['period_id']);
        abort_if(!$period, 422, 'Period not found.');

        // ── Clash checks ──────────────────────────────────────────────────
        $clashes = $this->checkClashes(
            $data['stream_id'], $data['teacher_id'], $data['room_id'] ?? null,
            $data['day_of_week'], $period->start_time, $period->end_time,
            $data['academic_year_id'], $data['term_id']
        );

        if (!empty($clashes)) {
            return response()->json(['message' => 'Timetable clash detected.', 'clashes' => $clashes], 422);
        }

        DB::beginTransaction();
        try {
            $id = DB::table('school_timetables')->insertGetId([
                'academic_year_id' => $data['academic_year_id'],
                'term_id'          => $data['term_id'],
                'form_id'          => $data['form_id'],
                'stream_id'        => $data['stream_id'],
                'subject_id'       => $data['subject_id'],
                'teacher_id'       => $data['teacher_id'],
                'room_id'          => $data['room_id'] ?? null,
                'period_id'        => $data['period_id'],
                'day_of_week'      => $data['day_of_week'],
                'start_time'       => $period->start_time,
                'end_time'         => $period->end_time,
                'timetable_type'   => $data['timetable_type'] ?? 'class',
                'remarks'          => $data['remarks'] ?? null,
                'created_by'       => Auth::id(),
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
            DB::commit();
            return response()->json(['message' => 'Timetable entry created.', 'entry' => $this->decorateEntry($this->withJoins()->where('st.id', $id)->first())], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    /* ── update ───────────────────────────────────────────────────────────── */
    public function update(Request $request, int $id)
    {
        $entry = DB::table('school_timetables')->find($id);
        abort_if(!$entry, 404);

        $data = $request->validate([
            'subject_id'     => 'required|exists:subjects,id',
            'teacher_id'     => 'required|exists:staff_members,id',
            'room_id'        => 'nullable|exists:timetable_rooms,id',
            'period_id'      => 'required|exists:timetable_periods,id',
            'day_of_week'    => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday',
            'timetable_type' => 'in:class,exam,remedial',
            'remarks'        => 'nullable|string|max:500',
        ]);

        $period = DB::table('timetable_periods')->find($data['period_id']);

        $clashes = $this->checkClashes(
            $entry->stream_id, $data['teacher_id'], $data['room_id'] ?? null,
            $data['day_of_week'], $period->start_time, $period->end_time,
            $entry->academic_year_id, $entry->term_id, $id
        );

        if (!empty($clashes)) {
            return response()->json(['message' => 'Timetable clash detected.', 'clashes' => $clashes], 422);
        }

        DB::table('school_timetables')->where('id', $id)->update([
            'subject_id'     => $data['subject_id'],
            'teacher_id'     => $data['teacher_id'],
            'room_id'        => $data['room_id'] ?? null,
            'period_id'      => $data['period_id'],
            'day_of_week'    => $data['day_of_week'],
            'start_time'     => $period->start_time,
            'end_time'       => $period->end_time,
            'timetable_type' => $data['timetable_type'] ?? $entry->timetable_type,
            'remarks'        => $data['remarks'] ?? null,
            'updated_at'     => now(),
        ]);

        return response()->json($this->decorateEntry($this->withJoins()->where('st.id', $id)->first()));
    }

    /* ── destroy ──────────────────────────────────────────────────────────── */
    public function destroy(int $id)
    {
        DB::table('school_timetables')->where('id', $id)->delete();
        return response()->json(['message' => 'Timetable entry deleted.']);
    }

    /* ── stream timetable (weekly grid) ──────────────────────────────────── */
    public function streamTimetable(int $streamId, Request $request)
    {
        $stream = DB::table('streams')->find($streamId);
        abort_if(!$stream, 404);

        $q = $this->withJoins()->where('st.stream_id', $streamId);
        if ($request->academic_year_id) $q->where('st.academic_year_id', $request->academic_year_id);
        if ($request->term_id)          $q->where('st.term_id', $request->term_id);

        $entries  = $q
            ->orderByRaw("CASE st.day_of_week WHEN 'monday' THEN 1 WHEN 'tuesday' THEN 2 WHEN 'wednesday' THEN 3 WHEN 'thursday' THEN 4 WHEN 'friday' THEN 5 ELSE 6 END")
            ->orderBy('tp.period_number')
            ->get();
        $periods  = DB::table('timetable_periods')->where('is_active', true)->orderBy('period_number')->get();

        return response()->json(['stream' => $stream, 'periods' => $periods, 'entries' => $this->decorateEntries($entries)]);
    }

    /* ── teacher timetable ────────────────────────────────────────────────── */
    public function teacherTimetable(int $teacherId, Request $request)
    {
        $teacher = DB::table('staff_members')->find($teacherId);
        abort_if(!$teacher, 404);

        $q = $this->withJoins()->where('st.teacher_id', $teacherId);
        if ($request->academic_year_id) $q->where('st.academic_year_id', $request->academic_year_id);
        if ($request->term_id)          $q->where('st.term_id', $request->term_id);

        $entries = $q
            ->orderByRaw("CASE st.day_of_week WHEN 'monday' THEN 1 WHEN 'tuesday' THEN 2 WHEN 'wednesday' THEN 3 WHEN 'thursday' THEN 4 WHEN 'friday' THEN 5 ELSE 6 END")
            ->orderBy('tp.period_number')
            ->get();
        $periods = DB::table('timetable_periods')->where('is_active', true)->orderBy('period_number')->get();

        return response()->json(['teacher' => $teacher, 'periods' => $periods, 'entries' => $this->decorateEntries($entries)]);
    }

    /* ── room timetable ───────────────────────────────────────────────────── */
    public function roomTimetable(int $roomId, Request $request)
    {
        $room = DB::table('timetable_rooms')->find($roomId);
        abort_if(!$room, 404);

        $q = $this->withJoins()->where('st.room_id', $roomId);
        if ($request->academic_year_id) $q->where('st.academic_year_id', $request->academic_year_id);
        if ($request->term_id)          $q->where('st.term_id', $request->term_id);

        $entries = $q
            ->orderByRaw("CASE st.day_of_week WHEN 'monday' THEN 1 WHEN 'tuesday' THEN 2 WHEN 'wednesday' THEN 3 WHEN 'thursday' THEN 4 WHEN 'friday' THEN 5 ELSE 6 END")
            ->orderBy('tp.period_number')
            ->get();
        $periods = DB::table('timetable_periods')->where('is_active', true)->orderBy('period_number')->get();

        return response()->json(['room' => $room, 'periods' => $periods, 'entries' => $this->decorateEntries($entries)]);
    }

    /* ── dashboard summary ────────────────────────────────────────────────── */
    public function dashboardSummary(Request $request)
    {
        $yearId = $request->academic_year_id;
        $termId = $request->term_id;

        $q = DB::table('school_timetables');
        if ($yearId) $q->where('academic_year_id', $yearId);
        if ($termId) $q->where('term_id', $termId);

        return response()->json([
            'total_entries'       => (clone $q)->count(),
            'streams_with_tt'     => (clone $q)->distinct('stream_id')->count('stream_id'),
            'teachers_scheduled'  => (clone $q)->distinct('teacher_id')->count('teacher_id'),
            'rooms_in_use'        => (clone $q)->whereNotNull('room_id')->distinct('room_id')->count('room_id'),
        ]);
    }

    /* ── clash check helper ───────────────────────────────────────────────── */
    private function checkClashes(int $streamId, int $teacherId, ?int $roomId, string $day, string $startTime, string $endTime, int $yearId, int $termId, ?int $excludeId = null): array
    {
        $clashes = [];
        $base = DB::table('school_timetables')
            ->where('academic_year_id', $yearId)
            ->where('term_id', $termId)
            ->where('day_of_week', $day)
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime);

        if ($excludeId) $base->where('id', '!=', $excludeId);

        // Stream clash
        if ((clone $base)->where('stream_id', $streamId)->exists()) {
            $clashes[] = 'Stream clash: this stream already has a lesson at this time.';
        }
        // Teacher clash
        if ((clone $base)->where('teacher_id', $teacherId)->exists()) {
            $clashes[] = 'Teacher clash: this teacher is already teaching at this time.';
        }
        // Room clash
        if ($roomId && (clone $base)->where('room_id', $roomId)->exists()) {
            $clashes[] = 'Room clash: this room is already in use at this time.';
        }

        return $clashes;
    }
}
