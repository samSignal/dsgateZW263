<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Announcement;

class StudentPortalApiController extends Controller
{
    public function portal()
    {
        $student = Student::where('user_id', auth()->id())
            ->with(['schoolClass', 'academicProgress.subject', 'attendance', 'schoolClass.timetables.subject', 'schoolClass.timetables.teacher'])
            ->first();

        if (!$student) {
            return response()->json(['message' => 'No student profile found.'], 404);
        }

        $announcements = Announcement::where('is_active', true)->whereIn('audience', ['all', 'students'])->latest()->take(5)->get();

        return response()->json(['student' => $student, 'announcements' => $announcements]);
    }
}
