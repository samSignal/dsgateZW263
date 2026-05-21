<?php

namespace App\Http\Controllers\Api\Discipline;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AttendanceSessionController extends Controller
{
    private function studentsForStream(int $formId, int $streamId)
    {
        $form = DB::table('forms')->where('id', $formId)->first();
        $stream = DB::table('streams')->where('id', $streamId)->first();
        abort_if(!$form || !$stream, 422, 'Invalid form or stream.');

        return DB::table('students as s')
            ->join('classes as c', 's.class_id', '=', 'c.id')
            ->select('s.id')
            ->where('s.status', 'active')
            ->where('c.class_name', $form->name)
            ->where('c.stream', $stream->name)
            ->pluck('s.id');
    }

    private function sessionQuery()
    {
        return DB::table('attendance_sessions as ats')
            ->join('academic_years as ay', 'ats.academic_year_id', '=', 'ay.id')
            ->join('terms as t', 'ats.term_id', '=', 't.id')
            ->join('forms as f', 'ats.form_id', '=', 'f.id')
            ->join('streams as st', 'ats.stream_id', '=', 'st.id')
            ->leftJoin('subjects as sub', 'ats.subject_id', '=', 'sub.id')
            ->leftJoin('users as u', 'ats.taken_by', '=', 'u.id')
            ->select('ats.*', 'ay.name as academic_year_name', 't.name as term_name', 'f.name as form_name', 'st.name as stream_name', 'sub.name as subject_name', 'u.name as taken_by_name');
    }

    public function meta()
    {
        return response()->json([
            'academic_years' => DB::table('academic_years')->orderByDesc('is_active')->orderByDesc('id')->get(),
            'terms' => DB::table('terms')->orderByDesc('is_current')->orderBy('name')->get(),
            'forms' => DB::table('forms')->orderBy('level')->get(),
            'streams' => DB::table('streams')->orderBy('name')->get(),
            'subjects' => DB::table('subjects')->orderBy('name')->get(),
            'behaviour_categories' => DB::table('behaviour_categories')->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function index(Request $request)
    {
        $q = $this->sessionQuery()->orderByDesc('ats.attendance_date')->orderByDesc('ats.id');
        if ($request->filled('date')) $q->where('ats.attendance_date', $request->date);
        if ($request->filled('stream_id')) $q->where('ats.stream_id', $request->stream_id);
        if ($request->filled('status')) $q->where('ats.status', $request->status);
        return response()->json($q->limit((int)($request->limit ?? 100))->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id' => 'required|exists:terms,id',
            'form_id' => 'required|exists:forms,id',
            'stream_id' => 'required|exists:streams,id',
            'attendance_date' => 'required|date',
            'session_type' => 'required|in:morning,afternoon,lesson',
            'subject_id' => 'nullable|exists:subjects,id',
        ]);

        $duplicate = DB::table('attendance_sessions')
            ->where('stream_id', $data['stream_id'])
            ->where('attendance_date', $data['attendance_date'])
            ->where('session_type', $data['session_type'])
            ->where(function ($q) use ($data) {
                if (!empty($data['subject_id'])) $q->where('subject_id', $data['subject_id']);
                else $q->whereNull('subject_id');
            })
            ->exists();
        if ($duplicate) return response()->json(['message' => 'Attendance session already exists for this stream, date, and session type.'], 422);

        DB::beginTransaction();
        try {
            $sessionId = DB::table('attendance_sessions')->insertGetId([
                ...$data,
                'subject_id' => $data['subject_id'] ?? null,
                'taken_by' => Auth::id(),
                'status' => 'draft',
                'submitted_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($this->studentsForStream((int)$data['form_id'], (int)$data['stream_id']) as $studentId) {
                DB::table('student_attendance_records')->insert([
                    'attendance_session_id' => $sessionId,
                    'student_id' => $studentId,
                    'status' => 'present',
                    'recorded_by' => Auth::id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();
            return response()->json(['message' => 'Attendance session created.', 'session_id' => $sessionId], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    public function show(int $id)
    {
        $session = $this->sessionQuery()->where('ats.id', $id)->first();
        abort_if(!$session, 404, 'Attendance session not found.');
        $records = DB::table('student_attendance_records as ar')
            ->join('students as s', 'ar.student_id', '=', 's.id')
            ->select('ar.*', DB::raw("CONCAT(s.first_name,' ',s.last_name) as student_name"), 's.student_number', 's.admission_number')
            ->where('ar.attendance_session_id', $id)
            ->orderBy('s.last_name')
            ->orderBy('s.first_name')
            ->get();
        return response()->json([...(array)$session, 'records' => $records]);
    }

    public function update(Request $request, int $id)
    {
        $session = DB::table('attendance_sessions')->where('id', $id)->first();
        abort_if(!$session, 404, 'Attendance session not found.');
        if ($session->status === 'submitted' && !in_array($request->user()->role, ['admin', 'headmaster'])) {
            return response()->json(['message' => 'Submitted attendance can only be edited by Admin or Headmaster.'], 403);
        }
        $data = $request->validate(['attendance_date' => 'required|date', 'session_type' => 'required|in:morning,afternoon,lesson', 'subject_id' => 'nullable|exists:subjects,id']);
        DB::table('attendance_sessions')->where('id', $id)->update([...$data, 'updated_at' => now()]);
        return response()->json(['message' => 'Attendance session updated.']);
    }

    public function submit(int $id)
    {
        abort_if(!DB::table('attendance_sessions')->where('id', $id)->exists(), 404, 'Attendance session not found.');
        DB::table('attendance_sessions')->where('id', $id)->update(['status' => 'submitted', 'submitted_at' => now(), 'updated_at' => now()]);
        return response()->json(['message' => 'Attendance submitted.']);
    }

    public function destroy(int $id)
    {
        $session = DB::table('attendance_sessions')->where('id', $id)->first();
        abort_if(!$session, 404, 'Attendance session not found.');
        if ($session->status === 'submitted') return response()->json(['message' => 'Submitted sessions cannot be deleted.'], 422);
        DB::beginTransaction();
        try {
            DB::table('student_attendance_records')->where('attendance_session_id', $id)->delete();
            DB::table('attendance_sessions')->where('id', $id)->delete();
            DB::commit();
            return response()->json(['message' => 'Attendance session deleted.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }
}
