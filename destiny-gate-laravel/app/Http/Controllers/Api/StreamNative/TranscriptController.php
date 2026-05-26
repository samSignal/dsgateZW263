<?php

namespace App\Http\Controllers\Api\StreamNative;

use App\Http\Controllers\Controller;
use App\Support\StudentStreamResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TranscriptController extends Controller
{
    public function me(Request $request)
    {
        $studentId = (int) DB::table('students')->where('user_id', Auth::id())->value('id');
        abort_if(!$studentId, 404, 'Student profile not found.');
        return $this->student($studentId, $request);
    }

    public function student(int $studentId, Request $request)
    {
        $student = DB::table('students')->where('id', $studentId)->first();
        abort_if(!$student, 404, 'Student not found.');
        $student = StudentStreamResolver::attachResolvedFields($student);

        $enrollments = DB::table('student_enrollments as se')
            ->leftJoin('academic_years as ay', 'se.academic_year_id', '=', 'ay.id')
            ->leftJoin('terms as t', 'se.term_id', '=', 't.id')
            ->leftJoin('forms as f', 'se.form_id', '=', 'f.id')
            ->leftJoin('streams as st', 'se.stream_id', '=', 'st.id')
            ->leftJoin('categories as cat', 'se.category_id', '=', 'cat.id')
            ->where('se.student_id', $studentId)
            ->select(
                'se.*',
                'ay.name as academic_year_name',
                't.name as term_name',
                'f.name as form_name',
                'st.name as stream_name',
                'cat.name as category_name'
            )
            ->orderBy('se.academic_year_id')
            ->orderBy('se.term_id')
            ->get();

        $years = DB::table('transcript_year_aggregates as tya')
            ->leftJoin('academic_years as ay', 'tya.academic_year_id', '=', 'ay.id')
            ->leftJoin('forms as f', 'tya.form_id', '=', 'f.id')
            ->leftJoin('streams as st', 'tya.stream_id', '=', 'st.id')
            ->leftJoin('categories as cat', 'tya.category_id', '=', 'cat.id')
            ->where('tya.student_id', $studentId)
            ->select(
                'tya.*',
                'ay.name as academic_year_name',
                'f.name as form_name',
                'st.name as stream_name',
                'cat.name as category_name'
            )
            ->orderBy('tya.academic_year_id')
            ->get();

        $subjects = DB::table('transcript_subject_history as tsh')
            ->join('subjects as sub', 'tsh.subject_id', '=', 'sub.id')
            ->leftJoin('academic_years as ay', 'tsh.academic_year_id', '=', 'ay.id')
            ->leftJoin('terms as t', 'tsh.term_id', '=', 't.id')
            ->leftJoin('forms as f', 'tsh.form_id', '=', 'f.id')
            ->leftJoin('streams as st', 'tsh.stream_id', '=', 'st.id')
            ->leftJoin('categories as cat', 'tsh.category_id', '=', 'cat.id')
            ->where('tsh.student_id', $studentId)
            ->select(
                'tsh.*',
                'sub.name as subject_name',
                'sub.code as subject_code',
                'ay.name as academic_year_name',
                't.name as term_name',
                'f.name as form_name',
                'st.name as stream_name',
                'cat.name as category_name'
            )
            ->orderBy('tsh.academic_year_id')
            ->orderBy('tsh.term_id')
            ->orderBy('sub.name')
            ->get();

        $graduation = DB::table('graduation_readiness as gr')
            ->leftJoin('academic_years as ay', 'gr.academic_year_id', '=', 'ay.id')
            ->where('gr.student_id', $studentId)
            ->select('gr.*', 'ay.name as academic_year_name')
            ->orderBy('gr.academic_year_id')
            ->get();

        $cumulativeGpa = null;
        $gpaRows = $years->pluck('gpa')->filter(fn ($v) => $v !== null)->values();
        if ($gpaRows->count()) {
            $cumulativeGpa = round($gpaRows->avg(), 2);
        }

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
            'cumulative_gpa' => $cumulativeGpa,
            'enrollments' => $enrollments,
            'year_aggregates' => $years,
            'subject_history' => $subjects,
            'graduation_readiness' => $graduation,
        ]);
    }
}
