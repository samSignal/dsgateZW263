<?php

namespace App\Http\Controllers\Api\Academics;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AcademicSubjectReportController extends Controller
{
    public function meta()
    {
        return response()->json([
            'academic_years' => DB::table('academic_years')->orderByDesc('is_active')->orderByDesc('id')->get(),
            'terms' => DB::table('terms')->orderByDesc('is_current')->orderBy('name')->get(),
            'forms' => DB::table('forms')->orderBy('level')->get(),
            'streams' => DB::table('streams')->orderBy('name')->get(),
            'subjects' => DB::table('subjects')->where(function ($q) { $q->where('is_active', true)->orWhereNull('is_active'); })->orderBy('name')->get(),
            'teachers' => DB::table('staff')->where('is_active', true)->where(function ($q) { $q->where('position', 'like', '%teacher%')->orWhere('roles', 'like', '%teacher%'); })->select('id', DB::raw("CONCAT(first_name,' ',last_name) as name"), 'staff_id')->orderBy('first_name')->get(),
        ]);
    }

    public function studentsPerSubject(Request $request)
    {
        $q = DB::table('student_subjects as ss')->join('students as s', 'ss.student_id', '=', 's.id')->join('subjects as sub', 'ss.subject_id', '=', 'sub.id')->select('sub.id as subject_id', 'sub.name as subject_name', 'sub.code', DB::raw('COUNT(*) as student_count'))->where('ss.enrollment_status', 'active')->groupBy('sub.id', 'sub.name', 'sub.code')->orderBy('sub.name');
        if ($request->academic_year_id) $q->where('ss.academic_year_id', $request->academic_year_id);
        if ($request->term_id) $q->where('ss.term_id', $request->term_id);
        return response()->json($q->get());
    }

    public function subjectsPerStudent(Request $request)
    {
        $q = DB::table('student_subjects as ss')->join('students as s', 'ss.student_id', '=', 's.id')->join('subjects as sub', 'ss.subject_id', '=', 'sub.id')->select('s.id as student_id', DB::raw("CONCAT(s.first_name,' ',s.last_name) as student_name"), 's.student_number', DB::raw('COUNT(*) as subject_count'), DB::raw("GROUP_CONCAT(sub.name ORDER BY sub.name SEPARATOR ', ') as subjects"))->where('ss.enrollment_status', 'active')->groupBy('s.id', 's.first_name', 's.last_name', 's.student_number')->orderBy('student_name');
        if ($request->academic_year_id) $q->where('ss.academic_year_id', $request->academic_year_id);
        if ($request->term_id) $q->where('ss.term_id', $request->term_id);
        return response()->json($q->get());
    }

    public function teacherAllocations()
    {
        return response()->json(DB::table('teacher_subject_allocations as tsa')->join('staff as stf', 'tsa.teacher_id', '=', 'stf.id')->join('subjects as sub', 'tsa.subject_id', '=', 'sub.id')->join('streams as st', 'tsa.stream_id', '=', 'st.id')->join('forms as f', 'st.form_id', '=', 'f.id')->select(DB::raw("CONCAT(stf.first_name,' ',stf.last_name) as teacher_name"), 'sub.name as subject_name', 'f.name as form_name', 'st.name as stream_name', DB::raw('COUNT(*) as allocation_count'))->groupBy('stf.first_name', 'stf.last_name', 'sub.name', 'f.name', 'st.name')->orderBy('teacher_name')->get());
    }

    public function streamSubjects()
    {
        return response()->json(DB::table('stream_subjects as ss')->join('forms as f', 'ss.form_id', '=', 'f.id')->join('streams as st', 'ss.stream_id', '=', 'st.id')->join('subjects as sub', 'ss.subject_id', '=', 'sub.id')->select('f.name as form_name', 'st.name as stream_name', 'sub.name as subject_name', 'ss.is_compulsory')->orderBy('f.level')->orderBy('st.name')->orderBy('sub.name')->get());
    }

    public function unallocatedSubjects()
    {
        return response()->json(DB::table('stream_subjects as ss')->join('subjects as sub', 'ss.subject_id', '=', 'sub.id')->join('streams as st', 'ss.stream_id', '=', 'st.id')->join('forms as f', 'ss.form_id', '=', 'f.id')->leftJoin('teacher_subject_allocations as tsa', function ($join) { $join->on('tsa.subject_id', '=', 'ss.subject_id')->on('tsa.stream_id', '=', 'ss.stream_id')->on('tsa.academic_year_id', '=', 'ss.academic_year_id')->on('tsa.term_id', '=', 'ss.term_id'); })->whereNull('tsa.id')->select('ss.*', 'sub.name as subject_name', 'f.name as form_name', 'st.name as stream_name')->get());
    }

    public function streamsWithoutTeachers()
    {
        return response()->json(DB::table('stream_subjects as ss')->join('streams as st', 'ss.stream_id', '=', 'st.id')->join('forms as f', 'ss.form_id', '=', 'f.id')->leftJoin('teacher_subject_allocations as tsa', function ($join) { $join->on('tsa.stream_id', '=', 'ss.stream_id')->on('tsa.academic_year_id', '=', 'ss.academic_year_id')->on('tsa.term_id', '=', 'ss.term_id'); })->whereNull('tsa.id')->select('ss.stream_id', 'f.name as form_name', 'st.name as stream_name')->distinct()->get());
    }
}
