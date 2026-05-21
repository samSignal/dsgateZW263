<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubjectController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('subjects')
            ->leftJoin('subject_groups', 'subjects.subject_group_id', '=', 'subject_groups.id')
            ->select('subjects.*', 'subject_groups.name as group_name')
            ->orderBy('subjects.name');

        if ($request->subject_group_id) {
            $query->where('subjects.subject_group_id', $request->subject_group_id);
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('subjects.name', 'like', '%' . $request->search . '%')
                  ->orWhere('subjects.code', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->is_compulsory !== null) {
            $query->where('subjects.is_compulsory', (bool) $request->is_compulsory);
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'             => 'required|string|max:100',
            'code'             => 'required|string|max:20|unique:subjects,code',
            'subject_group_id' => 'nullable|exists:subject_groups,id',
            'pass_mark'        => 'nullable|integer|min:0|max:100',
            'is_compulsory'    => 'boolean',
        ]);

        $id = DB::table('subjects')->insertGetId([
            'name'             => $data['name'],
            'code'             => strtoupper($data['code']),
            'subject_group_id' => $data['subject_group_id'] ?? null,
            'pass_mark'        => $data['pass_mark'] ?? 50,
            'is_compulsory'    => $data['is_compulsory'] ?? false,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        return response()->json(DB::table('subjects')->find($id), 201);
    }

    public function update(Request $request, int $id)
    {
        $subject = DB::table('subjects')->find($id);
        abort_if(!$subject, 404, 'Subject not found.');

        $data = $request->validate([
            'name'             => 'required|string|max:100',
            'code'             => 'required|string|max:20|unique:subjects,code,' . $id,
            'subject_group_id' => 'nullable|exists:subject_groups,id',
            'pass_mark'        => 'nullable|integer|min:0|max:100',
            'is_compulsory'    => 'boolean',
        ]);

        DB::table('subjects')->where('id', $id)->update([
            'name'             => $data['name'],
            'code'             => strtoupper($data['code']),
            'subject_group_id' => $data['subject_group_id'] ?? null,
            'pass_mark'        => $data['pass_mark'] ?? $subject->pass_mark,
            'is_compulsory'    => $data['is_compulsory'] ?? $subject->is_compulsory,
            'updated_at'       => now(),
        ]);

        return response()->json(DB::table('subjects')->find($id));
    }

    public function destroy(int $id)
    {
        $subject = DB::table('subjects')->find($id);
        abort_if(!$subject, 404, 'Subject not found.');

        $used = DB::table('teacher_allocations')->where('subject_id', $id)->exists();
        if ($used) {
            return response()->json(['message' => 'Cannot delete: subject has teacher allocations.'], 422);
        }

        DB::table('subjects')->where('id', $id)->delete();
        return response()->json(['message' => 'Subject deleted.']);
    }
}
