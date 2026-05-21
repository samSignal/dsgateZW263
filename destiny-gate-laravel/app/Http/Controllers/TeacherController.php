<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\AcademicProgress;
use App\Models\TeacherComment;
use App\Models\Attendance;
use App\Models\TeacherSubject;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    private function getStaff(): Staff
    {
        return Staff::where('user_id', auth()->id())->firstOrFail();
    }

    public function dashboard()
    {
        $staff = $this->getStaff();

        $myClasses = TeacherSubject::where('staff_id', $staff->id)
            ->with(['schoolClass', 'subject'])
            ->get()
            ->groupBy('class_id');

        $stats = [
            'my_classes'      => $myClasses->count(),
            'total_students'  => Student::whereIn('class_id', $myClasses->keys())->count(),
            'marks_recorded'  => AcademicProgress::where('recorded_by', auth()->id())->count(),
            'comments_added'  => TeacherComment::where('teacher_id', $staff->id)->count(),
        ];

        return view('teacher.dashboard', compact('stats', 'myClasses', 'staff'));
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

        $percentage = ($data['marks'] / $data['total_marks']) * 100;
        $grade = match (true) {
            $percentage >= 80 => 'A',
            $percentage >= 65 => 'B',
            $percentage >= 50 => 'C',
            default           => 'D',
        };

        AcademicProgress::create([
            ...$data,
            'percentage'  => round($percentage, 2),
            'grade'       => $grade,
            'recorded_by' => auth()->id(),
            'recorded_at' => now(),
        ]);

        return back()->with('success', 'Marks recorded successfully.');
    }

    public function addComment(Request $request)
    {
        $staff = $this->getStaff();

        $data = $request->validate([
            'student_id'           => 'required|exists:students,id',
            'class_id'             => 'required|exists:classes,id',
            'academic_year'        => 'required|string|max:20',
            'term'                 => 'required|in:term1,term2,term3',
            'progress'             => 'nullable|string',
            'participation'        => 'nullable|string',
            'homework'             => 'nullable|string',
            'behaviour'            => 'nullable|string',
            'areas_for_improvement'=> 'nullable|string',
            'strengths'            => 'nullable|string',
        ]);

        $data['teacher_id'] = $staff->id;

        TeacherComment::updateOrCreate(
            [
                'student_id'    => $data['student_id'],
                'class_id'      => $data['class_id'],
                'teacher_id'    => $staff->id,
                'academic_year' => $data['academic_year'],
                'term'          => $data['term'],
            ],
            $data
        );

        return back()->with('success', 'Comment saved.');
    }

    public function markAttendance(Request $request)
    {
        $data = $request->validate([
            'student_id'  => 'required|exists:students,id',
            'class_id'    => 'required|exists:classes,id',
            'date'        => 'required|date',
            'status'      => 'required|in:present,absent,late,excused,sick,early_departure',
            'remarks'     => 'nullable|string',
        ]);

        Attendance::updateOrCreate(
            [
                'student_id' => $data['student_id'],
                'class_id'   => $data['class_id'],
                'date'       => $data['date'],
            ],
            [
                ...$data,
                'recorded_by' => auth()->id(),
            ]
        );

        return back()->with('success', 'Attendance marked.');
    }

    public function myClasses()
    {
        $staff = $this->getStaff();

        $assignments = TeacherSubject::where('staff_id', $staff->id)
            ->with(['schoolClass.students', 'subject'])
            ->get();

        return view('teacher.classes', compact('assignments', 'staff'));
    }

    public function classStudents(SchoolClass $class)
    {
        $students = Student::where('class_id', $class->id)
            ->where('status', 'active')
            ->get();

        $subjects = Subject::all();

        return view('teacher.class-students', compact('class', 'students', 'subjects'));
    }
}
