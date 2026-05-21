<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guardian;
use App\Models\Announcement;

class ParentApiController extends Controller
{
    public function portal()
    {
        $guardian = Guardian::where('user_id', auth()->id())
            ->with(['student.schoolClass', 'student.fees', 'student.academicProgress.subject', 'student.attendance', 'student.behaviourRecords'])
            ->first();

        if (!$guardian) {
            return response()->json(['message' => 'No student linked to this account.'], 404);
        }

        $student = $guardian->student;
        $total = $student->attendance->count();
        $present = $student->attendance->whereIn('status', ['present', 'late'])->count();
        $attendanceRate = $total > 0 ? round(($present / $total) * 100, 1) : 0;
        $avg = $student->academicProgress->avg('percentage');
        $avgGrade = !$avg ? 'N/A' : match(true) { $avg >= 80 => 'A', $avg >= 65 => 'B', $avg >= 50 => 'C', default => 'D' };

        $announcements = Announcement::where('is_active', true)->whereIn('audience', ['all', 'parents'])->latest()->take(5)->get();

        return response()->json([
            'guardian'      => $guardian,
            'student'       => $student,
            'announcements' => $announcements,
            'stats' => [
                'outstanding_fees' => $student->fees->where('status', '!=', 'paid')->sum('balance'),
                'attendance_rate'  => $attendanceRate,
                'avg_grade'        => $avgGrade,
                'behaviour_cases'  => $student->behaviourRecords->count(),
            ],
        ]);
    }
}
