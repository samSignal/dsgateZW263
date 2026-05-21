<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\AcademicProgress;
use App\Models\TeacherComment;
use App\Models\Attendance;
use App\Models\TeacherSubject;
use Illuminate\Http\Request;

class TeacherApiController extends Controller
{
    private function getStaff(): Staff
    {
        return Staff::where('user_id', auth()->id())->firstOrFail();
    }

    public function dashboard()
    {
        $staff = $this->getStaff();
        $classIds = TeacherSubject::where('staff_id', $staff->id)->pluck('class_id')->unique();
        return response()->json([
            'staff'          => $staff,
            'my_classes'     => $classIds->count(),
            'total_students' => Student::whereIn('class_id', $classIds)->count(),
            'marks_recorded' => AcademicProgress::where('recorded_by', auth()->id())->count(),
            'comments_added' => TeacherComment::where('teacher_id', $staff->id)->count(),
        ]);
    }

    public function myClasses()
    {
        $staff = $this->getStaff();
        $assignments = TeacherSubject::where('staff_id', $staff->id)
            ->with(['schoolClass.students', 'subject'])
            ->get();
        return response()->json($assignments);
    }

    public function classStudents(SchoolClass $class)
    {
        $students = Student::where('class_id', $class->id)->where('status', 'active')->get();
        $subjects = Subject::all();
        return response()->json(['class' => $class, 'students' => $students, 'subjects' => $subjects]);
    }

    public function recordMarks(Request $request)
    {
        $data = $request->validate([
            'student_id'      => 'required|exists:students,id',
            'class_id'        => 'required|exists:classes,id',
            'subject_id'      => 'required|exists:subjects,id',
            'academic_year'   => 'required|string|max:20',
            'term'            => 'required|in:term1,term2,term3',
            'assessment_type' => 'required|in:weekly_test,monthly_test,assignment,exam,project',
            'marks'           => 'required|numeric|min:0',
            'total_marks'     => 'required|numeric|min:1',
        ]);
        $pct = ($data['marks'] / $data['total_marks']) * 100;
        $grade = match(true) {
            $pct >= 80 => 'A', $pct >= 65 => 'B', $pct >= 50 => 'C', default => 'D',
        };
        $progress = AcademicProgress::create([
            ...$data,
            'percentage'  => round($pct, 2),
            'grade'       => $grade,
            'recorded_by' => auth()->id(),
            'recorded_at' => now(),
        ]);
        return response()->json(['message' => 'Marks recorded.', 'progress' => $progress], 201);
    }

    public function addComment(Request $request)
    {
        $staff = $this->getStaff();
        $data = $request->validate([
            'student_id'            => 'required|exists:students,id',
            'class_id'              => 'required|exists:classes,id',
            'academic_year'         => 'required|string|max:20',
            'term'                  => 'required|in:term1,term2,term3',
            'progress'              => 'nullable|string',
            'participation'         => 'nullable|string',
            'homework'              => 'nullable|string',
            'behaviour'             => 'nullable|string',
            'areas_for_improvement' => 'nullable|string',
            'strengths'             => 'nullable|string',
        ]);
        $data['teacher_id'] = $staff->id;
        $comment = TeacherComment::updateOrCreate(
            ['student_id' => $data['student_id'], 'class_id' => $data['class_id'], 'teacher_id' => $staff->id, 'academic_year' => $data['academic_year'], 'term' => $data['term']],
            $data
        );
        return response()->json(['message' => 'Comment saved.', 'comment' => $comment]);
    }

    public function markAttendance(Request $request)
    {
        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'class_id'   => 'required|exists:classes,id',
            'date'       => 'required|date',
            'status'     => 'required|in:present,absent,late,excused,sick,early_departure',
            'remarks'    => 'nullable|string',
        ]);
        $att = Attendance::updateOrCreate(
            ['student_id' => $data['student_id'], 'class_id' => $data['class_id'], 'date' => $data['date']],
            [...$data, 'recorded_by' => auth()->id()]
        );
        return response()->json(['message' => 'Attendance marked.', 'attendance' => $att]);
    }
}
