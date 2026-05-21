<?php

namespace App\Http\Controllers\Api\Discipline;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DisciplineActionController extends Controller
{
    private function nextActionNumber(): string
    {
        $year = date('Y');
        $last = DB::table('discipline_actions')->where('action_number', 'like', "DISC-{$year}-%")->orderByDesc('id')->value('action_number');
        $seq = $last ? ((int)substr($last, -4)) + 1 : 1;
        return "DISC-{$year}-" . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    private function query()
    {
        return DB::table('discipline_actions as da')
            ->join('students as s', 'da.student_id', '=', 's.id')
            ->join('behaviour_incidents as bi', 'da.behaviour_incident_id', '=', 'bi.id')
            ->leftJoin('users as u', 'da.issued_by', '=', 'u.id')
            ->select('da.*', 'bi.incident_number', 'bi.title as incident_title', DB::raw("CONCAT(s.first_name,' ',s.last_name) as student_name"), 's.student_number', 'u.name as issued_by_name');
    }

    private function notify(int $studentId, int $actionId, string $message): void
    {
        $guardians = DB::table('guardians')->where('student_id', $studentId)->where(function ($q) { $q->where('can_receive_notifications', true)->orWhereNull('can_receive_notifications'); })->get();
        foreach ($guardians as $guardian) {
            DB::table('student_notifications')->insert([
                'student_id' => $studentId,
                'guardian_id' => $guardian->id,
                'notification_type' => 'discipline',
                'title' => 'Disciplinary Action',
                'message' => $message,
                'channel' => 'portal',
                'status' => 'sent',
                'related_type' => 'discipline_actions',
                'related_id' => $actionId,
                'created_by' => Auth::id(),
                'sent_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function index(Request $request)
    {
        $q = $this->query()->orderByDesc('da.action_date')->orderByDesc('da.id');
        if ($request->student_id) $q->where('da.student_id', $request->student_id);
        if ($request->status) $q->where('da.status', $request->status);
        return response()->json($q->limit((int)($request->limit ?? 100))->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'behaviour_incident_id' => 'required|exists:behaviour_incidents,id',
            'action_type' => 'required|in:verbal_warning,written_warning,parent_meeting,detention,suspension,headmaster_review,counselling,other',
            'action_date' => 'required|date',
            'description' => 'required|string',
            'parent_required' => 'nullable|boolean',
            'parent_notified' => 'nullable|boolean',
            'follow_up_date' => 'nullable|date',
        ]);

        DB::beginTransaction();
        try {
            $incident = DB::table('behaviour_incidents')->where('id', $data['behaviour_incident_id'])->first();
            $id = DB::table('discipline_actions')->insertGetId([
                'action_number' => $this->nextActionNumber(),
                'behaviour_incident_id' => $data['behaviour_incident_id'],
                'student_id' => $incident->student_id,
                'action_type' => $data['action_type'],
                'action_date' => $data['action_date'],
                'description' => $data['description'],
                'issued_by' => Auth::id(),
                'parent_required' => $data['parent_required'] ?? false,
                'parent_notified' => false,
                'follow_up_date' => $data['follow_up_date'] ?? null,
                'status' => 'open',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            if (($data['parent_required'] ?? false) || ($data['parent_notified'] ?? false)) {
                $this->notify((int)$incident->student_id, $id, $data['description']);
                DB::table('discipline_actions')->where('id', $id)->update(['parent_notified' => true]);
            }
            DB::commit();
            return response()->json(['message' => 'Disciplinary action created.', 'action_id' => $id], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    public function show(int $id)
    {
        $action = $this->query()->where('da.id', $id)->first();
        abort_if(!$action, 404, 'Action not found.');
        return response()->json($action);
    }

    public function update(Request $request, int $id)
    {
        abort_if(!DB::table('discipline_actions')->where('id', $id)->exists(), 404, 'Action not found.');
        $data = $request->validate(['action_type' => 'required|in:verbal_warning,written_warning,parent_meeting,detention,suspension,headmaster_review,counselling,other', 'action_date' => 'required|date', 'description' => 'required|string', 'parent_required' => 'nullable|boolean', 'follow_up_date' => 'nullable|date']);
        DB::table('discipline_actions')->where('id', $id)->update([...$data, 'updated_at' => now()]);
        return response()->json(['message' => 'Action updated.']);
    }

    public function complete(int $id) { return $this->status($id, 'completed'); }
    public function cancel(int $id) { return $this->status($id, 'cancelled'); }

    private function status(int $id, string $status)
    {
        DB::table('discipline_actions')->where('id', $id)->update(['status' => $status, 'updated_at' => now()]);
        return response()->json(['message' => "Action {$status}."]);
    }
}
