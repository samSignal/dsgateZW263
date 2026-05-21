<?php

namespace App\Http\Controllers\Api\Assessment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AssessmentController extends Controller
{
    private function nextNumber(): string
    {
        $year = date('Y');
        $last = DB::table('assessments')
            ->where('assessment_number', 'like', "ASS-{$year}-%")
            ->orderByDesc('id')
            ->value('assessment_number');

        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;
        return "ASS-{$year}-" . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    private function staffIdForUser(?int $userId = null): ?int
    {
        $userId ??= Auth::id();
        return DB::table('staff')->where('user_id', $userId)->value('id')
            ?: DB::table('staff_members')->where('user_id', $userId)->value('id');
    }

    private function isTeacherAllocated(int $subjectId, int $streamId, int $academicYearId, int $termId): bool
    {
        $staffId = $this->staffIdForUser();
        if (!$staffId) return false;

        return DB::table('teacher_subject_allocations')
            ->where('teacher_id', $staffId)
            ->where('subject_id', $subjectId)
            ->where('stream_id', $streamId)
            ->where('academic_year_id', $academicYearId)
            ->where('term_id', $termId)
            ->exists();
    }

    private function classPartsForStream(int $streamId): ?object
    {
        return DB::table('streams as st')
            ->join('forms as f', 'st.form_id', '=', 'f.id')
            ->where('st.id', $streamId)
            ->select('f.name as class_name', 'st.name as stream_name')
            ->first();
    }

    private function withJoins()
    {
        return DB::table('assessments as a')
            ->join('academic_years as ay', 'a.academic_year_id', '=', 'ay.id')
            ->join('terms as t', 'a.term_id', '=', 't.id')
            ->join('streams as st', 'a.stream_id', '=', 'st.id')
            ->join('forms as f', 'st.form_id', '=', 'f.id')
            ->join('subjects as sub', 'a.subject_id', '=', 'sub.id')
            ->join('assessment_types as at', 'a.assessment_type_id', '=', 'at.id')
            ->join('users as u', 'a.created_by', '=', 'u.id')
            ->select(
                'a.*',
                'ay.name as academic_year_name',
                't.name as term_name',
                'st.name as stream_name',
                'f.name as form_name',
                'sub.name as subject_name',
                'sub.code as subject_code',
                'at.name as type_name',
                'u.name as created_by_name',
                DB::raw('(SELECT COUNT(*) FROM assessment_marks WHERE assessment_marks.assessment_id = a.id) as marks_count'),
                DB::raw('(SELECT COUNT(*) FROM assessment_marks WHERE assessment_marks.assessment_id = a.id AND assessment_marks.status = "entered") as entered_count')
            );
    }

    public function index(Request $request)
    {
        $q = $this->withJoins()->orderByDesc('a.assessment_date');
        $user = Auth::user();

        if ($user->role === 'teacher') {
            $staffId = $this->staffIdForUser($user->id);
            $allocations = $staffId
                ? DB::table('teacher_subject_allocations')
                    ->where('teacher_id', $staffId)
                    ->select('subject_id', 'stream_id', 'academic_year_id', 'term_id')
                    ->get()
                : collect();

            if ($allocations->isEmpty()) {
                $q->whereRaw('1 = 0');
            } else {
                $q->where(function ($outer) use ($allocations) {
                    foreach ($allocations as $allocation) {
                        $outer->orWhere(function ($inner) use ($allocation) {
                            $inner->where('a.subject_id', $allocation->subject_id)
                                ->where('a.stream_id', $allocation->stream_id)
                                ->where('a.academic_year_id', $allocation->academic_year_id)
                                ->where('a.term_id', $allocation->term_id);
                        });
                    }
                });
            }
        }

        if ($request->academic_year_id) $q->where('a.academic_year_id', $request->academic_year_id);
        if ($request->term_id) $q->where('a.term_id', $request->term_id);
        if ($request->stream_id) $q->where('a.stream_id', $request->stream_id);
        if ($request->subject_id) $q->where('a.subject_id', $request->subject_id);
        if ($request->status) $q->where('a.status', $request->status);
        if ($request->assessment_type_id) $q->where('a.assessment_type_id', $request->assessment_type_id);

        return response()->json($q->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id' => 'required|exists:terms,id',
            'stream_id' => 'required|exists:streams,id',
            'subject_id' => 'required|exists:subjects,id',
            'assessment_type_id' => 'required|exists:assessment_types,id',
            'title' => 'required|string|max:200',
            'total_marks' => 'required|numeric|min:1',
            'assessment_date' => 'required|date',
            'status' => 'nullable|in:draft,open',
        ]);

        if (Auth::user()->role === 'teacher' && !$this->isTeacherAllocated((int) $data['subject_id'], (int) $data['stream_id'], (int) $data['academic_year_id'], (int) $data['term_id'])) {
            return response()->json(['message' => 'You can only create assessments for subjects and streams allocated to you.'], 403);
        }

        $streamClass = $this->classPartsForStream((int) $data['stream_id']);
        if (!$streamClass) {
            return response()->json(['message' => 'Stream not found.'], 422);
        }

        $exists = DB::table('assessments')
            ->where('academic_year_id', $data['academic_year_id'])
            ->where('term_id', $data['term_id'])
            ->where('stream_id', $data['stream_id'])
            ->where('subject_id', $data['subject_id'])
            ->where('title', $data['title'])
            ->where('assessment_date', $data['assessment_date'])
            ->whereNotIn('status', ['cancelled'])
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'An assessment with the same title, stream, subject, term, and date already exists.'], 422);
        }

        DB::beginTransaction();
        try {
            $assessmentId = DB::table('assessments')->insertGetId([
                'assessment_number' => $this->nextNumber(),
                'academic_year_id' => $data['academic_year_id'],
                'term_id' => $data['term_id'],
                'stream_id' => $data['stream_id'],
                'subject_id' => $data['subject_id'],
                'assessment_type_id' => $data['assessment_type_id'],
                'title' => $data['title'],
                'total_marks' => $data['total_marks'],
                'assessment_date' => $data['assessment_date'],
                'created_by' => Auth::id(),
                'status' => $data['status'] ?? 'open',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $students = DB::table('students as s')
                ->join('classes as c', 's.class_id', '=', 'c.id')
                ->join('student_subjects as ss', function ($join) use ($data) {
                    $join->on('ss.student_id', '=', 's.id')
                        ->where('ss.subject_id', '=', $data['subject_id'])
                        ->where('ss.academic_year_id', '=', $data['academic_year_id'])
                        ->where('ss.term_id', '=', $data['term_id'])
                        ->where('ss.enrollment_status', '=', 'active');
                })
                ->where('c.class_name', $streamClass->class_name)
                ->where('c.stream', $streamClass->stream_name)
                ->where('s.status', 'active')
                ->distinct()
                ->pluck('s.id');

            $rows = $students->map(fn ($studentId) => [
                'assessment_id' => $assessmentId,
                'student_id' => $studentId,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ])->toArray();

            if ($rows) DB::table('assessment_marks')->insert($rows);

            DB::commit();

            return response()->json([
                'message' => 'Assessment created with ' . count($rows) . ' enrolled student(s).',
                'assessment' => $this->withJoins()->where('a.id', $assessmentId)->first(),
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    public function show(int $id)
    {
        $assessment = $this->withJoins()->where('a.id', $id)->first();
        abort_if(!$assessment, 404, 'Assessment not found.');

        $marks = DB::table('assessment_marks as am')
            ->join('students as s', 'am.student_id', '=', 's.id')
            ->leftJoin('users as eu', 'am.entered_by', '=', 'eu.id')
            ->select(
                'am.*',
                DB::raw("CONCAT(s.first_name,' ',s.last_name) as student_name"),
                's.student_number',
                's.admission_number',
                'eu.name as entered_by_name'
            )
            ->where('am.assessment_id', $id)
            ->orderBy('s.first_name')
            ->orderBy('s.last_name')
            ->get();

        return response()->json([...(array) $assessment, 'marks' => $marks]);
    }

    public function update(Request $request, int $id)
    {
        $assessment = DB::table('assessments')->find($id);
        abort_if(!$assessment, 404, 'Assessment not found.');

        if (!in_array($assessment->status, ['draft', 'open'])) {
            return response()->json(['message' => 'Only draft or open assessments can be edited.'], 422);
        }

        $data = $request->validate([
            'title' => 'required|string|max:200',
            'total_marks' => 'required|numeric|min:1',
            'assessment_date' => 'required|date',
            'status' => 'nullable|in:draft,open',
        ]);

        $exists = DB::table('assessments')
            ->where('academic_year_id', $assessment->academic_year_id)
            ->where('term_id', $assessment->term_id)
            ->where('stream_id', $assessment->stream_id)
            ->where('subject_id', $assessment->subject_id)
            ->where('title', $data['title'])
            ->where('assessment_date', $data['assessment_date'])
            ->where('id', '!=', $id)
            ->whereNotIn('status', ['cancelled'])
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'An assessment with the same title, stream, subject, term, and date already exists.'], 422);
        }

        DB::table('assessments')->where('id', $id)->update([
            'title' => $data['title'],
            'total_marks' => $data['total_marks'],
            'assessment_date' => $data['assessment_date'],
            'status' => $data['status'] ?? $assessment->status,
            'updated_at' => now(),
        ]);

        return response()->json($this->withJoins()->where('a.id', $id)->first());
    }

    public function submit(int $id)
    {
        $assessment = DB::table('assessments')->find($id);
        abort_if(!$assessment, 404, 'Assessment not found.');

        if (!in_array($assessment->status, ['open', 'draft'])) {
            return response()->json(['message' => 'Only open or draft assessments can be submitted.'], 422);
        }

        DB::table('assessments')->where('id', $id)->update(['status' => 'submitted', 'updated_at' => now()]);
        return response()->json(['message' => 'Assessment submitted for approval.']);
    }

    public function approve(int $id)
    {
        $assessment = DB::table('assessments')->find($id);
        abort_if(!$assessment, 404, 'Assessment not found.');

        if ($assessment->status !== 'submitted') {
            return response()->json(['message' => 'Only submitted assessments can be approved.'], 422);
        }

        DB::table('assessments')->where('id', $id)->update(['status' => 'approved', 'updated_at' => now()]);
        return response()->json(['message' => 'Assessment approved.']);
    }

    public function reopen(int $id)
    {
        $assessment = DB::table('assessments')->find($id);
        abort_if(!$assessment, 404, 'Assessment not found.');

        if ($assessment->status === 'cancelled') {
            return response()->json(['message' => 'Cancelled assessments cannot be reopened.'], 422);
        }

        DB::table('assessments')->where('id', $id)->update(['status' => 'open', 'updated_at' => now()]);
        return response()->json(['message' => 'Assessment reopened for editing.']);
    }

    public function cancel(int $id)
    {
        $assessment = DB::table('assessments')->find($id);
        abort_if(!$assessment, 404, 'Assessment not found.');

        if ($assessment->status === 'approved') {
            return response()->json(['message' => 'Approved assessments must be reopened before cancellation.'], 422);
        }

        DB::table('assessments')->where('id', $id)->update(['status' => 'cancelled', 'updated_at' => now()]);
        return response()->json(['message' => 'Assessment cancelled.']);
    }

    public function myAllocations()
    {
        $staffId = $this->staffIdForUser();
        if (!$staffId) return response()->json([]);

        return response()->json(
            DB::table('teacher_subject_allocations as ta')
                ->join('subjects as sub', 'ta.subject_id', '=', 'sub.id')
                ->join('streams as st', 'ta.stream_id', '=', 'st.id')
                ->join('forms as f', 'st.form_id', '=', 'f.id')
                ->join('academic_years as ay', 'ta.academic_year_id', '=', 'ay.id')
                ->join('terms as t', 'ta.term_id', '=', 't.id')
                ->select(
                    'ta.*',
                    'sub.name as subject_name',
                    'sub.code as subject_code',
                    'st.name as stream_name',
                    'f.name as form_name',
                    'ay.name as academic_year_name',
                    't.name as term_name'
                )
                ->where('ta.teacher_id', $staffId)
                ->orderBy('ay.name')
                ->orderBy('t.name')
                ->orderBy('f.name')
                ->orderBy('st.name')
                ->orderBy('sub.name')
                ->get()
        );
    }
}
