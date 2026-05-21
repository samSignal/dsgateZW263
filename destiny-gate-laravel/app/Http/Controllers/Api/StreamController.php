<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StreamController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('streams')
            ->join('forms', 'streams.form_id', '=', 'forms.id')
            ->select(
                'streams.*',
                'forms.name as form_name',
                'forms.level as form_level'
            )
            ->orderBy('forms.level')
            ->orderBy('streams.name');

        if ($request->form_id) {
            $query->where('streams.form_id', $request->form_id);
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'form_id'  => 'required|exists:forms,id',
            'name'     => 'required|string|max:100',
            'capacity' => 'nullable|integer|min:1|max:200',
        ]);

        // Prevent duplicate name under same form
        $exists = DB::table('streams')
            ->where('form_id', $data['form_id'])
            ->where('name', $data['name'])
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Stream name already exists under this form.'], 422);
        }

        $id = DB::table('streams')->insertGetId([
            'form_id'          => $data['form_id'],
            'name'             => $data['name'],
            'capacity'         => $data['capacity'] ?? 40,
            'class_teacher_id' => null,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        return response()->json(
            DB::table('streams')
                ->join('forms', 'streams.form_id', '=', 'forms.id')
                ->select('streams.*', 'forms.name as form_name')
                ->where('streams.id', $id)
                ->first(),
            201
        );
    }

    public function update(Request $request, int $id)
    {
        $stream = DB::table('streams')->find($id);
        abort_if(!$stream, 404, 'Stream not found.');

        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'capacity' => 'nullable|integer|min:1|max:200',
        ]);

        // Prevent duplicate (excluding self)
        $exists = DB::table('streams')
            ->where('form_id', $stream->form_id)
            ->where('name', $data['name'])
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Stream name already exists under this form.'], 422);
        }

        DB::table('streams')->where('id', $id)->update([
            'name'       => $data['name'],
            'capacity'   => $data['capacity'] ?? $stream->capacity,
            'updated_at' => now(),
        ]);

        return response()->json(DB::table('streams')->find($id));
    }

    public function destroy(int $id)
    {
        $stream = DB::table('streams')->find($id);
        abort_if(!$stream, 404, 'Stream not found.');

        // Check if used in allocations or students
        $usedInAllocations = DB::table('teacher_allocations')->where('stream_id', $id)->exists();
        $usedInStudents    = DB::table('students')->where('class_id', $id)->exists();

        if ($usedInAllocations || $usedInStudents) {
            return response()->json(['message' => 'Cannot delete: stream is in use.'], 422);
        }

        DB::table('streams')->where('id', $id)->delete();
        return response()->json(['message' => 'Stream deleted.']);
    }
}
