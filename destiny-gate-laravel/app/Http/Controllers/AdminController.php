<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Staff;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\Application;
use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'total_users'       => User::count(),
            'total_students'    => Student::where('status', 'active')->count(),
            'total_staff'       => Staff::where('is_active', true)->count(),
            'total_classes'     => SchoolClass::count(),
            'pending_apps'      => Application::where('status', 'pending')->count(),
            'recent_users'      => User::latest()->take(5)->get(),
        ];

        return view('admin.dashboard', compact('stats'));
    }

    // ---- Users ----
    public function users()
    {
        $users = User::latest()->paginate(20);
        return view('admin.users.index', compact('users'));
    }

    public function updateUserRole(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|in:admin,headmaster,teacher,bursar,parent,student,user',
        ]);

        $user->update(['role' => $request->role]);

        return back()->with('success', "Role updated to {$request->role} for {$user->name}.");
    }

    // ---- Staff ----
    public function staff()
    {
        $staff = Staff::with('user')->where('is_active', true)->paginate(20);
        return view('admin.staff.index', compact('staff'));
    }

    public function createStaff()
    {
        return view('admin.staff.create');
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

        Staff::create([
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

        return redirect()->route('admin.staff')->with('success', 'Staff member created successfully.');
    }

    // ---- Classes ----
    public function classes()
    {
        $classes = SchoolClass::with('classTeacher')->paginate(20);
        return view('admin.classes.index', compact('classes'));
    }

    public function createClass()
    {
        $staff = Staff::where('is_active', true)->get();
        return view('admin.classes.create', compact('staff'));
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

        SchoolClass::create($data);

        return redirect()->route('admin.classes')->with('success', 'Class created successfully.');
    }

    // ---- Applications ----
    public function applications()
    {
        $applications = Application::latest()->paginate(20);
        return view('admin.applications.index', compact('applications'));
    }

    public function approveApplication(Request $request, Application $application)
    {
        $application->update([
            'status'       => 'approved',
            'processed_by' => auth()->id(),
            'processed_at' => now(),
        ]);

        return back()->with('success', 'Application approved.');
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

        return back()->with('success', 'Application rejected.');
    }
}
