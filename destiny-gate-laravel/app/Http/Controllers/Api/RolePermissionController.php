<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionController extends Controller
{
    /* ── Permissions ─────────────────────────────────────────────────────── */

    public function permissions()
    {
        $permissions = Permission::where('guard_name', 'web')
            ->orderBy('name')
            ->get()
            ->groupBy(function ($p) {
                $name = (string) $p->name;
                if (str_contains($name, '.')) {
                    return explode('.', $name)[0];
                }
                if (str_contains($name, '-')) {
                    return explode('-', $name)[0];
                }
                return $name;
            });

        return response()->json($permissions);
    }

    /* ── Roles ───────────────────────────────────────────────────────────── */

    public function roles()
    {
        $roles = Role::where('guard_name', 'web')
            ->orderBy('name')
            ->get()
            ->map(function ($role) {
                return [
                    'id'          => $role->id,
                    'name'        => $role->name,
                    'permissions' => $role->permissions->pluck('name'),
                    'users_count' => DB::table('model_has_roles')
                        ->where('role_id', $role->id)
                        ->where('model_type', 'App\\Models\\User')
                        ->count(),
                ];
            });

        return response()->json($roles);
    }

    public function showRole(int $id)
    {
        $role = Role::where('guard_name', 'web')->findOrFail($id);
        return response()->json([
            'id'          => $role->id,
            'name'        => $role->name,
            'permissions' => $role->permissions->pluck('name'),
            'users_count' => DB::table('model_has_roles')
                ->where('role_id', $role->id)
                ->where('model_type', 'App\\Models\\User')
                ->count(),
        ]);
    }

    public function storeRole(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100|unique:roles,name',
            'permissions' => 'array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        DB::beginTransaction();
        try {
            $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);

            if (!empty($data['permissions'])) {
                $role->syncPermissions($data['permissions']);
            }

            app()[PermissionRegistrar::class]->forgetCachedPermissions();
            DB::commit();

            return response()->json([
                'message' => 'Role created successfully.',
                'role'    => ['id' => $role->id, 'name' => $role->name, 'permissions' => $role->permissions->pluck('name')],
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    public function updateRole(Request $request, int $id)
    {
        $role = Role::where('guard_name', 'web')->findOrFail($id);

        $data = $request->validate([
            'name'          => 'required|string|max:100|unique:roles,name,' . $id,
            'permissions'   => 'array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        DB::beginTransaction();
        try {
            $role->name = $data['name'];
            $role->save();
            $role->syncPermissions($data['permissions'] ?? []);

            app()[PermissionRegistrar::class]->forgetCachedPermissions();
            DB::commit();

            return response()->json([
                'message' => 'Role updated.',
                'role'    => ['id' => $role->id, 'name' => $role->name, 'permissions' => $role->permissions->pluck('name')],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    public function destroyRole(int $id)
    {
        $role = Role::where('guard_name', 'web')->findOrFail($id);

        // Prevent deleting core roles
        $protected = ['admin', 'headmaster', 'teacher', 'bursar', 'parent', 'student'];
        if (in_array($role->name, $protected)) {
            return response()->json(['message' => "Cannot delete the built-in '{$role->name}' role."], 422);
        }

        $usersCount = DB::table('model_has_roles')
            ->where('role_id', $id)
            ->where('model_type', 'App\\Models\\User')
            ->count();

        if ($usersCount > 0) {
            return response()->json(['message' => "Cannot delete: {$usersCount} user(s) have this role."], 422);
        }

        $role->delete();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return response()->json(['message' => 'Role deleted.']);
    }

    /* ── Assign role to user ─────────────────────────────────────────────── */

    public function assignUserRole(Request $request, int $userId)
    {
        $data = $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);

        $user = \App\Models\User::findOrFail($userId);
        $user->syncRoles([$data['role']]);

        // Also update the users.role column for backward compat
        DB::table('users')->where('id', $userId)->update(['role' => $data['role'], 'updated_at' => now()]);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return response()->json(['message' => "Role '{$data['role']}' assigned to {$user->name}."]);
    }

    /* ── Check permission ────────────────────────────────────────────────── */

    public function myPermissions(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'roles'       => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }
}
