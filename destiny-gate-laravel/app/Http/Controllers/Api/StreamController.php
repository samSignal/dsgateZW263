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
            ->leftJoin('categories', 'streams.category_id', '=', 'categories.id')
            ->select(
                'streams.*',
                'forms.name as form_name',
                'forms.level as form_level',
                'categories.name as category_name',
                'categories.code as category_code',
                'categories.is_active as category_is_active'
            )
            ->orderBy('forms.level')
            ->orderBy('streams.name');

        if ($request->filled('form_id')) {
            $query->where('streams.form_id', $request->form_id);
        }

        if ($request->filled('category_id')) {
            $query->where('streams.category_id', $request->category_id);
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'form_id'     => 'required|exists:forms,id',
            'category_id' => 'nullable|exists:categories,id',
            'name'        => 'required|string|max:100',
            'capacity'    => 'nullable|integer|min:1|max:200',
        ]);

        if (!empty($data['category_id']) && !$this->categoryIsActive((int) $data['category_id'])) {
            return response()->json(['message' => 'Inactive categories cannot be assigned to streams.'], 422);
        }

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
            'category_id'      => $data['category_id'] ?? null,
            'name'             => $data['name'],
            'capacity'         => $data['capacity'] ?? 40,
            'class_teacher_id' => null,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        return response()->json(
            DB::table('streams')
                ->join('forms', 'streams.form_id', '=', 'forms.id')
                ->leftJoin('categories', 'streams.category_id', '=', 'categories.id')
                ->select(
                    'streams.*',
                    'forms.name as form_name',
                    'forms.level as form_level',
                    'categories.name as category_name',
                    'categories.code as category_code',
                    'categories.is_active as category_is_active'
                )
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
            'category_id' => 'nullable|exists:categories,id',
            'name'        => 'required|string|max:100',
            'capacity'    => 'nullable|integer|min:1|max:200',
        ]);

        if (!empty($data['category_id']) && !$this->categoryIsActive((int) $data['category_id'])) {
            return response()->json(['message' => 'Inactive categories cannot be assigned to streams.'], 422);
        }

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
            'category_id' => $data['category_id'] ?? null,
            'name'        => $data['name'],
            'capacity'    => $data['capacity'] ?? $stream->capacity,
            'updated_at'  => now(),
        ]);

        return response()->json(
            DB::table('streams')
                ->join('forms', 'streams.form_id', '=', 'forms.id')
                ->leftJoin('categories', 'streams.category_id', '=', 'categories.id')
                ->select(
                    'streams.*',
                    'forms.name as form_name',
                    'forms.level as form_level',
                    'categories.name as category_name',
                    'categories.code as category_code',
                    'categories.is_active as category_is_active'
                )
                ->where('streams.id', $id)
                ->first()
        );
    }

    public function destroy(int $id)
    {
        $stream = DB::table('streams')->find($id);
        abort_if(!$stream, 404, 'Stream not found.');

        // Check if used in allocations or students
        $usedInAllocations = DB::table('teacher_allocations')->where('stream_id', $id)->exists();
        $usedInStudents    = DB::table('students')->where('stream_id', $id)->exists();

        if ($usedInAllocations || $usedInStudents) {
            return response()->json(['message' => 'Cannot delete: stream is in use.'], 422);
        }

        DB::table('streams')->where('id', $id)->delete();
        return response()->json(['message' => 'Stream deleted.']);
    }

    private function categoryIsActive(int $categoryId): bool
    {
        return DB::table('categories')
            ->where('id', $categoryId)
            ->where('is_active', true)
            ->exists();
    }
}
