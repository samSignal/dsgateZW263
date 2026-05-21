<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = DB::table('departments')
            ->select(
                'departments.*',
                DB::raw('(SELECT COUNT(*) FROM staff_members WHERE staff_members.department_id = departments.id) as staff_count')
            )
            ->orderBy('name')
            ->get();

        return response()->json($departments);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100|unique:departments,name',
            'description' => 'nullable|string|max:500',
        ]);

        $id = DB::table('departments')->insertGetId([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        return response()->json(DB::table('departments')->find($id), 201);
    }

    public function update(Request $request, int $id)
    {
        $dept = DB::table('departments')->find($id);
        abort_if(!$dept, 404, 'Department not found.');

        $data = $request->validate([
            'name'        => 'required|string|max:100|unique:departments,name,' . $id,
            'description' => 'nullable|string|max:500',
        ]);

        DB::table('departments')->where('id', $id)->update([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'updated_at'  => now(),
        ]);

        return response()->json(DB::table('departments')->find($id));
    }

    public function destroy(int $id)
    {
        $dept = DB::table('departments')->find($id);
        abort_if(!$dept, 404, 'Department not found.');

        $staffCount = DB::table('staff_members')->where('department_id', $id)->count();
        if ($staffCount > 0) {
            return response()->json([
                'message' => "Cannot delete: {$staffCount} staff member(s) are assigned to this department.",
            ], 422);
        }

        DB::table('departments')->where('id', $id)->delete();
        return response()->json(['message' => 'Department deleted.']);
    }
}
