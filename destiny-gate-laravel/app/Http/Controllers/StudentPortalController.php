<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Announcement;
use Illuminate\Http\Request;

class StudentPortalController extends Controller
{
    public function portal()
    {
        $student = Student::where('user_id', auth()->id())
            ->with([
                'schoolClass',
                'academicProgress.subject',
                'attendance',
                'schoolClass.timetables.subject',
                'schoolClass.timetables.teacher',
            ])
            ->first();

        if (!$student) {
            return view('student.no-profile');
        }

        $announcements = Announcement::where('is_active', true)
            ->whereIn('audience', ['all', 'students'])
            ->latest()
            ->take(5)
            ->get();

        return view('student.portal', compact('student', 'announcements'));
    }
}
