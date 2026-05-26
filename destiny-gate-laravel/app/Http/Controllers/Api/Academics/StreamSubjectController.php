<?php

namespace App\Http\Controllers\Api\Academics;

use App\Http\Controllers\Controller;
use App\Support\StudentStreamResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StreamSubjectController extends Controller
{
    private function query()
    {
        return DB::table('stream_subjects as ss')
            ->join('academic_years as ay', 'ss.academic_year_id', '=', 'ay.id')
            ->join('terms as t', 'ss.term_id', '=', 't.id')
            ->join('forms as f', 'ss.form_id', '=', 'f.id')
            ->join('streams as st', 'ss.stream_id', '=', 'st.id')
            ->join('subjects as sub', 'ss.subject_id', '=', 'sub.id')
            ->select('ss.*', 'ay.name as academic_year_name', 't.name as term_name', 'f.name as form_name', 'st.name as stream_name', 'sub.name as subject_name', 'sub.code as subject_code', 'sub.is_active as subject_is_active');
    }

    public function index(Request $request)
    {
        $q = $this->query()->orderBy('f.level')->orderBy('st.name')->orderBy('sub.name');
        if ($request->stream_id) $q->where('ss.stream_id', $request->stream_id);
        if ($request->academic_year_id) $q->where('ss.academic_year_id', $request->academic_year_id);
        if ($request->term_id) $q->where('ss.term_id', $request->term_id);
        return response()->json($q->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id' => 'required|exists:terms,id',
            'form_id' => 'required|exists:forms,id',
            'stream_id' => 'required|exists:streams,id',
            'subjects' => 'required|array|min:1',
            'subjects.*.subject_id' => 'required|exists:subjects,id',
            'subjects.*.is_compulsory' => 'nullable|boolean',
        ]);

        $streamForm = DB::table('streams')->where('id', $data['stream_id'])->value('form_id');
        if ((int)$streamForm !== (int)$data['form_id']) return response()->json(['message' => 'Stream does not belong to the selected form.'], 422);

        DB::beginTransaction();
        try {
            $created = 0;
            foreach ($data['subjects'] as $row) {
                $subject = DB::table('subjects')->where('id', $row['subject_id'])->first();
                if (!$subject || (isset($subject->is_active) && !$subject->is_active)) {
                    DB::rollBack();
                    return response()->json(['message' => 'Inactive subjects cannot be assigned.'], 422);
                }
                $exists = DB::table('stream_subjects')->where('academic_year_id', $data['academic_year_id'])->where('term_id', $data['term_id'])->where('stream_id', $data['stream_id'])->where('subject_id', $row['subject_id'])->exists();
                if ($exists) {
                    DB::rollBack();
                    return response()->json(['message' => 'Duplicate stream subject assignment blocked.'], 422);
                }
                $streamSubjectId = DB::table('stream_subjects')->insertGetId([
                    'academic_year_id' => $data['academic_year_id'],
                    'term_id' => $data['term_id'],
                    'form_id' => $data['form_id'],
                    'stream_id' => $data['stream_id'],
                    'subject_id' => $row['subject_id'],
                    'is_compulsory' => $row['is_compulsory'] ?? false,
                    'created_by' => Auth::id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $created++;
                if (!empty($row['is_compulsory'])) $this->autoEnrollForStream($streamSubjectId);
            }
            DB::commit();
            return response()->json(['message' => 'Stream subjects assigned.', 'created' => $created], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    private function autoEnrollForStream(int $streamSubjectId): void
    {
        $ss = DB::table('stream_subjects')->where('id', $streamSubjectId)->first();
        if (!$ss) return;
        $studentIds = StudentStreamResolver::studentIdsForStream((int) $ss->stream_id, (int) $ss->academic_year_id);
        foreach ($studentIds as $studentId) {
            DB::table('student_subjects')->updateOrInsert(
                ['student_id' => $studentId, 'subject_id' => $ss->subject_id, 'academic_year_id' => $ss->academic_year_id, 'term_id' => $ss->term_id],
                ['stream_subject_id' => $ss->id, 'is_compulsory' => true, 'enrollment_status' => 'active', 'created_by' => Auth::id(), 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }

    public function update(Request $request, int $id)
    {
        abort_if(!DB::table('stream_subjects')->where('id', $id)->exists(), 404, 'Stream subject not found.');
        $data = $request->validate(['is_compulsory' => 'required|boolean']);
        DB::table('stream_subjects')->where('id', $id)->update(['is_compulsory' => $data['is_compulsory'], 'updated_at' => now()]);
        if ($data['is_compulsory']) $this->autoEnrollForStream($id);
        return response()->json(['message' => 'Stream subject updated.']);
    }

    public function destroy(int $id)
    {
        if (DB::table('student_subjects')->where('stream_subject_id', $id)->where('enrollment_status', 'active')->exists()) {
            return response()->json(['message' => 'Cannot delete: students are enrolled in this stream subject.'], 422);
        }
        DB::table('stream_subjects')->where('id', $id)->delete();
        return response()->json(['message' => 'Stream subject removed.']);
    }

    public function streamSubjects(Request $request, int $streamId)
    {
        $q = $this->query()->where('ss.stream_id', $streamId);
        if ($request->academic_year_id) $q->where('ss.academic_year_id', $request->academic_year_id);
        if ($request->term_id) $q->where('ss.term_id', $request->term_id);
        return response()->json($q->get());
    }
}
