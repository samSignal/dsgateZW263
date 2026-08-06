<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Guardian;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class StudentController extends Controller
{
    public function index()
    {
        $students = Student::with('schoolClass')->latest()->paginate(20);
        return view('students.index', compact('students'));
    }

    public function create()
    {
        $classes = SchoolClass::all();
        return view('students.create', compact('classes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name'        => 'required|string|max:100',
            'last_name'         => 'required|string|max:100',
            'email'             => 'nullable|email|unique:students,email',
            'date_of_birth'     => 'nullable|date',
            'gender'            => 'nullable|in:male,female,other',
            'class_id'          => 'nullable|exists:classes,id',
            'admission_date'    => 'required|date',
            'blood_type'        => 'nullable|string|max:10',
            'allergies'         => 'nullable|string',
            'medical_conditions'=> 'nullable|string',
        ]);

        $admissionNumber = 'DGI-' . date('Y') . '-' . str_pad(Student::count() + 1, 4, '0', STR_PAD_LEFT);
        $data['admission_number'] = $admissionNumber;

        $student = Student::create($data);

        return redirect()->route('students.show', $student)->with('success', 'Student created successfully.');
    }

    public function show(Student $student)
    {
        $student->load(['schoolClass', 'guardians', 'fees', 'academicProgress.subject', 'attendance', 'behaviourRecords', 'teacherComments']);
        return view('students.show', compact('student'));
    }

    public function edit(Student $student)
    {
        $classes = SchoolClass::all();
        return view('students.edit', compact('student', 'classes'));
    }

    public function update(Request $request, Student $student)
    {
        $data = $request->validate([
            'first_name'         => 'required|string|max:100',
            'last_name'          => 'required|string|max:100',
            'email'              => 'nullable|email|unique:students,email,' . $student->id,
            'date_of_birth'      => 'nullable|date',
            'gender'             => 'nullable|in:male,female,other',
            'class_id'           => 'nullable|exists:classes,id',
            'status'             => 'required|in:active,inactive,transferred,graduated,suspended,deceased',
            'blood_type'         => 'nullable|string|max:10',
            'allergies'          => 'nullable|string',
            'medical_conditions' => 'nullable|string',
        ]);

        $student->update($data);

        return redirect()->route('students.show', $student)->with('success', 'Student updated successfully.');
    }

    public function addGuardian(Request $request, Student $student)
    {
        $data = $request->validate([
            'first_name'         => 'required|string|max:100',
            'last_name'          => 'required|string|max:100',
            'email'              => 'nullable|email',
            'phone'              => 'required|string|max:20',
            'relationship'       => 'required|string|max:50',
            'address'            => 'nullable|string',
            'city'               => 'nullable|string|max:100',
            'country'            => 'nullable|string|max:100',
            'occupation'         => 'nullable|string|max:100',
            'is_primary_contact' => 'boolean',
        ]);

        $data['student_id'] = $student->id;

        // If this is primary contact, unset others
        if (!empty($data['is_primary_contact'])) {
            Guardian::where('student_id', $student->id)->update(['is_primary_contact' => false]);
        }

        Guardian::create($data);

        return back()->with('success', 'Guardian added successfully.');
    }
}
