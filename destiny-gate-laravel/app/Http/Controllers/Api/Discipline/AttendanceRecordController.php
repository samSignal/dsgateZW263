<?php

namespace App\Http\Controllers\Api\Discipline;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AttendanceRecordController extends Controller
{
    private function canEditSession(Request $request, int $sessionId): bool
    {
        $status = DB::table('attendance_sessions')->where('id', $sessionId)->value('status');
        return $status !== 'submitted' || in_array($request->user()->role, ['admin', 'headmaster']);
    }

    private function notifyForAttendance(object $record, string $status, ?string $arrivalTime): void
    {
        if (!in_array($status, ['absent', 'late', 'early_departure'])) return;

        $session = DB::table('attendance_sessions')->where('id', $record->attendance_session_id)->first();
        $student = DB::table('students')->where('id', $record->student_id)->first();
        $title = match ($status) {
            'absent' => 'Attendance Alert: Absent',
            'late' => 'Attendance Alert: Late Arrival',
            default => 'Attendance Alert: Early Departure',
        };
        $message = match ($status) {
            'absent' => "Your child {$student->first_name} {$student->last_name} was marked absent on {$session->attendance_date}.",
            'late' => "Your child {$student->first_name} {$student->last_name} arrived late on {$session->attendance_date}" . ($arrivalTime ? " at {$arrivalTime}." : '.'),
            default => "Your child {$student->first_name} {$student->last_name} left school early on {$session->attendance_date}.",
        };

        $guardians = DB::table('guardians')->where('student_id', $record->student_id)
            ->where(function ($q) { $q->where('can_receive_notifications', true)->orWhereNull('can_receive_notifications'); })
            ->get();
        foreach ($guardians as $guardian) {
            DB::table('student_notifications')->insert([
                'student_id' => $record->student_id,
                'guardian_id' => $guardian->id,
                'notification_type' => 'attendance',
                'title' => $title,
                'message' => $message,
                'channel' => 'portal',
                'status' => 'sent',
                'related_type' => 'student_attendance_records',
                'related_id' => $record->id,
                'created_by' => Auth::id(),
                'sent_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function updateRecord(Request $request, int $id)
    {
        $record = DB::table('student_attendance_records')->where('id', $id)->first();
        abort_if(!$record, 404, 'Attendance record not found.');
        if (!$this->canEditSession($request, $record->attendance_session_id)) return response()->json(['message' => 'Submitted attendance can only be edited by Admin or Headmaster.'], 403);

        $data = $request->validate([
            'status' => 'required|in:present,absent,late,excused,sick,early_departure',
            'arrival_time' => 'nullable|date_format:H:i',
            'reason' => 'nullable|string',
            'remarks' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            DB::table('student_attendance_records')->where('id', $id)->update([...$data, 'recorded_by' => Auth::id(), 'updated_at' => now()]);
            $updated = DB::table('student_attendance_records')->where('id', $id)->first();
            $this->notifyForAttendance($updated, $data['status'], $data['arrival_time'] ?? null);
            DB::commit();
            return response()->json(['message' => 'Attendance record updated.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    public function bulkUpdateRecords(Request $request, int $sessionId)
    {
        abort_if(!DB::table('attendance_sessions')->where('id', $sessionId)->exists(), 404, 'Attendance session not found.');
        if (!$this->canEditSession($request, $sessionId)) return response()->json(['message' => 'Submitted attendance can only be edited by Admin or Headmaster.'], 403);

        $data = $request->validate([
            'records' => 'required|array|min:1',
            'records.*.id' => 'required|exists:student_attendance_records,id',
            'records.*.status' => 'required|in:present,absent,late,excused,sick,early_departure',
            'records.*.arrival_time' => 'nullable|date_format:H:i',
            'records.*.reason' => 'nullable|string',
            'records.*.remarks' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            foreach ($data['records'] as $row) {
                DB::table('student_attendance_records')->where('id', $row['id'])->where('attendance_session_id', $sessionId)->update([
                    'status' => $row['status'],
                    'arrival_time' => $row['arrival_time'] ?? null,
                    'reason' => $row['reason'] ?? null,
                    'remarks' => $row['remarks'] ?? null,
                    'recorded_by' => Auth::id(),
                    'updated_at' => now(),
                ]);
                $updated = DB::table('student_attendance_records')->where('id', $row['id'])->first();
                if ($updated) $this->notifyForAttendance($updated, $row['status'], $row['arrival_time'] ?? null);
            }
            DB::commit();
            return response()->json(['message' => 'Attendance records updated.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    public function studentHistory(int $studentId)
    {
        $records = DB::table('student_attendance_records as ar')
            ->join('attendance_sessions as ats', 'ar.attendance_session_id', '=', 'ats.id')
            ->join('forms as f', 'ats.form_id', '=', 'f.id')
            ->join('streams as st', 'ats.stream_id', '=', 'st.id')
            ->select('ar.*', 'ats.attendance_date', 'ats.session_type', 'f.name as form_name', 'st.name as stream_name')
            ->where('ar.student_id', $studentId)
            ->orderByDesc('ats.attendance_date')
            ->get();
        return response()->json($records);
    }
}
