<?php

namespace App\Http\Controllers\Api\Academics;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StudentSubjectController extends Controller
{
    private function studentStream(int $studentId)
    {
        $student = DB::table('students as s')->join('classes as c', 's.class_id', '=', 'c.id')->select('s.*', 'c.class_name', 'c.stream')->where('s.id', $studentId)->first();
        abort_if(!$student, 404, 'Student not found.');
        $formId = DB::table('forms')->where('name', $student->class_name)->value('id');
        $streamId = DB::table('streams')->where('form_id', $formId)->where('name', $student->stream)->value('id');
        return [$student, $formId, $streamId];
    }

    private function query()
    {
        return DB::table('student_subjects as ss')
            ->join('students as s', 'ss.student_id', '=', 's.id')
            ->join('stream_subjects as strsub', 'ss.stream_subject_id', '=', 'strsub.id')
            ->join('subjects as sub', 'ss.subject_id', '=', 'sub.id')
            ->join('academic_years as ay', 'ss.academic_year_id', '=', 'ay.id')
            ->join('terms as t', 'ss.term_id', '=', 't.id')
            ->select('ss.*', DB::raw("CONCAT(s.first_name,' ',s.last_name) as student_name"), 's.student_number', 'sub.name as subject_name', 'sub.code as subject_code', 'ay.name as academic_year_name', 't.name as term_name');
    }

    public function index(Request $request)
    {
        $q = $this->query()->orderBy('student_name')->orderBy('subject_name');
        if ($request->student_id) $q->where('ss.student_id', $request->student_id);
        if ($request->status) $q->where('ss.enrollment_status', $request->status);
        return response()->json($q->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id' => 'required|exists:terms,id',
            'stream_subject_ids' => 'required|array|min:1',
            'stream_subject_ids.*' => 'required|exists:stream_subjects,id',
        ]);

        [$student, $formId, $streamId] = $this->studentStream((int)$data['student_id']);
        DB::beginTransaction();
        try {
            $created = 0;
            foreach ($data['stream_subject_ids'] as $streamSubjectId) {
                $ss = DB::table('stream_subjects as ss')->join('subjects as sub', 'ss.subject_id', '=', 'sub.id')->select('ss.*', 'sub.is_active as subject_is_active')->where('ss.id', $streamSubjectId)->first();
                if (!$ss || (int)$ss->stream_id !== (int)$streamId || (int)$ss->form_id !== (int)$formId || (int)$ss->academic_year_id !== (int)$data['academic_year_id'] || (int)$ss->term_id !== (int)$data['term_id']) {
                    DB::rollBack();
                    return response()->json(['message' => 'Student can only select subjects assigned to their stream for this year and term.'], 422);
                }
                if (isset($ss->subject_is_active) && !$ss->subject_is_active) {
                    DB::rollBack();
                    return response()->json(['message' => 'Inactive subjects cannot be selected.'], 422);
                }
                $existing = DB::table('student_subjects')->where('student_id', $data['student_id'])->where('subject_id', $ss->subject_id)->where('academic_year_id', $data['academic_year_id'])->where('term_id', $data['term_id'])->first();
                if ($existing && $existing->enrollment_status === 'active') {
                    DB::rollBack();
                    return response()->json(['message' => 'Duplicate subject enrolment blocked.'], 422);
                }
                DB::table('student_subjects')->updateOrInsert(
                    ['student_id' => $data['student_id'], 'subject_id' => $ss->subject_id, 'academic_year_id' => $data['academic_year_id'], 'term_id' => $data['term_id']],
                    ['stream_subject_id' => $ss->id, 'is_compulsory' => $ss->is_compulsory, 'enrollment_status' => 'active', 'created_by' => Auth::id(), 'updated_at' => now(), 'created_at' => now()]
                );
                $created++;
            }
            DB::commit();
            return response()->json(['message' => 'Student subjects saved.', 'enrolled' => $created], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    public function dropSubject(int $id)
    {
        $record = DB::table('student_subjects')->where('id', $id)->first();
        abort_if(!$record, 404, 'Student subject not found.');
        if ($record->is_compulsory) return response()->json(['message' => 'Compulsory subjects cannot be dropped.'], 422);
        DB::beginTransaction();
        try {
            DB::table('student_subjects')->where('id', $id)->update(['enrollment_status' => 'dropped', 'updated_at' => now()]);
            DB::commit();
            return response()->json(['message' => 'Subject dropped.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    public function studentSubjects(int $studentId)
    {
        return response()->json($this->query()->where('ss.student_id', $studentId)->where('ss.enrollment_status', 'active')->get());
    }

    public function autoEnrollCompulsorySubjects(Request $request, int $studentId)
    {
        $data = $request->validate(['academic_year_id' => 'required|exists:academic_years,id', 'term_id' => 'required|exists:terms,id']);
        [$student, $formId, $streamId] = $this->studentStream($studentId);
        $ids = DB::table('stream_subjects')->where('form_id', $formId)->where('stream_id', $streamId)->where('academic_year_id', $data['academic_year_id'])->where('term_id', $data['term_id'])->where('is_compulsory', true)->pluck('id')->toArray();
        $request->merge(['student_id' => $studentId, 'stream_subject_ids' => $ids]);
        return $this->store($request);
    }

    public function mySubjects()
    {
        $studentId = DB::table('students')->where('user_id', Auth::id())->value('id');
        abort_if(!$studentId, 404, 'Student profile not found.');
        return $this->studentSubjects((int)$studentId);
    }

    public function childSubjects(int $studentId)
    {
        abort_if(!DB::table('guardians')->where('user_id', Auth::id())->where('student_id', $studentId)->exists(), 403, 'You cannot view this child.');
        return $this->studentSubjects($studentId);
    }
}
