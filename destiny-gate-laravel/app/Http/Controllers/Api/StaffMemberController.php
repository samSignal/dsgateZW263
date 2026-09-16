<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StaffMemberController extends Controller
{
    /* ── helpers ─────────────────────────────────────────────────────────── */

    private function withJoins()
    {
        return DB::table('staff_members as sm')
            ->leftJoin('departments', 'sm.department_id', '=', 'departments.id')
            ->leftJoin('users', 'sm.user_id', '=', 'users.id')
            ->leftJoin('teacher_profiles as tp', 'sm.id', '=', 'tp.staff_member_id')
            ->select(
                'sm.*',
                'departments.name as department_name',
                'users.email as user_email',
                'users.role as user_role',
                'tp.id as teacher_profile_id',
                'tp.teacher_code',
                'tp.specialization'
            );
    }

    private function generateStaffNumber(): string
    {
        $last = DB::table('staff_members')
            ->where('staff_number', 'like', 'DGI-STF-%')
            ->orderByDesc('id')
            ->value('staff_number');

        $next = $last ? (int) substr($last, 8) + 1 : 1;
        return 'DGI-STF-' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    /* ── index ───────────────────────────────────────────────────────────── */

    public function index(Request $request)
    {
        $query = $this->withJoins();

        if ($request->search) {
            $s = '%' . $request->search . '%';
            $query->where(function ($q) use ($s) {
                $q->where('sm.first_name',   'like', $s)
                  ->orWhere('sm.last_name',   'like', $s)
                  ->orWhere('sm.staff_number','like', $s)
                  ->orWhere('sm.email',       'like', $s);
            });
        }

        if ($request->department_id) {
            $query->where('sm.department_id', $request->department_id);
        }

        if ($request->status) {
            $query->where('sm.status', $request->status);
        }

        if ($request->role && $request->role !== 'all') {
            $query->where('users.role', $request->role);
        }

        $perPage = (int) ($request->per_page ?? 20);
        $page    = (int) ($request->page ?? 1);
        $total   = (clone $query)->count();
        $items   = $query->orderBy('sm.first_name')
                         ->offset(($page - 1) * $perPage)
                         ->limit($perPage)
                         ->get();

        return response()->json([
            'data'         => $items,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => (int) ceil($total / $perPage),
        ]);
    }

    /* ── store ───────────────────────────────────────────────────────────── */

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name'      => 'required|string|max:100',
            'last_name'       => 'required|string|max:100',
            'gender'          => 'required|in:male,female,other',
            'date_of_birth'   => 'nullable|date',
            'phone'           => 'required|string|max:20',
            'email'           => 'required|email|unique:staff_members,email',
            'address'         => 'nullable|string|max:500',
            'national_id'     => 'nullable|string|max:50',
            'department_id'   => 'required|exists:departments,id',
            'job_title'       => 'required|string|max:100',
            'employment_type' => 'required|in:full_time,part_time,contract',
            'employment_date' => 'required|date',
            // Optional user account
            'create_account'  => 'boolean',
            'role'            => 'nullable|in:admin,headmaster,teacher,bursar,storekeeper,clerk,parent,student,user',
        ]);

        DB::beginTransaction();
        try {
            $staffNumber = $this->generateStaffNumber();
            $userId      = null;

            // Create user account if requested
            if (!empty($data['create_account'])) {
                $defaultPassword = 'DGI@' . date('Y') . '!';
                $userId = DB::table('users')->insertGetId([
                    'name'       => "{$data['first_name']} {$data['last_name']}",
                    'email'      => $data['email'],
                    'password'   => Hash::make($defaultPassword),
                    'role'       => $data['role'] ?? 'user',
                    'is_active'  => true,
                    'must_change_password' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'last_signed_in' => now(),
                ]);
            }

            $id = DB::table('staff_members')->insertGetId([
                'user_id'         => $userId,
                'staff_number'    => $staffNumber,
                'first_name'      => $data['first_name'],
                'last_name'       => $data['last_name'],
                'gender'          => $data['gender'],
                'date_of_birth'   => $data['date_of_birth'] ?? null,
                'phone'           => $data['phone'],
                'email'           => $data['email'],
                'address'         => $data['address'] ?? null,
                'national_id'     => $data['national_id'] ?? null,
                'department_id'   => $data['department_id'],
                'job_title'       => $data['job_title'],
                'employment_type' => $data['employment_type'],
                'employment_date' => $data['employment_date'],
                'status'          => 'active',
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            DB::commit();

            $staff = $this->withJoins()->where('sm.id', $id)->first();
            return response()->json([
                'message'          => 'Staff member created.',
                'staff'            => $staff,
                'default_password' => !empty($data['create_account']) ? 'DGI@' . date('Y') . '!' : null,
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create staff: ' . $e->getMessage()], 500);
        }
    }

    /* ── show ────────────────────────────────────────────────────────────── */

    public function show(int $id)
    {
        $staff = $this->withJoins()->where('sm.id', $id)->first();
        abort_if(!$staff, 404, 'Staff member not found.');
        return response()->json($staff);
    }

    /* ── update ──────────────────────────────────────────────────────────── */

    public function update(Request $request, int $id)
    {
        $staff = DB::table('staff_members')->find($id);
        abort_if(!$staff, 404, 'Staff member not found.');

        $data = $request->validate([
            'first_name'      => 'required|string|max:100',
            'last_name'       => 'required|string|max:100',
            'gender'          => 'required|in:male,female,other',
            'date_of_birth'   => 'nullable|date',
            'phone'           => 'required|string|max:20',
            'email'           => 'required|email|unique:staff_members,email,' . $id,
            'address'         => 'nullable|string|max:500',
            'national_id'     => 'nullable|string|max:50',
            'department_id'   => 'required|exists:departments,id',
            'job_title'       => 'required|string|max:100',
            'employment_type' => 'required|in:full_time,part_time,contract',
            'employment_date' => 'required|date',
        ]);

        DB::table('staff_members')->where('id', $id)->update([
            ...$data,
            'updated_at' => now(),
        ]);

        // Sync user name/email if linked
        if ($staff->user_id) {
            DB::table('users')->where('id', $staff->user_id)->update([
                'name'       => "{$data['first_name']} {$data['last_name']}",
                'email'      => $data['email'],
                'updated_at' => now(),
            ]);
        }

        return response()->json($this->withJoins()->where('sm.id', $id)->first());
    }

    /* ── destroy ─────────────────────────────────────────────────────────── */

    public function destroy(int $id)
    {
        $staff = DB::table('staff_members')->find($id);
        abort_if(!$staff, 404, 'Staff member not found.');

        // Dependency checks
        $allocations = DB::table('teacher_allocations')->where('teacher_id', $id)->count();
        if ($allocations > 0) {
            return response()->json(['message' => 'Cannot delete: staff has teacher allocations.'], 422);
        }

        DB::beginTransaction();
        try {
            DB::table('teacher_profiles')->where('staff_member_id', $id)->delete();
            DB::table('staff_members')->where('id', $id)->delete();
            // Note: user account is kept for audit trail
            DB::commit();
            return response()->json(['message' => 'Staff member deleted.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to delete: ' . $e->getMessage()], 500);
        }
    }

    /* ── status actions ──────────────────────────────────────────────────── */

    public function activate(int $id)
    {
        $staff = DB::table('staff_members')->find($id);
        abort_if(!$staff, 404, 'Staff member not found.');

        DB::beginTransaction();
        try {
            DB::table('staff_members')->where('id', $id)->update(['status' => 'active', 'updated_at' => now()]);
            if ($staff->user_id) {
                DB::table('users')->where('id', $staff->user_id)->update(['is_active' => true, 'updated_at' => now()]);
            }
            DB::commit();
            return response()->json(['message' => 'Staff member activated.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function suspend(int $id)
    {
        $staff = DB::table('staff_members')->find($id);
        abort_if(!$staff, 404, 'Staff member not found.');

        DB::beginTransaction();
        try {
            DB::table('staff_members')->where('id', $id)->update(['status' => 'suspended', 'updated_at' => now()]);
            if ($staff->user_id) {
                DB::table('users')->where('id', $staff->user_id)->update(['is_active' => false, 'updated_at' => now()]);
            }
            DB::commit();
            return response()->json(['message' => 'Staff member suspended.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function setOnLeave(int $id)
    {
        $staff = DB::table('staff_members')->find($id);
        abort_if(!$staff, 404, 'Staff member not found.');
        DB::table('staff_members')->where('id', $id)->update(['status' => 'on_leave', 'updated_at' => now()]);
        return response()->json(['message' => 'Staff member set to on leave.']);
    }

    public function resign(int $id)
    {
        $staff = DB::table('staff_members')->find($id);
        abort_if(!$staff, 404, 'Staff member not found.');

        DB::beginTransaction();
        try {
            DB::table('staff_members')->where('id', $id)->update(['status' => 'resigned', 'updated_at' => now()]);
            if ($staff->user_id) {
                DB::table('users')->where('id', $staff->user_id)->update(['is_active' => false, 'updated_at' => now()]);
            }
            DB::commit();
            return response()->json(['message' => 'Staff member marked as resigned.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /* ── roles ───────────────────────────────────────────────────────────── */

    public function assignRoles(Request $request, int $id)
    {
        $staff = DB::table('staff_members')->find($id);
        abort_if(!$staff, 404, 'Staff member not found.');

        $data = $request->validate([
            'role'            => 'required|in:admin,headmaster,teacher,bursar,storekeeper,clerk,user',
            'create_account'  => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            if ($staff->user_id) {
                // Update existing user role
                DB::table('users')->where('id', $staff->user_id)->update([
                    'role'       => $data['role'],
                    'is_active'  => true,
                    'updated_at' => now(),
                ]);
            } elseif (!empty($data['create_account'])) {
                // Create new user account
                $defaultPassword = 'DGI@' . date('Y') . '!';
                $userId = DB::table('users')->insertGetId([
                    'name'           => DB::table('staff_members')->where('id', $id)->value(DB::raw("CONCAT(first_name, ' ', last_name)")),
                    'email'          => $staff->email,
                    'password'       => Hash::make($defaultPassword),
                    'role'           => $data['role'],
                    'is_active'      => true,
                    'must_change_password' => true,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                    'last_signed_in' => now(),
                ]);
                DB::table('staff_members')->where('id', $id)->update(['user_id' => $userId, 'updated_at' => now()]);
            }

            DB::commit();
            return response()->json(['message' => 'Role assigned successfully.', 'staff' => $this->withJoins()->where('sm.id', $id)->first()]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /* ── teacher profile ─────────────────────────────────────────────────── */

    public function createTeacherProfile(Request $request, int $id)
    {
        $staff = DB::table('staff_members')->find($id);
        abort_if(!$staff, 404, 'Staff member not found.');

        $exists = DB::table('teacher_profiles')->where('staff_member_id', $id)->exists();
        if ($exists) {
            return response()->json(['message' => 'Teacher profile already exists for this staff member.'], 422);
        }

        $data = $request->validate([
            'specialization' => 'nullable|string|max:150',
        ]);

        // Generate teacher code
        $last = DB::table('teacher_profiles')
            ->where('teacher_code', 'like', 'TCH-%')
            ->orderByDesc('id')
            ->value('teacher_code');
        $next = $last ? (int) substr($last, 4) + 1 : 1;
        $teacherCode = 'TCH-' . str_pad($next, 4, '0', STR_PAD_LEFT);

        $profileId = DB::table('teacher_profiles')->insertGetId([
            'staff_member_id' => $id,
            'teacher_code'    => $teacherCode,
            'specialization'  => $data['specialization'] ?? null,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        // Ensure user has teacher role
        if ($staff->user_id) {
            DB::table('users')->where('id', $staff->user_id)->update(['role' => 'teacher', 'updated_at' => now()]);
        }

        return response()->json([
            'message' => 'Teacher profile created.',
            'profile' => DB::table('teacher_profiles')->find($profileId),
        ], 201);
    }

    public function updateTeacherProfile(Request $request, int $id)
    {
        $profile = DB::table('teacher_profiles')->where('staff_member_id', $id)->first();
        abort_if(!$profile, 404, 'Teacher profile not found.');

        $data = $request->validate([
            'specialization' => 'nullable|string|max:150',
        ]);

        DB::table('teacher_profiles')->where('staff_member_id', $id)->update([
            'specialization' => $data['specialization'] ?? null,
            'updated_at'     => now(),
        ]);

        return response()->json(['message' => 'Teacher profile updated.', 'profile' => DB::table('teacher_profiles')->where('staff_member_id', $id)->first()]);
    }

    /* ── lookup helpers ──────────────────────────────────────────────────── */

    public function listForDropdown()
    {
        return response()->json(
            DB::table('staff_members')
                ->where('status', 'active')
                ->select('id', DB::raw("CONCAT(first_name, ' ', last_name) as name"), 'staff_number', 'job_title')
                ->orderBy('first_name')
                ->get()
        );
    }
}
