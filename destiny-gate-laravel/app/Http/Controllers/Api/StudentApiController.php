<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\StudentNumberGenerator;
use App\Support\StudentStreamResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StudentApiController extends Controller
{
    /* ── index ────────────────────────────────────────────────────────────*/
    public function index(Request $request)
    {
        $query = DB::table('students')
            ->select('students.*');

        if ($request->search) {
            $s = '%' . $request->search . '%';
            $query->where(function ($q) use ($s) {
                $q->where('students.first_name',     'like', $s)
                  ->orWhere('students.last_name',     'like', $s)
                  ->orWhere('students.student_number','like', $s)
                  ->orWhere('students.admission_number','like', $s);
            });
        }

        if ($request->status)   $query->where('students.status',   $request->status);
        if ($request->class_id) $query->where('students.class_id', $request->class_id);

        $perPage = (int)($request->per_page ?? 20);
        $page    = (int)($request->page ?? 1);
        $total   = (clone $query)->count();
        $items   = $query->orderBy('students.first_name')
                         ->offset(($page - 1) * $perPage)
                         ->limit($perPage)
                         ->get();
        $items = StudentStreamResolver::attachResolvedFieldsToCollection($items);

        return response()->json([
            'data'         => $items,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => (int) ceil($total / $perPage),
        ]);
    }

    /* ── store ────────────────────────────────────────────────────────────*/
    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name'         => 'required|string|max:100',
            'last_name'          => 'required|string|max:100',
            'email'              => 'nullable|email|unique:students,email',
            'date_of_birth'      => 'nullable|date',
            'gender'             => 'nullable|in:male,female,other',
            'stream_id'          => 'nullable|exists:streams,id',
            'admission_date'     => 'required|date',
            'national_id'        => 'nullable|string|max:50',
            'blood_type'         => 'nullable|string|max:10',
            'allergies'          => 'nullable|string',
            'medical_conditions' => 'nullable|string',
        ]);

        // Class is authoritative — never trust client-sent form/category, derive from the class record.
        $stream = !empty($data['stream_id']) ? DB::table('streams')->find($data['stream_id']) : null;

        DB::beginTransaction();
        try {
            // Generate student number
            $studentNumber   = StudentNumberGenerator::generate($data['admission_date']);
            $admissionNumber = $studentNumber; // use same as admission number

            // Create user account automatically
            $defaultPassword = $data['national_id'] ?? 'DGI@' . date('Y') . '!';
            $userId = DB::table('users')->insertGetId([
                'name'                 => "{$data['first_name']} {$data['last_name']}",
                'email'                => $data['email'] ?? null,
                'username'             => $studentNumber,   // login with student number
                'password'             => Hash::make($defaultPassword),
                'role'                 => 'student',
                'is_active'            => true,
                'must_change_password' => true,
                'created_at'           => now(),
                'updated_at'           => now(),
                'last_signed_in'       => now(),
            ]);

            $studentId = DB::table('students')->insertGetId([
                'user_id'            => $userId,
                'admission_number'   => $admissionNumber,
                'student_number'     => $studentNumber,
                'national_id'        => $data['national_id'] ?? null,
                'first_name'         => $data['first_name'],
                'last_name'          => $data['last_name'],
                'email'              => $data['email'] ?? null,
                'date_of_birth'      => $data['date_of_birth'] ?? null,
                'gender'             => $data['gender'] ?? null,
                'stream_id'          => $stream->id ?? null,
                'form_id'            => $stream->form_id ?? null,
                'category_id'        => $stream->category_id ?? null,
                'admission_date'     => $data['admission_date'],
                'status'             => 'active',
                'blood_type'         => $data['blood_type'] ?? null,
                'allergies'          => $data['allergies'] ?? null,
                'medical_conditions' => $data['medical_conditions'] ?? null,
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);

            DB::commit();

            $student = DB::table('students')
                ->select('students.*')
                ->where('students.id', $studentId)
                ->first();
            $student = StudentStreamResolver::attachResolvedFields($student);

            return response()->json([
                'message'        => 'Student enrolled successfully.',
                'student'        => $student,
                'student_number' => $studentNumber,
                'login_info'     => [
                    'username' => $studentNumber,
                    'password' => 'National ID (or DGI@' . date('Y') . '! if no National ID provided)',
                ],
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to enroll student: ' . $e->getMessage()], 500);
        }
    }

    /* ── show ─────────────────────────────────────────────────────────────*/
    public function show(int $id)
    {
        $student = DB::table('students')
            ->select('students.*')
            ->where('students.id', $id)
            ->first();

        abort_if(!$student, 404, 'Student not found.');
        $student = StudentStreamResolver::attachResolvedFields($student);

        $guardians = DB::table('guardians')->where('student_id', $id)->get();
        $fees      = DB::table('student_fees')->where('student_id', $id)->get();
        $progress  = DB::table('academic_progress')
            ->join('subjects', 'academic_progress.subject_id', '=', 'subjects.id')
            ->select('academic_progress.*', 'subjects.name as subject_name', 'subjects.code as subject_code')
            ->where('academic_progress.student_id', $id)
            ->get();
        $attendance = DB::table('attendance')->where('student_id', $id)->orderByDesc('date')->limit(30)->get();
        $behaviour  = DB::table('behaviour_records')->where('student_id', $id)->orderByDesc('issue_date')->get();

        $applicationDocuments = null;
        if ($student->application_id) {
            $applicationDocuments = DB::table('applications')
                ->select('application_number', 'doc_student_id_path', 'doc_results_path', 'doc_parent_id_path', 'doc_transfer_letter_path')
                ->where('id', $student->application_id)
                ->first();
        }

        return response()->json([
            ...(array) $student,
            'guardians'             => $guardians,
            'fees'                  => $fees,
            'academic_progress'     => $progress,
            'attendance'            => $attendance,
            'behaviour_records'     => $behaviour,
            'application_documents' => $applicationDocuments,
        ]);
    }

    /* ── update ───────────────────────────────────────────────────────────*/
    public function update(Request $request, int $id)
    {
        $student = DB::table('students')->find($id);
        abort_if(!$student, 404, 'Student not found.');

        $data = $request->validate([
            'first_name'         => 'required|string|max:100',
            'last_name'          => 'required|string|max:100',
            'email'              => 'nullable|email|unique:students,email,' . $id,
            'date_of_birth'      => 'nullable|date',
            'gender'             => 'nullable|in:male,female,other',
            'stream_id'          => 'nullable|exists:streams,id',
            'status'             => 'required|in:active,inactive,transferred,graduated,suspended,deceased',
            'blood_type'         => 'nullable|string|max:10',
            'allergies'          => 'nullable|string',
            'medical_conditions' => 'nullable|string',
        ]);

        // Only touch class assignment when the caller actually sent stream_id — class is
        // authoritative for form/category, never trust client-sent values for those.
        if ($request->has('stream_id')) {
            $stream = !empty($data['stream_id']) ? DB::table('streams')->find($data['stream_id']) : null;
            $data['form_id']     = $stream->form_id ?? null;
            $data['category_id'] = $stream->category_id ?? null;
        }

        DB::table('students')->where('id', $id)->update([
            ...$data,
            'updated_at' => now(),
        ]);

        // Sync user name
        if ($student->user_id) {
            DB::table('users')->where('id', $student->user_id)->update([
                'name'       => "{$data['first_name']} {$data['last_name']}",
                'updated_at' => now(),
            ]);
        }

        return response()->json([
            'message' => 'Student updated.',
            'student' => StudentStreamResolver::attachResolvedFields(DB::table('students')->where('id', $id)->first()),
        ]);
    }

    /* ── addGuardian ──────────────────────────────────────────────────────*/
    public function addGuardian(Request $request, int $studentId)
    {
        $student = DB::table('students')->find($studentId);
        abort_if(!$student, 404, 'Student not found.');

        $data = $request->validate([
            'first_name'         => 'required|string|max:100',
            'last_name'          => 'required|string|max:100',
            'email'              => 'nullable|email',
            'phone'              => 'required|string|max:20',
            'national_id'        => 'nullable|string|max:50',
            'relationship'       => 'required|string|max:50',
            'address'            => 'nullable|string',
            'city'               => 'nullable|string|max:100',
            'country'            => 'nullable|string|max:100',
            'occupation'         => 'nullable|string|max:100',
            'is_primary_contact' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            // If primary contact, unset others
            if (!empty($data['is_primary_contact'])) {
                DB::table('guardians')->where('student_id', $studentId)->update(['is_primary_contact' => false]);
            }

            // Create user account for guardian using phone as username
            $defaultPassword = $data['national_id'] ?? 'DGI@' . date('Y') . '!';

            // Check if user with this phone/username already exists
            $existingUser = DB::table('users')->where('username', $data['phone'])->first();
            $userId = null;

            if ($existingUser) {
                $userId = $existingUser->id;
            } else {
                $userId = DB::table('users')->insertGetId([
                    'name'                 => "{$data['first_name']} {$data['last_name']}",
                    'email'                => $data['email'] ?? null,
                    'username'             => $data['phone'],   // login with phone number
                    'phone'                => $data['phone'],
                    'password'             => Hash::make($defaultPassword),
                    'role'                 => 'parent',
                    'is_active'            => true,
                    'must_change_password' => true,
                    'created_at'           => now(),
                    'updated_at'           => now(),
                    'last_signed_in'       => now(),
                ]);
            }

            $guardianId = DB::table('guardians')->insertGetId([
                'user_id'            => $userId,
                'student_id'         => $studentId,
                'first_name'         => $data['first_name'],
                'last_name'          => $data['last_name'],
                'email'              => $data['email'] ?? null,
                'phone'              => $data['phone'],
                'national_id'        => $data['national_id'] ?? null,
                'relationship'       => $data['relationship'],
                'address'            => $data['address'] ?? null,
                'city'               => $data['city'] ?? null,
                'country'            => $data['country'] ?? null,
                'occupation'         => $data['occupation'] ?? null,
                'is_primary_contact' => $data['is_primary_contact'] ?? false,
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);

            DB::commit();

            return response()->json([
                'message'    => 'Guardian added successfully.',
                'guardian'   => DB::table('guardians')->find($guardianId),
                'login_info' => [
                    'username' => $data['phone'],
                    'password' => 'National ID (or DGI@' . date('Y') . '! if no National ID provided)',
                ],
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to add guardian: ' . $e->getMessage()], 500);
        }
    }

    /* ── helpers ──────────────────────────────────────────────────────────*/
    public function classes()
    {
        return response()->json(
            DB::table('classes')->orderBy('class_name')->get()
        );
    }

    public function subjects()
    {
        return response()->json(
            DB::table('subjects')->orderBy('name')->get()
        );
    }
}
