<?php

namespace App\Http\Controllers\Api\StreamNative;

use App\Http\Controllers\Controller;
use App\Support\FinancialClearance;
use App\Support\StudentStreamResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GraduationController extends Controller
{
    public function me(Request $request)
    {
        $studentId = (int) DB::table('students')->where('user_id', Auth::id())->value('id');
        abort_if(!$studentId, 404, 'Student profile not found.');
        $data = $request->validate([
            'academic_year_id' => 'nullable|integer',
        ]);
        if (empty($data['academic_year_id'])) {
            $current = FinancialClearance::currentPeriod();
            $request->merge(['academic_year_id' => $current?->academic_year_id]);
        }
        return $this->student($studentId, $request);
    }

    public function student(int $studentId, Request $request)
    {
        $data = $request->validate([
            'academic_year_id' => 'required|integer',
        ]);

        $student = DB::table('students')->where('id', $studentId)->first();
        abort_if(!$student, 404, 'Student not found.');
        $student = StudentStreamResolver::attachResolvedFields($student);

        $row = DB::table('graduation_readiness as gr')
            ->leftJoin('academic_years as ay', 'gr.academic_year_id', '=', 'ay.id')
            ->leftJoin('forms as f', 'gr.form_id', '=', 'f.id')
            ->leftJoin('streams as st', 'gr.stream_id', '=', 'st.id')
            ->leftJoin('categories as cat', 'gr.category_id', '=', 'cat.id')
            ->where('gr.student_id', $studentId)
            ->where('gr.academic_year_id', $data['academic_year_id'])
            ->select(
                'gr.*',
                'ay.name as academic_year_name',
                'f.name as form_name',
                'st.name as stream_name',
                'cat.name as category_name'
            )
            ->first();

        return response()->json([
            'student' => [
                'id' => $student->id,
                'student_number' => $student->student_number ?? null,
                'admission_number' => $student->admission_number ?? null,
                'first_name' => $student->first_name ?? null,
                'last_name' => $student->last_name ?? null,
                'stream_id' => $student->stream_id,
                'stream_name' => $student->stream_name,
                'form_id' => $student->form_id,
                'form_name' => $student->form_name,
                'category_id' => $student->category_id,
                'category_name' => $student->category_name,
                'class_id' => $student->class_id,
                'class_name' => $student->class_name,
            ],
            'graduation' => $row,
        ]);
    }

    public function dashboard(Request $request)
    {
        $data = $request->validate([
            'academic_year_id' => 'required|integer',
            'stream_id' => 'nullable|integer',
            'form_id' => 'nullable|integer',
            'category_id' => 'nullable|integer',
        ]);

        $q = DB::table('graduation_readiness as gr')
            ->leftJoin('forms as f', 'gr.form_id', '=', 'f.id')
            ->leftJoin('streams as st', 'gr.stream_id', '=', 'st.id')
            ->leftJoin('categories as cat', 'gr.category_id', '=', 'cat.id')
            ->where('gr.academic_year_id', $data['academic_year_id'])
            ->select(
                'gr.status',
                DB::raw('COUNT(*) as total'),
                'gr.stream_id',
                'st.name as stream_name',
                'gr.form_id',
                'f.name as form_name',
                'gr.category_id',
                'cat.name as category_name'
            )
            ->groupBy('gr.status', 'gr.stream_id', 'st.name', 'gr.form_id', 'f.name', 'gr.category_id', 'cat.name')
            ->orderBy('f.name')
            ->orderBy('st.name');

        if (!empty($data['stream_id'])) $q->where('gr.stream_id', $data['stream_id']);
        if (!empty($data['form_id'])) $q->where('gr.form_id', $data['form_id']);
        if (!empty($data['category_id'])) $q->where('gr.category_id', $data['category_id']);

        return response()->json(['data' => $q->get()]);
    }
}
