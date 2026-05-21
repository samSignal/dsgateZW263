<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubjectGroupController extends Controller
{
    public function index()
    {
        return response()->json(DB::table('subject_groups')->orderBy('name')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100|unique:subject_groups,name',
            'description' => 'nullable|string|max:255',
        ]);

        $id = DB::table('subject_groups')->insertGetId([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        return response()->json(DB::table('subject_groups')->find($id), 201);
    }

    public function update(Request $request, int $id)
    {
        $group = DB::table('subject_groups')->find($id);
        abort_if(!$group, 404, 'Subject group not found.');

        $data = $request->validate([
            'name'        => 'required|string|max:100|unique:subject_groups,name,' . $id,
            'description' => 'nullable|string|max:255',
        ]);

        DB::table('subject_groups')->where('id', $id)->update([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'updated_at'  => now(),
        ]);

        return response()->json(DB::table('subject_groups')->find($id));
    }

    public function destroy(int $id)
    {
        $group = DB::table('subject_groups')->find($id);
        abort_if(!$group, 404, 'Subject group not found.');

        $hasSubjects = DB::table('subjects')->where('subject_group_id', $id)->exists();
        if ($hasSubjects) {
            return response()->json(['message' => 'Cannot delete: group has subjects linked to it.'], 422);
        }

        DB::table('subject_groups')->where('id', $id)->delete();
        return response()->json(['message' => 'Subject group deleted.']);
    }
}
