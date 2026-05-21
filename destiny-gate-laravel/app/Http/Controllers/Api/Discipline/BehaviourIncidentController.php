<?php

namespace App\Http\Controllers\Api\Discipline;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BehaviourIncidentController extends Controller
{
    private function nextIncidentNumber(): string
    {
        $year = date('Y');
        $last = DB::table('behaviour_incidents')->where('incident_number', 'like', "BEH-{$year}-%")->orderByDesc('id')->value('incident_number');
        $seq = $last ? ((int)substr($last, -4)) + 1 : 1;
        return "BEH-{$year}-" . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    private function query()
    {
        return DB::table('behaviour_incidents as bi')
            ->join('students as s', 'bi.student_id', '=', 's.id')
            ->join('behaviour_categories as bc', 'bi.behaviour_category_id', '=', 'bc.id')
            ->join('academic_years as ay', 'bi.academic_year_id', '=', 'ay.id')
            ->join('terms as t', 'bi.term_id', '=', 't.id')
            ->leftJoin('users as ru', 'bi.reported_by', '=', 'ru.id')
            ->leftJoin('users as vu', 'bi.reviewed_by', '=', 'vu.id')
            ->select('bi.*', DB::raw("CONCAT(s.first_name,' ',s.last_name) as student_name"), 's.student_number', 'bc.name as category_name', 'bc.type as category_type', 'ay.name as academic_year_name', 't.name as term_name', 'ru.name as reported_by_name', 'vu.name as reviewed_by_name');
    }

    private function notifyParent(int $studentId, string $type, string $title, string $message, string $relatedType, int $relatedId): void
    {
        $guardians = DB::table('guardians')->where('student_id', $studentId)
            ->where(function ($q) { $q->where('can_receive_notifications', true)->orWhereNull('can_receive_notifications'); })
            ->get();
        foreach ($guardians as $guardian) {
            DB::table('student_notifications')->insert([
                'student_id' => $studentId,
                'guardian_id' => $guardian->id,
                'notification_type' => $type,
                'title' => $title,
                'message' => $message,
                'channel' => 'portal',
                'status' => 'sent',
                'related_type' => $relatedType,
                'related_id' => $relatedId,
                'created_by' => Auth::id(),
                'sent_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function index(Request $request)
    {
        $q = $this->query()->orderByDesc('bi.incident_date')->orderByDesc('bi.id');
        if ($request->student_id) $q->where('bi.student_id', $request->student_id);
        if ($request->review_status) $q->where('bi.review_status', $request->review_status);
        if ($request->severity) $q->where('bi.severity', $request->severity);
        if ($request->search) {
            $s = '%' . $request->search . '%';
            $q->where(function ($x) use ($s) { $x->where('bi.incident_number', 'like', $s)->orWhere('bi.title', 'like', $s)->orWhere('s.first_name', 'like', $s)->orWhere('s.last_name', 'like', $s)->orWhere('s.student_number', 'like', $s); });
        }
        return response()->json($q->limit((int)($request->limit ?? 100))->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id' => 'required|exists:terms,id',
            'behaviour_category_id' => 'required|exists:behaviour_categories,id',
            'incident_date' => 'required|date',
            'title' => 'required|string|max:150',
            'description' => 'required|string',
            'severity' => 'required|in:low,medium,high,critical',
        ]);

        DB::beginTransaction();
        try {
            $category = DB::table('behaviour_categories')->where('id', $data['behaviour_category_id'])->first();
            if (!$category || !$category->is_active) {
                DB::rollBack();
                return response()->json(['message' => 'Inactive behaviour categories cannot be used.'], 422);
            }
            $id = DB::table('behaviour_incidents')->insertGetId([
                ...$data,
                'incident_number' => $this->nextIncidentNumber(),
                'reported_by' => Auth::id(),
                'review_status' => 'pending',
                'parent_notified' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            if ($category->type === 'negative') {
                $this->notifyParent((int)$data['student_id'], 'behaviour', 'Behaviour Incident Recorded', $data['title'], 'behaviour_incidents', $id);
                DB::table('behaviour_incidents')->where('id', $id)->update(['parent_notified' => true]);
            }
            DB::commit();
            return response()->json(['message' => 'Incident recorded.', 'incident_id' => $id], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    public function show(int $id)
    {
        $incident = $this->query()->where('bi.id', $id)->first();
        abort_if(!$incident, 404, 'Incident not found.');
        $actions = DB::table('discipline_actions as da')->leftJoin('users as u', 'da.issued_by', '=', 'u.id')->select('da.*', 'u.name as issued_by_name')->where('da.behaviour_incident_id', $id)->orderByDesc('da.action_date')->get();
        return response()->json([...(array)$incident, 'actions' => $actions]);
    }

    public function update(Request $request, int $id)
    {
        abort_if(!DB::table('behaviour_incidents')->where('id', $id)->exists(), 404, 'Incident not found.');
        $data = $request->validate(['title' => 'required|string|max:150', 'description' => 'required|string', 'severity' => 'required|in:low,medium,high,critical', 'incident_date' => 'required|date']);
        DB::table('behaviour_incidents')->where('id', $id)->update([...$data, 'updated_at' => now()]);
        return response()->json(['message' => 'Incident updated.']);
    }

    public function review(int $id) { return $this->setStatus($id, 'reviewed'); }
    public function escalate(int $id) { return $this->setStatus($id, 'escalated'); }
    public function close(int $id) { return $this->setStatus($id, 'closed'); }

    private function setStatus(int $id, string $status)
    {
        abort_if(!DB::table('behaviour_incidents')->where('id', $id)->exists(), 404, 'Incident not found.');
        DB::table('behaviour_incidents')->where('id', $id)->update(['review_status' => $status, 'reviewed_by' => Auth::id(), 'updated_at' => now()]);
        return response()->json(['message' => "Incident {$status}."]);
    }
}
