<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeacherAllocationController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('teacher_allocations as ta')
            ->join('staff',          'ta.teacher_id',       '=', 'staff.id')
            ->join('subjects',       'ta.subject_id',       '=', 'subjects.id')
            ->join('streams',        'ta.stream_id',        '=', 'streams.id')
            ->join('forms',          'streams.form_id',     '=', 'forms.id')
            ->join('academic_years', 'ta.academic_year_id', '=', 'academic_years.id')
            ->join('terms',          'ta.term_id',          '=', 'terms.id')
            ->select(
                'ta.id',
                'ta.teacher_id', 'ta.subject_id', 'ta.stream_id',
                'ta.academic_year_id', 'ta.term_id',
                'ta.created_at',
                DB::raw("staff.first_name || ' ' || staff.last_name as teacher_name"),
                'subjects.name as subject_name',
                'subjects.code as subject_code',
                'streams.name as stream_name',
                'forms.name as form_name',
                'academic_years.name as academic_year_name',
                'terms.name as term_name'
            )
            ->orderBy('academic_years.name')
            ->orderBy('terms.name')
            ->orderBy('forms.level')
            ->orderBy('streams.name');

        if ($request->teacher_id)       $query->where('ta.teacher_id',       $request->teacher_id);
        if ($request->stream_id)        $query->where('ta.stream_id',        $request->stream_id);
        if ($request->academic_year_id) $query->where('ta.academic_year_id', $request->academic_year_id);
        if ($request->term_id)          $query->where('ta.term_id',          $request->term_id);

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'teacher_id'       => 'required|exists:staff,id',
            'subject_id'       => 'required|exists:subjects,id',
            'stream_id'        => 'required|exists:streams,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id'          => 'required|exists:terms,id',
        ]);

        // Prevent duplicate allocation
        $exists = DB::table('teacher_allocations')
            ->where('teacher_id',       $data['teacher_id'])
            ->where('subject_id',       $data['subject_id'])
            ->where('stream_id',        $data['stream_id'])
            ->where('academic_year_id', $data['academic_year_id'])
            ->where('term_id',          $data['term_id'])
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'This allocation already exists.'], 422);
        }

        $id = DB::table('teacher_allocations')->insertGetId([
            'teacher_id'       => $data['teacher_id'],
            'subject_id'       => $data['subject_id'],
            'stream_id'        => $data['stream_id'],
            'academic_year_id' => $data['academic_year_id'],
            'term_id'          => $data['term_id'],
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        return response()->json(
            DB::table('teacher_allocations as ta')
                ->join('staff',          'ta.teacher_id',       '=', 'staff.id')
                ->join('subjects',       'ta.subject_id',       '=', 'subjects.id')
                ->join('streams',        'ta.stream_id',        '=', 'streams.id')
                ->join('forms',          'streams.form_id',     '=', 'forms.id')
                ->join('academic_years', 'ta.academic_year_id', '=', 'academic_years.id')
                ->join('terms',          'ta.term_id',          '=', 'terms.id')
                ->select(
                    'ta.id',
                    DB::raw("staff.first_name || ' ' || staff.last_name as teacher_name"),
                    'subjects.name as subject_name',
                    'streams.name as stream_name',
                    'forms.name as form_name',
                    'academic_years.name as academic_year_name',
                    'terms.name as term_name'
                )
                ->where('ta.id', $id)
                ->first(),
            201
        );
    }

    public function destroy(int $id)
    {
        $allocation = DB::table('teacher_allocations')->find($id);
        abort_if(!$allocation, 404, 'Allocation not found.');

        DB::table('teacher_allocations')->where('id', $id)->delete();
        return response()->json(['message' => 'Allocation removed.']);
    }

    // Helper endpoints for dropdowns
    public function teachers()
    {
        return response()->json(
            DB::table('staff')
                ->where('is_active', true)
                ->select('id', DB::raw("first_name || ' ' || last_name as name"), 'staff_id')
                ->orderBy('first_name')
                ->get()
        );
    }
}
