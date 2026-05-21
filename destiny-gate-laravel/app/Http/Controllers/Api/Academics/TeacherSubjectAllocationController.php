<?php

namespace App\Http\Controllers\Api\Academics;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TeacherSubjectAllocationController extends Controller
{
    private function query()
    {
        return DB::table('teacher_subject_allocations as tsa')
            ->join('staff as stf', 'tsa.teacher_id', '=', 'stf.id')
            ->join('subjects as sub', 'tsa.subject_id', '=', 'sub.id')
            ->join('streams as st', 'tsa.stream_id', '=', 'st.id')
            ->join('forms as f', 'st.form_id', '=', 'f.id')
            ->join('academic_years as ay', 'tsa.academic_year_id', '=', 'ay.id')
            ->join('terms as t', 'tsa.term_id', '=', 't.id')
            ->select('tsa.*', DB::raw("CONCAT(stf.first_name,' ',stf.last_name) as teacher_name"), 'stf.staff_id', 'sub.name as subject_name', 'sub.code as subject_code', 'f.name as form_name', 'st.name as stream_name', 'ay.name as academic_year_name', 't.name as term_name');
    }

    public function index(Request $request)
    {
        $q = $this->query()->orderBy('teacher_name')->orderBy('form_name')->orderBy('stream_name');
        if ($request->teacher_id) $q->where('tsa.teacher_id', $request->teacher_id);
        if ($request->stream_id) $q->where('tsa.stream_id', $request->stream_id);
        if ($request->academic_year_id) $q->where('tsa.academic_year_id', $request->academic_year_id);
        if ($request->term_id) $q->where('tsa.term_id', $request->term_id);
        if ($request->user()->role === 'teacher') {
            $staffId = DB::table('staff')->where('user_id', Auth::id())->value('id');
            $q->where('tsa.teacher_id', $staffId ?: 0);
        }
        return response()->json($q->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'teacher_id' => 'required|exists:staff,id',
            'subject_id' => 'required|exists:subjects,id',
            'stream_id' => 'required|exists:streams,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id' => 'required|exists:terms,id',
        ]);
        $teacher = DB::table('staff')->where('id', $data['teacher_id'])->first();
        if (!$teacher || !$teacher->is_active || (!str_contains(strtolower((string)$teacher->roles), 'teacher') && !str_contains(strtolower((string)$teacher->position), 'teacher'))) {
            return response()->json(['message' => 'Only active teachers can be allocated.'], 422);
        }
        if (DB::table('teacher_subject_allocations')->where($data)->exists()) return response()->json(['message' => 'Duplicate teacher allocation blocked.'], 422);
        $id = DB::table('teacher_subject_allocations')->insertGetId([...$data, 'created_by' => Auth::id(), 'created_at' => now(), 'updated_at' => now()]);
        return response()->json($this->query()->where('tsa.id', $id)->first(), 201);
    }

    public function update(Request $request, int $id)
    {
        abort_if(!DB::table('teacher_subject_allocations')->where('id', $id)->exists(), 404, 'Allocation not found.');
        $data = $request->validate(['teacher_id' => 'required|exists:staff,id', 'subject_id' => 'required|exists:subjects,id', 'stream_id' => 'required|exists:streams,id', 'academic_year_id' => 'required|exists:academic_years,id', 'term_id' => 'required|exists:terms,id']);
        $exists = DB::table('teacher_subject_allocations')->where($data)->where('id', '!=', $id)->exists();
        if ($exists) return response()->json(['message' => 'Duplicate teacher allocation blocked.'], 422);
        DB::table('teacher_subject_allocations')->where('id', $id)->update([...$data, 'updated_at' => now()]);
        return response()->json($this->query()->where('tsa.id', $id)->first());
    }

    public function destroy(int $id)
    {
        DB::table('teacher_subject_allocations')->where('id', $id)->delete();
        return response()->json(['message' => 'Teacher allocation removed.']);
    }

    public function teacherAllocations(int $teacherId) { return response()->json($this->query()->where('tsa.teacher_id', $teacherId)->get()); }
    public function streamAllocations(int $streamId) { return response()->json($this->query()->where('tsa.stream_id', $streamId)->get()); }

    public function teachers()
    {
        return response()->json(DB::table('staff')->where('is_active', true)->where(function ($q) {
            $q->where('position', 'like', '%teacher%')->orWhere('roles', 'like', '%teacher%');
        })->select('id', DB::raw("CONCAT(first_name,' ',last_name) as name"), 'staff_id')->orderBy('first_name')->get());
    }
}
