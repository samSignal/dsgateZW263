<?php

namespace App\Http\Controllers\Api\StreamNative;

use App\Http\Controllers\Controller;
use App\Support\FinancialClearance;
use App\Support\StreamNativeProgressionEngine;
use App\Support\StudentStreamResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProgressionController extends Controller
{
    public function myStatus(Request $request)
    {
        $studentId = (int) DB::table('students')->where('user_id', Auth::id())->value('id');
        abort_if(!$studentId, 404, 'Student profile not found.');
        $data = $request->validate([
            'academic_year_id' => 'nullable|integer',
            'term_id' => 'nullable|integer',
        ]);

        if (empty($data['academic_year_id']) || empty($data['term_id'])) {
            $current = FinancialClearance::currentPeriod();
            $request->merge([
                'academic_year_id' => $data['academic_year_id'] ?? $current?->academic_year_id,
                'term_id' => $data['term_id'] ?? $current?->term_id,
            ]);
        }

        return $this->status($studentId, $request);
    }

    public function myHistory()
    {
        $studentId = (int) DB::table('students')->where('user_id', Auth::id())->value('id');
        abort_if(!$studentId, 404, 'Student profile not found.');
        return $this->history($studentId);
    }

    public function status(int $studentId, Request $request)
    {
        $data = $request->validate([
            'academic_year_id' => 'required|integer',
            'term_id' => 'required|integer',
        ]);

        $student = DB::table('students')->where('id', $studentId)->first();
        abort_if(!$student, 404, 'Student not found.');
        $student = StudentStreamResolver::attachResolvedFields($student);

        $decision = StreamNativeProgressionEngine::decide($studentId, (int) $data['academic_year_id'], (int) $data['term_id']);

        $enrollment = DB::table('student_enrollments as se')
            ->leftJoin('forms as f', 'se.form_id', '=', 'f.id')
            ->leftJoin('streams as st', 'se.stream_id', '=', 'st.id')
            ->leftJoin('categories as cat', 'se.category_id', '=', 'cat.id')
            ->where('se.student_id', $studentId)
            ->where('se.academic_year_id', $data['academic_year_id'])
            ->where('se.term_id', $data['term_id'])
            ->select('se.*', 'f.name as form_name', 'st.name as stream_name', 'cat.name as category_name')
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
            'enrollment' => $enrollment,
            'decision' => $decision,
        ]);
    }

    public function history(int $studentId)
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
            ->orderByDesc('se.academic_year_id')
            ->orderByDesc('se.term_id')
            ->get();

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
            'history' => $enrollments,
        ]);
    }
}
