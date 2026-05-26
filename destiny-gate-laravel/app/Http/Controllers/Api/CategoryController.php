<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('categories')
            ->select('categories.*', DB::raw('(SELECT COUNT(*) FROM streams WHERE streams.category_id = categories.id) as streams_count'))
            ->orderBy('name');

        if ($request->filled('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100|unique:categories,name',
            'code' => 'required|string|max:20|unique:categories,code',
            'description' => 'nullable|string|max:500',
            'is_active' => 'sometimes|boolean',
        ]);

        $id = DB::table('categories')->insertGetId([
            'name' => $data['name'],
            'code' => strtoupper($data['code']),
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(DB::table('categories')->find($id), 201);
    }

    public function update(Request $request, int $id)
    {
        abort_if(!DB::table('categories')->where('id', $id)->exists(), 404, 'Category not found.');

        $data = $request->validate([
            'name' => 'required|string|max:100|unique:categories,name,' . $id,
            'code' => 'required|string|max:20|unique:categories,code,' . $id,
            'description' => 'nullable|string|max:500',
            'is_active' => 'sometimes|boolean',
        ]);

        $updates = [
            'name' => $data['name'],
            'code' => strtoupper($data['code']),
            'description' => $data['description'] ?? null,
            'updated_at' => now(),
        ];

        if (array_key_exists('is_active', $data)) {
            $updates['is_active'] = $data['is_active'];
        }

        DB::table('categories')->where('id', $id)->update($updates);

        return response()->json(DB::table('categories')->find($id));
    }

    public function activate(int $id)
    {
        abort_if(!DB::table('categories')->where('id', $id)->exists(), 404, 'Category not found.');

        DB::table('categories')->where('id', $id)->update(['is_active' => true, 'updated_at' => now()]);

        return response()->json(['message' => 'Category activated.']);
    }

    public function deactivate(int $id)
    {
        abort_if(!DB::table('categories')->where('id', $id)->exists(), 404, 'Category not found.');

        DB::table('categories')->where('id', $id)->update(['is_active' => false, 'updated_at' => now()]);

        return response()->json(['message' => 'Category deactivated.']);
    }

    public function destroy(int $id)
    {
        abort_if(!DB::table('categories')->where('id', $id)->exists(), 404, 'Category not found.');

        if (DB::table('streams')->where('category_id', $id)->exists()) {
            return response()->json(['message' => 'Cannot delete category while streams are linked.'], 422);
        }

        DB::table('categories')->where('id', $id)->delete();

        return response()->json(['message' => 'Category deleted.']);
    }
}
