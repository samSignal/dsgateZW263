<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Staff;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminApiController extends Controller
{
    public function dashboard()
    {
        return response()->json([
            'total_users'    => User::count(),
            'total_students' => Student::where('status', 'active')->count(),
            'total_staff'    => Staff::where('is_active', true)->count(),
            'total_classes'  => SchoolClass::count(),
            'pending_apps'   => Application::where('status', 'pending')->where('is_draft', false)->count(),
            'recent_users'   => User::latest()->take(5)->get(['id','name','email','role','created_at']),
        ]);
    }

    public function users(Request $request)
    {
        $users = User::latest()->paginate(20);
        return response()->json($users);
    }

    public function updateUserRole(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|in:admin,headmaster,teacher,bursar,parent,student,user',
        ]);
        $user->update(['role' => $request->role]);
        return response()->json(['message' => "Role updated to {$request->role}.", 'user' => $user]);
    }

    public function staff()
    {
        $staff = Staff::with('user')->where('is_active', true)->paginate(20);
        return response()->json($staff);
    }

    public function storeStaff(Request $request)
    {
        $data = $request->validate([
            'first_name'      => 'required|string|max:100',
            'last_name'       => 'required|string|max:100',
            'email'           => 'required|email|unique:users,email',
            'phone'           => 'nullable|string|max:20',
            'department'      => 'nullable|string|max:100',
            'position'        => 'nullable|string|max:100',
            'qualifications'  => 'nullable|string',
            'employment_date' => 'nullable|date',
            'role'            => 'required|in:teacher,bursar,headmaster,admin',
        ]);

        $user = User::create([
            'name'     => "{$data['first_name']} {$data['last_name']}",
            'email'    => $data['email'],
            'phone'    => $data['phone'] ?? null,
            'role'     => $data['role'],
            'password' => Hash::make('password123'),
        ]);

        $staffId = 'STF-' . str_pad(Staff::count() + 1, 4, '0', STR_PAD_LEFT);

        $staff = Staff::create([
            'user_id'         => $user->id,
            'staff_id'        => $staffId,
            'first_name'      => $data['first_name'],
            'last_name'       => $data['last_name'],
            'email'           => $data['email'],
            'phone'           => $data['phone'] ?? null,
            'department'      => $data['department'] ?? null,
            'position'        => $data['position'] ?? null,
            'qualifications'  => $data['qualifications'] ?? null,
            'employment_date' => $data['employment_date'] ?? null,
            'roles'           => [$data['role']],
        ]);

        return response()->json(['message' => 'Staff created.', 'staff' => $staff], 201);
    }

    public function classes()
    {
        $classes = SchoolClass::with('classTeacher')->paginate(20);
        return response()->json($classes);
    }

    public function storeClass(Request $request)
    {
        $data = $request->validate([
            'class_name'       => 'required|string|max:100',
            'stream'           => 'nullable|string|max:50',
            'class_teacher_id' => 'nullable|exists:staff,id',
            'academic_year'    => 'required|string|max:20',
            'capacity'         => 'nullable|integer|min:1',
        ]);
        $class = SchoolClass::create($data);
        return response()->json(['message' => 'Class created.', 'class' => $class], 201);
    }

    public function applications()
    {
        $apps = Application::where('is_draft', false)->latest()->paginate(20);
        return response()->json($apps);
    }

    public function approveApplication(Request $request, Application $application)
    {
        $application->update([
            'status'       => 'approved',
            'processed_by' => auth()->id(),
            'processed_at' => now(),
        ]);
        return response()->json(['message' => 'Application approved.', 'application' => $application]);
    }

    public function rejectApplication(Request $request, Application $application)
    {
        $request->validate(['rejection_reason' => 'required|string']);
        $application->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->rejection_reason,
            'processed_by'     => auth()->id(),
            'processed_at'     => now(),
        ]);
        return response()->json(['message' => 'Application rejected.', 'application' => $application]);
    }
}
