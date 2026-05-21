<?php

namespace App\Http\Controllers;

use App\Models\Guardian;
use App\Models\Student;
use App\Models\Announcement;
use Illuminate\Http\Request;

class ParentController extends Controller
{
    public function portal()
    {
        $guardian = Guardian::where('user_id', auth()->id())
            ->with('student.schoolClass')
            ->first();

        if (!$guardian) {
            return view('parent.no-student');
        }

        $student = $guardian->student;
        $student->load(['fees', 'academicProgress.subject', 'attendance', 'behaviourRecords']);

        $announcements = Announcement::where('is_active', true)
            ->whereIn('audience', ['all', 'parents'])
            ->latest()
            ->take(5)
            ->get();

        $stats = [
            'outstanding_fees' => $student->fees->where('status', '!=', 'paid')->sum('balance'),
            'attendance_rate'  => $this->calcAttendanceRate($student),
            'avg_grade'        => $this->calcAvgGrade($student),
            'behaviour_cases'  => $student->behaviourRecords->count(),
        ];

        return view('parent.portal', compact('guardian', 'student', 'announcements', 'stats'));
    }

    private function calcAttendanceRate(Student $student): float
    {
        $total   = $student->attendance->count();
        $present = $student->attendance->whereIn('status', ['present', 'late'])->count();
        return $total > 0 ? round(($present / $total) * 100, 1) : 0;
    }

    private function calcAvgGrade(Student $student): string
    {
        $avg = $student->academicProgress->avg('percentage');
        if (!$avg) return 'N/A';
        return match (true) {
            $avg >= 80 => 'A',
            $avg >= 65 => 'B',
            $avg >= 50 => 'C',
            default    => 'D',
        };
    }
}
